<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuotationStatus;
use App\Enums\WorkspaceRole;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentProviderEvent;
use App\Models\Quotation;
use App\Models\QuotationAcceptance;
use App\Models\QuotationItem;
use App\Models\QuotationPublicToken;
use App\Models\QuotationRevision;
use App\Models\Receipt;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function acceptedFinanceQuote(array $overrides = []): array
{
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $workspace = Workspace::query()->create(['name' => 'Finance Studio', 'currency' => 'USD', 'payment_instructions' => 'Transfer using the invoice number.']);
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value, 'is_active' => true]);
    $customer = Customer::query()->create(['workspace_id' => $workspace->id, 'name' => 'Accepted Client', 'email' => 'client@example.test']);
    $quotation = Quotation::query()->create(['workspace_id' => $workspace->id, 'customer_id' => $customer->id, 'created_by' => $owner->id, 'number' => 'QF-2026-0001', 'status' => QuotationStatus::Approved, 'currency' => 'USD', 'issue_date' => now(), 'expiry_date' => now()->addDays(14)]);
    $revision = QuotationRevision::query()->create(['workspace_id' => $workspace->id, 'quotation_id' => $quotation->id, 'revision_number' => 1, 'title' => 'Accepted delivery', 'tax_mode' => 'exclusive', 'subtotal_minor' => 15000, 'discount_minor' => 0, 'tax_minor' => 1500, 'total_minor' => 16500, 'deposit_percentage' => 20, 'deposit_minor' => 3300, 'locked_at' => now()]);
    $quotation->update(['current_revision_id' => $revision->id]);
    $items = [
        ['id' => 1, 'name' => 'Core delivery', 'description' => 'Accepted core scope', 'quantity' => '1.0000', 'unit' => 'project', 'unit_price_minor' => 10000, 'tax_rate_basis_points' => 1000, 'line_subtotal_minor' => 10000, 'line_tax_minor' => 1000, 'line_total_minor' => 11000, 'is_optional' => false, 'is_selected' => true],
        ['id' => 2, 'name' => 'Optional support', 'description' => null, 'quantity' => '1.0000', 'unit' => 'package', 'unit_price_minor' => 5000, 'tax_rate_basis_points' => 1000, 'line_subtotal_minor' => 5000, 'line_tax_minor' => 500, 'line_total_minor' => 5500, 'is_optional' => true, 'is_selected' => true],
    ];
    foreach ($items as $position => $item) {
        QuotationItem::query()->create(['workspace_id' => $workspace->id, 'quotation_revision_id' => $revision->id, 'name' => $item['name'], 'description' => $item['description'], 'quantity' => $item['quantity'], 'unit' => $item['unit'], 'unit_price_minor' => $item['unit_price_minor'], 'tax_rate_basis_points' => $item['tax_rate_basis_points'], 'line_subtotal_minor' => $item['line_subtotal_minor'], 'line_tax_minor' => $item['line_tax_minor'], 'line_total_minor' => $item['line_total_minor'], 'is_optional' => $item['is_optional'], 'is_selected' => $item['is_selected'], 'position' => $position]);
    }
    $publicToken = QuotationPublicToken::query()->create(['workspace_id' => $workspace->id, 'quotation_id' => $quotation->id, 'quotation_revision_id' => $revision->id, 'created_by' => $owner->id, 'token_hash' => hash('sha256', 'finance-token-'.$quotation->id), 'expires_at' => now()->addDays(14)]);
    $snapshot = ['quotation' => ['number' => $quotation->number, 'currency' => 'USD'], 'customer' => ['name' => $customer->name, 'email' => $customer->email], 'revision' => ['revision_number' => 1, 'title' => 'Accepted delivery'], 'items' => $items, 'totals' => ['subtotal_minor' => 15000, 'discount_minor' => 0, 'tax_minor' => 1500, 'total_minor' => 16500, 'deposit_minor' => 3300], 'decision' => ['value' => 'approved', 'printed_name' => 'Client Person']];
    $snapshot = array_replace_recursive($snapshot, $overrides);
    $acceptance = QuotationAcceptance::query()->create(['workspace_id' => $workspace->id, 'quotation_id' => $quotation->id, 'quotation_revision_id' => $revision->id, 'quotation_public_token_id' => $publicToken->id, 'decision' => 'approved', 'printed_name' => 'Client Person', 'terms_accepted' => true, 'snapshot' => $snapshot, 'snapshot_hash' => hash('sha256', json_encode($snapshot)), 'decided_at' => now()]);

    return compact('owner', 'workspace', 'customer', 'quotation', 'revision', 'acceptance');
}

