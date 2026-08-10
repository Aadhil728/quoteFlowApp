<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\VerifiedPaymentEventData;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentProviderEvent;
use App\Models\Receipt;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ProcessPaymentWebhook
{
    public function execute(VerifiedPaymentEventData $data, string $payload): void
    {
        try {
            DB::transaction(function () use ($data, $payload): void {
                $event = PaymentProviderEvent::query()->create(['workspace_id' => $data->workspaceId ?: null, 'provider' => $data->provider, 'provider_event_id' => $data->eventId, 'type' => $data->type, 'payload_hash' => hash('sha256', $payload), 'normalized_payload' => $data->normalizedPayload]);
                if ($data->status === 'ignored') {
                    $event->update(['processed_at' => now()]);

                    return;
                }

                $invoice = Invoice::query()->where('workspace_id', $data->workspaceId)->whereKey($data->invoiceId)->lockForUpdate()->first();
                if (! $invoice || strtoupper($data->currency) !== $invoice->currency) {
                    throw new RuntimeException('Webhook invoice or currency does not match.');
                }

                $payment = Payment::query()->firstOrNew(['provider' => $data->provider, 'provider_payment_id' => $data->providerPaymentId]);
                $previouslySucceeded = $payment->exists && $payment->status === PaymentStatus::Succeeded;
                $payment->fill(['workspace_id' => $invoice->workspace_id, 'invoice_id' => $invoice->id, 'status' => PaymentStatus::from($data->status), 'currency' => $invoice->currency, 'amount_minor' => $data->amountMinor, 'paid_at' => $data->status === 'succeeded' ? now() : $payment->paid_at, 'metadata' => $data->normalizedPayload])->save();

                if ($data->status === 'succeeded' && ! $previouslySucceeded) {
                    if ($data->amountMinor < 1 || $data->amountMinor > $invoice->balance_minor) {
                        throw new RuntimeException('Webhook payment amount exceeds the invoice balance.');
                    }
                    $paid = $invoice->paid_minor + $data->amountMinor;
                    $balance = $invoice->total_minor - $paid;
                    $invoice->update(['paid_minor' => $paid, 'balance_minor' => $balance, 'status' => $balance === 0 ? InvoiceStatus::Paid : InvoiceStatus::Partial]);
                    $this->issueReceipt($invoice, $payment, $balance);
                } elseif ($data->status === 'refunded' && $previouslySucceeded) {
                    $paid = max(0, $invoice->paid_minor - $data->amountMinor);
                    $balance = $invoice->total_minor - $paid;
                    $invoice->update(['paid_minor' => $paid, 'balance_minor' => $balance, 'status' => $paid > 0 ? InvoiceStatus::Partial : InvoiceStatus::Sent]);
                }
                $event->update(['processed_at' => now()]);
            });
        } catch (UniqueConstraintViolationException) {
            // Provider event IDs are unique; retries are acknowledged without replaying money changes.
        } catch (RuntimeException $exception) {
            PaymentProviderEvent::query()->where('provider', $data->provider)->where('provider_event_id', $data->eventId)->update(['failure' => $exception->getMessage()]);
            throw $exception;
        }
    }

    private function issueReceipt(Invoice $invoice, Payment $payment, int $balance): void
    {
        if (Receipt::query()->where('payment_id', $payment->id)->exists()) {
            return;
        }
        $prefix = 'RCT-'.now()->format('Y').'-';
        $last = Receipt::query()->where('workspace_id', $invoice->workspace_id)->where('number', 'like', $prefix.'%')->lockForUpdate()->orderByDesc('number')->value('number');
        $number = $prefix.str_pad((string) ($last ? ((int) substr($last, -4)) + 1 : 1), 4, '0', STR_PAD_LEFT);
        $snapshot = ['receipt' => ['number' => $number, 'issued_at' => now()->toIso8601String()], 'invoice' => ['number' => $invoice->number, 'currency' => $invoice->currency, 'total_minor' => $invoice->total_minor], 'payment' => ['provider' => $payment->provider, 'provider_payment_id' => $payment->provider_payment_id, 'amount_minor' => $payment->amount_minor], 'balance_minor' => $balance];
        $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        Receipt::query()->create(['workspace_id' => $invoice->workspace_id, 'invoice_id' => $invoice->id, 'payment_id' => $payment->id, 'number' => $number, 'snapshot' => $snapshot, 'snapshot_hash' => hash('sha256', $json), 'issued_at' => now()]);
    }
}
