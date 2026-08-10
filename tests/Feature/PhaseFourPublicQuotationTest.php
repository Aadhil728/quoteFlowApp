<?php

declare(strict_types=1);

use App\Enums\QuotationStatus;
use App\Enums\WorkspaceRole;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\QuotationAcceptance;
use App\Models\QuotationComment;
use App\Models\QuotationOptionalSelection;
use App\Models\QuotationPublicToken;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function publicQuoteFixture(): array
{
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $workspace = Workspace::query()->create(['name' => 'Public Quote Studio', 'currency' => 'USD']);
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner->value, 'is_active' => true]);
    $customer = Customer::query()->create(['workspace_id' => $workspace->id, 'name' => 'Client Example', 'email' => 'client@example.test']);
    $payload = [
        'customer_id' => $customer->id, 'currency' => 'USD', 'issue_date' => now()->toDateString(), 'expiry_date' => now()->addDays(14)->toDateString(),
        'title' => 'Public proposal', 'introduction' => 'A client-ready scope.', 'terms' => 'Payment due on approval.', 'tax_mode' => 'exclusive',
        'discount_value' => 0, 'deposit_percentage' => 20, 'items' => [
            ['name' => 'Core delivery', 'quantity' => '1', 'unit' => 'project', 'unit_price_minor' => 10000, 'tax_rate_basis_points' => 1000, 'is_selected' => true],
            ['name' => 'Optional support', 'quantity' => '1', 'unit' => 'package', 'unit_price_minor' => 5000, 'tax_rate_basis_points' => 1000, 'is_optional' => true, 'is_selected' => true],
        ],
    ];
    test()->actingAs($owner)->post(route('quotations.store'), $payload);
    $quotation = Quotation::query()->firstOrFail();
    test()->actingAs($owner)->post(route('quotations.transition', $quotation), ['status' => 'ready']);
    test()->actingAs($owner)->post(route('quotations.transition', $quotation), ['status' => 'sent']);
    test()->actingAs($owner)->post(route('quotations.share', $quotation));
    $url = session('public_url');

    return [$owner, $workspace, $quotation->fresh(), basename((string) $url)];
}

it('stores only a token hash and serves the locked quotation with privacy headers', function (): void {
    [, , $quotation, $raw] = publicQuoteFixture();
    $access = QuotationPublicToken::query()->firstOrFail();

    expect($access->token_hash)->toBe(hash('sha256', $raw))->and($access->token_hash)->not->toContain($raw);
    $this->get(route('public.quotation.show', $raw))->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertSee('Public proposal');
    expect($quotation->fresh()->status)->toBe(QuotationStatus::Viewed);
});

it('keeps public optional selections separate and recalculates authoritative totals', function (): void {
    [, , , $raw] = publicQuoteFixture();
    $optional = QuotationPublicToken::query()->firstOrFail()->revision->items()->where('is_optional', true)->firstOrFail();

    $this->post(route('public.quotation.select', $raw), ['items' => [$optional->id]])->assertRedirect();
    expect(QuotationOptionalSelection::query()->where('quotation_item_id', $optional->id)->firstOrFail()->is_selected)->toBeTrue();
    $this->get(route('public.quotation.show', $raw))->assertSee('USD 165.00');
});

it('records client comments and revision requests on the token revision', function (): void {
    [, , $quotation, $raw] = publicQuoteFixture();
    $this->post(route('public.quotation.comment', $raw), ['author_name' => 'Client Person', 'author_email' => 'client@example.test', 'message' => 'Can you confirm timing?'])->assertRedirect();
    expect(QuotationComment::query()->firstOrFail()->quotation_revision_id)->toBe($quotation->current_revision_id);

    $this->post(route('public.quotation.revision', $raw), ['author_name' => 'Client Person', 'message' => 'Please adjust the timeline.'])->assertRedirect();
    expect($quotation->fresh()->status)->toBe(QuotationStatus::RevisionRequested)->and(QuotationComment::query()->count())->toBe(2);
});

it('creates one immutable approval snapshot with privacy-conscious evidence', function (): void {
    [, , $quotation, $raw] = publicQuoteFixture();
    $this->get(route('public.quotation.show', $raw));
    $this->post(route('public.quotation.decision', $raw), ['decision' => 'approved', 'printed_name' => 'Client Person', 'terms_accepted' => '1'])->assertRedirect();

    $acceptance = QuotationAcceptance::query()->firstOrFail();
    expect($quotation->fresh()->status)->toBe(QuotationStatus::Approved)
        ->and($acceptance->snapshot_hash)->toHaveLength(64)
        ->and($acceptance->ip_hash)->toHaveLength(64)
        ->and($acceptance->snapshot['decision']['printed_name'])->toBe('Client Person');
    $this->post(route('public.quotation.decision', $raw), ['decision' => 'approved', 'printed_name' => 'Again', 'terms_accepted' => '1'])->assertStatus(409);
});

it('returns gone for expired or revoked public links', function (): void {
    [$owner, , $quotation, $raw] = publicQuoteFixture();
    $this->actingAs($owner)->delete(route('quotations.public-links.revoke', $quotation))->assertRedirect();
    $this->get(route('public.quotation.show', $raw))->assertStatus(410);
});

it('downloads a stable public PDF', function (): void {
    [, , , $raw] = publicQuoteFixture();
    $response = $this->get(route('public.quotation.pdf', $raw));
    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

it('rotates prior client links when a new link is created', function (): void {
    [$owner, , $quotation, $oldRaw] = publicQuoteFixture();
    $this->actingAs($owner)->post(route('quotations.share', $quotation))->assertRedirect();
    $newRaw = basename((string) session('public_url'));

    expect($newRaw)->not->toBe($oldRaw);
    $this->get(route('public.quotation.show', $oldRaw))->assertStatus(410);
    $this->get(route('public.quotation.show', $newRaw))->assertOk();
});

it('rate limits repeated access attempts against a public token', function (): void {
    $unknownToken = str_repeat('a', 96);
    foreach (range(1, 30) as $attempt) {
        $this->get(route('public.quotation.show', $unknownToken))->assertNotFound();
    }
    $this->get(route('public.quotation.show', $unknownToken))->assertTooManyRequests();
});
