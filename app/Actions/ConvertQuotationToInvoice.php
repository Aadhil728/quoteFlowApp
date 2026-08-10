<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\CreateInvoiceData;
use App\Enums\InvoiceStatus;
use App\Enums\QuotationStatus;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ConvertQuotationToInvoice
{
    public function execute(Workspace $workspace, Quotation $quotation, User $actor, CreateInvoiceData $data): Invoice
    {
        return DB::transaction(function () use ($workspace, $quotation, $actor, $data): Invoice {
            $locked = Quotation::query()->where('workspace_id', $workspace->id)->whereKey($quotation->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== QuotationStatus::Approved) {
                throw ValidationException::withMessages(['quotation' => 'Only an approved quotation can be converted.']);
            }

            $acceptance = $locked->acceptance()->where('workspace_id', $workspace->id)->where('decision', 'approved')->firstOrFail();
            if (Invoice::query()->where('workspace_id', $workspace->id)->where('quotation_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['quotation' => 'This quotation has already been converted.']);
            }

            $source = $acceptance->snapshot;
            $totals = $source['totals'];
            $isDeposit = $data->type === 'deposit';
            $total = $isDeposit ? (int) $totals['deposit_minor'] : (int) $totals['total_minor'];
            if ($isDeposit && $total < 1) {
                throw ValidationException::withMessages(['type' => 'This quotation does not require a deposit.']);
            }

            $number = $this->nextNumber($workspace);
            $snapshot = [
                'invoice' => ['number' => $number, 'type' => $data->type, 'currency' => $locked->currency, 'issue_date' => $data->issueDate, 'due_date' => $data->dueDate],
                'source_acceptance_hash' => $acceptance->snapshot_hash,
                'accepted_quotation' => $source,
                'amounts' => ['subtotal_minor' => $isDeposit ? $total : (int) $totals['subtotal_minor'] - (int) $totals['discount_minor'], 'tax_minor' => $isDeposit ? 0 : (int) $totals['tax_minor'], 'total_minor' => $total],
            ];
            $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $invoice = Invoice::query()->create([
                'workspace_id' => $workspace->id, 'customer_id' => $locked->customer_id, 'quotation_id' => $locked->id,
                'quotation_acceptance_id' => $acceptance->id, 'created_by' => $actor->id, 'number' => $number, 'type' => $data->type,
                'status' => InvoiceStatus::Draft, 'currency' => $locked->currency, 'issue_date' => $data->issueDate, 'due_date' => $data->dueDate,
                'subtotal_minor' => $snapshot['amounts']['subtotal_minor'], 'tax_minor' => $snapshot['amounts']['tax_minor'], 'total_minor' => $total,
                'paid_minor' => 0, 'balance_minor' => $total, 'snapshot' => $snapshot, 'snapshot_hash' => hash('sha256', $json),
                'payment_instructions' => $workspace->payment_instructions,
            ]);

            if ($isDeposit) {
                $invoice->items()->create(['workspace_id' => $workspace->id, 'name' => 'Deposit for '.$source['revision']['title'], 'description' => 'Deposit requested for accepted quotation '.$locked->number, 'quantity' => 1, 'unit' => 'deposit', 'unit_price_minor' => $total, 'tax_rate_basis_points' => 0, 'subtotal_minor' => $total, 'tax_minor' => 0, 'total_minor' => $total, 'position' => 0]);
            } else {
                $this->copyFullInvoiceItems($invoice, $source['items'], (int) $totals['discount_minor']);
            }

            $locked->update(['status' => QuotationStatus::Converted]);
            AuditLog::query()->create(['workspace_id' => $workspace->id, 'user_id' => $actor->id, 'event' => 'invoice.created_from_quotation', 'auditable_type' => Invoice::class, 'auditable_id' => $invoice->id, 'metadata' => ['quotation_id' => $locked->id, 'type' => $data->type, 'total_minor' => $total]]);

            return $invoice->load('items');
        });
    }

    private function nextNumber(Workspace $workspace): string
    {
        $prefix = 'INV-'.now()->format('Y').'-';
        $last = Invoice::query()->where('workspace_id', $workspace->id)->where('number', 'like', $prefix.'%')->lockForUpdate()->orderByDesc('number')->value('number');
        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function copyFullInvoiceItems(Invoice $invoice, array $items, int $discount): void
    {
        $position = 0;
        foreach ($items as $item) {
            if (! ($item['is_selected'] ?? false)) {
                continue;
            }
            $invoice->items()->create(['workspace_id' => $invoice->workspace_id, 'name' => $item['name'], 'description' => $item['description'] ?? null, 'quantity' => $item['quantity'], 'unit' => $item['unit'], 'unit_price_minor' => $item['unit_price_minor'], 'tax_rate_basis_points' => $item['tax_rate_basis_points'], 'subtotal_minor' => $item['line_subtotal_minor'], 'tax_minor' => $item['line_tax_minor'], 'total_minor' => $item['line_total_minor'], 'position' => $position++]);
        }
        if ($discount > 0) {
            $invoice->items()->create(['workspace_id' => $invoice->workspace_id, 'name' => 'Quotation discount', 'quantity' => 1, 'unit' => 'discount', 'unit_price_minor' => -$discount, 'tax_rate_basis_points' => 0, 'subtotal_minor' => -$discount, 'tax_minor' => 0, 'total_minor' => -$discount, 'position' => $position]);
        }
    }
}
