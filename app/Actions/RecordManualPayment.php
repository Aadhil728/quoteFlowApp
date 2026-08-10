<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\RecordManualPaymentData;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecordManualPayment
{
    public function execute(Workspace $workspace, Invoice $invoice, User $actor, RecordManualPaymentData $data): Payment
    {
        return DB::transaction(function () use ($workspace, $invoice, $actor, $data): Payment {
            $locked = Invoice::query()->where('workspace_id', $workspace->id)->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($data->amountMinor < 1 || $data->amountMinor > $locked->balance_minor) {
                throw ValidationException::withMessages(['amount' => 'Payment must be greater than zero and cannot exceed the outstanding balance.']);
            }

            $payment = Payment::query()->create([
                'workspace_id' => $workspace->id, 'invoice_id' => $locked->id, 'recorded_by' => $actor->id,
                'provider' => 'manual', 'status' => PaymentStatus::Succeeded, 'currency' => $locked->currency,
                'amount_minor' => $data->amountMinor, 'reference' => $data->reference, 'paid_at' => $data->paidAt,
                'metadata' => ['recorded_via' => 'workspace'],
            ]);

            $paid = $locked->paid_minor + $data->amountMinor;
            $balance = $locked->total_minor - $paid;
            $locked->update(['paid_minor' => $paid, 'balance_minor' => $balance, 'status' => $balance === 0 ? InvoiceStatus::Paid : InvoiceStatus::Partial]);
            $number = $this->nextReceiptNumber($workspace);
            $snapshot = ['receipt' => ['number' => $number, 'issued_at' => now()->toIso8601String()], 'invoice' => ['number' => $locked->number, 'currency' => $locked->currency, 'total_minor' => $locked->total_minor], 'payment' => ['provider' => 'manual', 'amount_minor' => $data->amountMinor, 'reference' => $data->reference, 'paid_at' => $data->paidAt], 'balance_minor' => $balance];
            $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            Receipt::query()->create(['workspace_id' => $workspace->id, 'invoice_id' => $locked->id, 'payment_id' => $payment->id, 'number' => $number, 'snapshot' => $snapshot, 'snapshot_hash' => hash('sha256', $json), 'issued_at' => now()]);
            AuditLog::query()->create(['workspace_id' => $workspace->id, 'user_id' => $actor->id, 'event' => 'payment.manual_recorded', 'auditable_type' => Payment::class, 'auditable_id' => $payment->id, 'metadata' => ['invoice_id' => $locked->id, 'amount_minor' => $data->amountMinor]]);

            return $payment->load('receipt');
        });
    }

    private function nextReceiptNumber(Workspace $workspace): string
    {
        $prefix = 'RCT-'.now()->format('Y').'-';
        $last = Receipt::query()->where('workspace_id', $workspace->id)->where('number', 'like', $prefix.'%')->lockForUpdate()->orderByDesc('number')->value('number');
        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