it('converts an approved acceptance into an immutable full invoice', function (): void {
    $fixture = acceptedFinanceQuote();
    $this->actingAs($fixture['owner'])->post(route('invoices.convert', $fixture['quotation']), ['type' => 'full', 'issue_date' => '2026-08-10', 'due_date' => '2026-08-24'])->assertRedirect();

    $invoice = Invoice::query()->with('items')->firstOrFail();
    expect($invoice->number)->toBe('INV-2026-0001')->and($invoice->total_minor)->toBe(16500)->and($invoice->balance_minor)->toBe(16500)
        ->and($invoice->snapshot['source_acceptance_hash'])->toBe($fixture['acceptance']->snapshot_hash)->and($invoice->snapshot_hash)->toHaveLength(64)
        ->and($invoice->items)->toHaveCount(2)->and($fixture['quotation']->fresh()->status)->toBe(QuotationStatus::Converted);
});

it('creates a deposit invoice from the accepted deposit amount', function (): void {
    $fixture = acceptedFinanceQuote();
    $this->actingAs($fixture['owner'])->post(route('invoices.convert', $fixture['quotation']), ['type' => 'deposit', 'issue_date' => '2026-08-10', 'due_date' => '2026-08-17'])->assertRedirect();

    $invoice = Invoice::query()->with('items')->firstOrFail();
    expect($invoice->type)->toBe('deposit')->and($invoice->total_minor)->toBe(3300)->and($invoice->items)->toHaveCount(1)->and($invoice->items->first()->total_minor)->toBe(3300);
});

it('blocks duplicate conversion and cross-workspace invoice access', function (): void {
    $fixture = acceptedFinanceQuote();
    $payload = ['type' => 'full', 'issue_date' => '2026-08-10', 'due_date' => '2026-08-24'];
    $this->actingAs($fixture['owner'])->post(route('invoices.convert', $fixture['quotation']), $payload)->assertRedirect();
    $invoice = Invoice::query()->firstOrFail();

    $other = User::factory()->create(['email_verified_at' => now()]);
    $otherWorkspace = Workspace::query()->create(['name' => 'Other workspace']);
    $otherWorkspace->users()->attach($other, ['role' => WorkspaceRole::Owner->value, 'is_active' => true]);
    $this->actingAs($other)->get(route('invoices.show', $invoice))->assertNotFound();
});

it('requires invoice permissions and an approved quotation', function (): void {
    $fixture = acceptedFinanceQuote();
    $viewer = User::factory()->create(['email_verified_at' => now()]);
    $fixture['workspace']->users()->attach($viewer, ['role' => WorkspaceRole::Viewer->value, 'is_active' => true]);
    $this->actingAs($viewer)->post(route('invoices.convert', $fixture['quotation']), ['type' => 'full', 'issue_date' => '2026-08-10', 'due_date' => '2026-08-24'])->assertForbidden();

    $fixture['quotation']->update(['status' => QuotationStatus::Sent]);
    $this->actingAs($fixture['owner'])->post(route('invoices.convert', $fixture['quotation']), ['type' => 'full', 'issue_date' => '2026-08-10', 'due_date' => '2026-08-24'])->assertSessionHasErrors('quotation');
});

it('records partial and final manual payments and issues immutable receipts', function (): void {
    $fixture = acceptedFinanceQuote();
    $this->actingAs($fixture['owner'])->post(route('invoices.convert', $fixture['quotation']), ['type' => 'full', 'issue_date' => '2026-08-10', 'due_date' => '2026-08-24']);
    $invoice = Invoice::query()->firstOrFail();

    $this->post(route('invoices.payments.manual', $invoice), ['amount' => '50.00', 'reference' => 'BANK-001', 'paid_at' => '2026-08-11'])->assertRedirect();
    expect($invoice->fresh()->paid_minor)->toBe(5000)->and($invoice->fresh()->balance_minor)->toBe(11500)->and($invoice->fresh()->status)->toBe(InvoiceStatus::Partial)
        ->and(Payment::query()->firstOrFail()->status)->toBe(PaymentStatus::Succeeded)
        ->and(Receipt::query()->firstOrFail()->number)->toBe('RCT-2026-0001')->and(Receipt::query()->firstOrFail()->snapshot_hash)->toHaveLength(64);

    $this->post(route('invoices.payments.manual', $invoice), ['amount' => '115.00', 'reference' => 'BANK-002', 'paid_at' => '2026-08-12'])->assertRedirect();
    expect($invoice->fresh()->balance_minor)->toBe(0)->and($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)->and(Receipt::query()->count())->toBe(2);
});

it('rejects overpayments and blocks users without payment permission', function (): void {
    $fixture = acceptedFinanceQuote();
    $this->actingAs($fixture['owner'])->post(route('invoices.convert', $fixture['quotation']), ['type' => 'full', 'issue_date' => '2026-08-10', 'due_date' => '2026-08-24']);
    $invoice = Invoice::query()->firstOrFail();
    $this->post(route('invoices.payments.manual', $invoice), ['amount' => '200.00', 'reference' => 'TOO-MUCH', 'paid_at' => '2026-08-11'])->assertSessionHasErrors('amount');
    expect(Payment::query()->count())->toBe(0);

    $viewer = User::factory()->create(['email_verified_at' => now()]);
    $fixture['workspace']->users()->attach($viewer, ['role' => WorkspaceRole::Viewer->value, 'is_active' => true]);
    $this->actingAs($viewer)->post(route('invoices.payments.manual', $invoice), ['amount' => '10.00', 'reference' => 'BLOCKED', 'paid_at' => '2026-08-11'])->assertForbidden();
});

