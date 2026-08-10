<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments;

use App\Contracts\PaymentGateway;
use App\Data\CheckoutSessionData;
use App\Data\VerifiedPaymentEventData;
use App\Models\Invoice;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class StripeGateway implements PaymentGateway
{
    public function provider(): string
    {
        return 'stripe';
    }

    public function createCheckout(Invoice $invoice, string $successUrl, string $cancelUrl): CheckoutSessionData
    {
        $this->ensureConfigured();
        $response = $this->client()->asForm()->post(config('payments.stripe.api_url').'/v1/checkout/sessions', [
            'mode' => 'payment', 'success_url' => $successUrl, 'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $invoice->id,
            'metadata' => ['workspace_id' => (string) $invoice->workspace_id, 'invoice_id' => (string) $invoice->id],
            'payment_intent_data' => ['metadata' => ['workspace_id' => (string) $invoice->workspace_id, 'invoice_id' => (string) $invoice->id]],
            'line_items' => [['quantity' => 1, 'price_data' => ['currency' => strtolower($invoice->currency), 'unit_amount' => $invoice->balance_minor, 'product_data' => ['name' => 'Invoice '.$invoice->number]]]],
        ])->throw()->json();

        return new CheckoutSessionData((string) $response['id'], (string) $response['url']);
    }

    public function verifyWebhook(Request $request): VerifiedPaymentEventData
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature');
        $parts = collect(explode(',', $signature))->mapWithKeys(function (string $part): array {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

            return [$key => $value];
        });
        $timestamp = (int) $parts->get('t');
        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, (string) config('payments.stripe.webhook_secret'));
        if (! $timestamp || abs(time() - $timestamp) > (int) config('payments.stripe.webhook_tolerance') || ! hash_equals($expected, (string) $parts->get('v1'))) {
            throw new RuntimeException('Invalid Stripe webhook signature.');
        }

        $event = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        $object = $event['data']['object'];
        $metadata = $object['metadata'] ?? [];
        $status = match ($event['type']) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded', 'payment_intent.succeeded' => 'succeeded',
            'checkout.session.async_payment_failed', 'payment_intent.payment_failed' => 'failed',
            'charge.refunded' => 'refunded',
            default => 'ignored',
        };

        return new VerifiedPaymentEventData('stripe', (string) $event['id'], (string) $event['type'], (int) ($metadata['workspace_id'] ?? 0), (int) ($metadata['invoice_id'] ?? $object['client_reference_id'] ?? 0), (string) ($object['payment_intent'] ?? $object['id']), $status, (int) ($object['amount_total'] ?? $object['amount_received'] ?? $object['amount_refunded'] ?? 0), strtoupper((string) ($object['currency'] ?? '')), ['livemode' => (bool) ($event['livemode'] ?? false)]);
    }

    private function client(): PendingRequest
    {
        return Http::withToken((string) config('payments.stripe.secret'))->acceptJson()->timeout(20);
    }

    private function ensureConfigured(): void
    {
        if (! config('payments.stripe.enabled') || ! config('payments.stripe.secret')) {
            throw new RuntimeException('Stripe is not configured.');
        }
    }
}
