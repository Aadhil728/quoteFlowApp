<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final class ReconcileInvoice
{
    public function execute(Workspace $workspace, Invoice $invoice, User $actor): Invoice
    {
        return DB::transaction(function () use ($workspace, $invoice, $actor): Invoice {
            $locked = Invoice::query()->where('workspace_id', $workspace->id)->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $paid = (int) $locked->payments()->where('status', PaymentStatus::Succeeded)->sum('amount_minor');
            $paid = min($paid, $locked->total_minor);
            $balance = $locked->total_minor - $paid;
            $status = $balance === 0 ? InvoiceStatus::Paid : ($paid > 0 ? InvoiceStatus::Partial : InvoiceStatus::Sent);
            $before = ['paid_minor' => $locked->paid_minor, 'balance_minor' => $locked->balance_minor, 'status' => $locked->status->value];
            $locked->update(['paid_minor' => $paid, 'balance_minor' => $balance, 'status' => $status]);
            AuditLog::query()->create(['workspace_id' => $workspace->id, 'user_id' => $actor->id, 'event' => 'invoice.reconciled', 'auditable_type' => Invoice::class, 'auditable_id' => $locked->id, 'metadata' => ['before' => $before, 'after' => ['paid_minor' => $paid, 'balance_minor' => $balance, 'status' => $status->value]]]);

            return $locked->fresh();
        });
    }
}