it('accepts a signed Stripe webhook exactly once and ignores browser redirects', function (): void {
    $fixture = acceptedFinanceQuote();
    $this->actingAs($fixture['owner'])->post(route('invoices.convert', $fixture['quotation']), ['type' => 'full', 'issue_date' => '2026-08-10', 'due_date' => '2026-08-24']);
    $invoice = Invoice::query()->firstOrFail();
    config(['payments.stripe.webhook_secret' => 'whsec_test', 'payments.stripe.webhook_tolerance' => 300]);
    $event = ['id' => 'evt_once', 'type' => 'checkout.session.completed', 'livemode' => false, 'data' => ['object' => ['id' => 'cs_test', 'payment_intent' => 'pi_test', 'amount_total' => 16500, 'currency' => 'usd', 'metadata' => ['workspace_id' => (string) $fixture['workspace']->id, 'invoice_id' => (string) $invoice->id]]]];
    $payload = json_encode($event, JSON_THROW_ON_ERROR);
    $timestamp = time();
    $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

    $send = fn () => $this->call('POST', route('payments.webhook', 'stripe'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature], $payload);
    $send()->assertOk();
    $send()->assertOk();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)->and($invoice->fresh()->paid_minor)->toBe(16500)
        ->and(Payment::query()->count())->toBe(1)->and(Receipt::query()->count())->toBe(1)->and(PaymentProviderEvent::query()->count())->toBe(1);
});

it('rejects invalid Stripe signatures without changing invoice money', function (): void {
    $fixture = acceptedFinanceQuote();
    $this->actingAs($fixture['owner'])->post(route('invoices.convert', $fixture['quotation']), ['type' => 'full', 'issue_date' => '2026-08-10', 'due_date' => '2026-08-24']);
    $invoice = Invoice::query()->firstOrFail();
    config(['payments.stripe.webhook_secret' => 'whsec_test']);
    $this->call('POST', route('payments.webhook', 'stripe'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=wrong'], '{}')->assertBadRequest();
    expect($invoice->fresh()->paid_minor)->toBe(0)->and(Payment::query()->count())->toBe(0);
});

it('verifies PayPal webhooks remotely before applying a payment', function (): void {
    $fixture = acceptedFinanceQuote();
    $this->actingAs($fixture['owner'])->post(route('invoices.convert', $fixture['quotation']), ['type' => 'deposit', 'issue_date' => '2026-08-10', 'due_date' => '2026-08-17']);
    $invoice = Invoice::query()->firstOrFail();
    config(['payments.paypal.enabled' => true, 'payments.paypal.client_id' => 'client', 'payments.paypal.client_secret' => 'secret', 'payments.paypal.webhook_id' => 'hook', 'payments.paypal.api_url' => 'https://paypal.test']);
    Http::fake([
        'https://paypal.test/v1/oauth2/token' => Http::response(['access_token' => 'token']),
        'https://paypal.test/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
    ]);
    $event = ['id' => 'WH-ONE', 'event_type' => 'PAYMENT.CAPTURE.COMPLETED', 'resource_type' => 'capture', 'resource' => ['id' => 'CAPTURE-1', 'custom_id' => $fixture['workspace']->id.':'.$invoice->id, 'amount' => ['value' => '33.00', 'currency_code' => 'USD']]];
    $this->withHeaders(['PAYPAL-AUTH-ALGO' => 'SHA256withRSA', 'PAYPAL-CERT-URL' => 'https://paypal.test/cert', 'PAYPAL-TRANSMISSION-ID' => 'transmission-one', 'PAYPAL-TRANSMISSION-SIG' => 'signature', 'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String()])->postJson(route('payments.webhook', 'paypal'), $event)->assertOk();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)->and(Payment::query()->firstOrFail()->provider)->toBe('paypal');
});

it('reconciles invoice totals from successful payment records', function (): void {
    $fixture = acceptedFinanceQuote();
    $this->actingAs($fixture['owner'])->post(route('invoices.convert', $fixture['quotation']), ['type' => 'deposit', 'issue_date' => '2026-08-10', 'due_date' => '2026-08-17']);
    $invoice = Invoice::query()->firstOrFail();
    Payment::query()->create(['workspace_id' => $fixture['workspace']->id, 'invoice_id' => $invoice->id, 'recorded_by' => $fixture['owner']->id, 'provider' => 'manual', 'status' => PaymentStatus::Succeeded, 'currency' => 'USD', 'amount_minor' => 1000, 'reference' => 'IMPORT-1', 'paid_at' => now()]);
    $invoice->update(['paid_minor' => 0, 'balance_minor' => 3300]);
    $this->post(route('invoices.reconcile', $invoice))->assertRedirect();
    expect($invoice->fresh()->paid_minor)->toBe(1000)->and($invoice->fresh()->balance_minor)->toBe(2300)->and($invoice->fresh()->status)->toBe(InvoiceStatus::Partial);
});
