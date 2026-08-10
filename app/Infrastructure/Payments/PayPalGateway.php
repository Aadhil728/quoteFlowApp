<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments;

use App\Contracts\PaymentGateway;
use App\Data\CheckoutSessionData;
use App\Data\VerifiedPaymentEventData;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PayPalGateway implements PaymentGateway
{
    public function provider(): string
    {
        return 'paypal';
    }

    public function createCheckout(Invoice $invoice, string $successUrl, string $cancelUrl): CheckoutSessionData
    {
        $this->ensureConfigured();
        $response = Http::withToken($this->accessToken())->acceptJson()->timeout(20)->post(config('payments.paypal.api_url').'/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) $invoice->id,
                'custom_id' => $invoice->workspace_id.':'.$invoice->id,
                'invoice_id' => $invoice->number,
                'description' => 'Invoice '.$invoice->number,
                'amount' => ['currency_code' => $invoice->currency, 'value' => number_format($invoice->balance_minor / 100, 2, '.', '')],
            ]],
            'payment_source' => ['paypal' => ['experience_context' => ['return_url' => $successUrl, 'cancel_url' => $cancelUrl, 'user_action' => 'PAY_NOW']]],
        ])->throw()->json();
        $approval = collect($response['links'] ?? [])->firstWhere('rel', 'payer-action') ?? collect($response['links'] ?? [])->firstWhere('rel', 'approve');
        if (! $approval) {
            throw new RuntimeException('PayPal did not return an approval URL.');
        }

        return new CheckoutSessionData((string) $response['id'], (string) $approval['href']);
    }

    public function verifyWebhook(Request $request): VerifiedPaymentEventData
    {
        $this->ensureConfigured();
        $event = $request->json()->all();
        $verification = Http::withToken($this->accessToken())->acceptJson()->timeout(20)->post(config('payments.paypal.api_url').'/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'), 'cert_url' => $request->header('PAYPAL-CERT-URL'),
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'), 'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'), 'webhook_id' => config('payments.paypal.webhook_id'), 'webhook_event' => $event,
        ])->throw()->json();
        if (($verification['verification_status'] ?? null) !== 'SUCCESS') {
            throw new RuntimeException('Invalid PayPal webhook signature.');
        }

        $resource = $event['resource'] ?? [];
        $customId = $resource['custom_id'] ?? $resource['supplementary_data']['related_ids']['custom_id'] ?? '';
        [$workspaceId, $invoiceId] = array_pad(explode(':', (string) $customId, 2), 2, 0);
        $status = match ($event['event_type'] ?? '') {
            'PAYMENT.CAPTURE.COMPLETED' => 'succeeded',
            'PAYMENT.CAPTURE.PENDING' => 'processing',
            'PAYMENT.CAPTURE.DENIED', 'CHECKOUT.PAYMENT-APPROVAL.REVERSED' => 'failed',
            'PAYMENT.CAPTURE.REFUNDED' => 'refunded',
            default => 'ignored',
        };
        $amount = $resource['amount'] ?? $resource['seller_payable_breakdown']['gross_amount'] ?? [];

        return new VerifiedPaymentEventData('paypal', (string) $event['id'], (string) $event['event_type'], (int) $workspaceId, (int) $invoiceId, (string) ($resource['id'] ?? ''), $status, $this->toMinor((string) ($amount['value'] ?? '0')), strtoupper((string) ($amount['currency_code'] ?? '')), ['resource_type' => $event['resource_type'] ?? null]);
    }

    private function accessToken(): string
    {
        $response = Http::withBasicAuth((string) config('payments.paypal.client_id'), (string) config('payments.paypal.client_secret'))->asForm()->timeout(20)->post(config('payments.paypal.api_url').'/v1/oauth2/token', ['grant_type' => 'client_credentials'])->throw()->json();

        return (string) $response['access_token'];
    }

    private function ensureConfigured(): void
    {
        if (! config('payments.paypal.enabled') || ! config('payments.paypal.client_id') || ! config('payments.paypal.client_secret') || ! config('payments.paypal.webhook_id')) {
            throw new RuntimeException('PayPal is not configured.');
        }
    }

    private function toMinor(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
