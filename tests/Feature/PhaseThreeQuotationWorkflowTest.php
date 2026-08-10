<?php

declare(strict_types=1);

use App\Actions\SeedWorkspaceTemplates;
use App\Enums\QuotationStatus;
use App\Enums\WorkspaceRole;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function phaseThreeOwner(string $name = 'Quote Workspace'): array
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $workspace = Workspace::query()->create(['name' => $name, 'currency' => 'USD']);
    $workspace->users()->attach($user, ['role' => WorkspaceRole::Owner->value, 'is_active' => true]);
    $customer = Customer::query()->create(['workspace_id' => $workspace->id, 'name' => "{$name} Customer", 'email' => 'buyer@example.test']);

    return [$user, $workspace, $customer];
}

function phaseThreePayload(Customer $customer): array
{
    return [
        'customer_id' => $customer->id,
        'currency' => 'USD',
        'reference' => 'WEB-001',
        'issue_date' => now()->toDateString(),
        'expiry_date' => now()->addDays(14)->toDateString(),
        'title' => 'Website delivery proposal',
        'introduction' => 'A clear delivery scope.',
        'terms' => 'Valid for fourteen days.',
        'tax_mode' => 'exclusive',
        'discount_type' => null,
        'discount_value' => 0,
        'deposit_percentage' => 20,
        'items' => [[
            'name' => 'Implementation',
            'description' => 'Design and delivery',
            'quantity' => '2.5',
            'unit' => 'day',
            'unit_price_minor' => 10000,
            'tax_rate_basis_points' => 1000,
            'is_selected' => true,
        ]],
    ];
}

it('creates a versioned quotation with authoritative integer totals and a default section', function (): void {
    [$owner, $workspace, $customer] = phaseThreeOwner();

    $this->actingAs($owner)->post(route('quotations.store'), phaseThreePayload($customer))->assertRedirect();

    $quotation = Quotation::query()->firstOrFail();
    $revision = $quotation->currentRevision()->firstOrFail();
    expect($quotation->workspace_id)->toBe($workspace->id)
        ->and($quotation->number)->toMatch('/^QF-\d{4}-0001$/')
        ->and($revision->subtotal_minor)->toBe(25000)
        ->and($revision->tax_minor)->toBe(2500)
        ->and($revision->total_minor)->toBe(27500)
        ->and($revision->deposit_minor)->toBe(5500)
        ->and($revision->sections()->count())->toBe(1)
        ->and($revision->items()->firstOrFail()->quotation_section_id)->not->toBeNull();
});

it('blocks cross-tenant quotation access', function (): void {
    [$owner, , $customer] = phaseThreeOwner();
    [$otherOwner] = phaseThreeOwner('Other Quote Workspace');
    $this->actingAs($owner)->post(route('quotations.store'), phaseThreePayload($customer));

    $this->actingAs($otherOwner)->get(route('quotations.show', Quotation::query()->firstOrFail()))->assertNotFound();
});

it('locks a deterministic snapshot when sent and refuses subsequent edits', function (): void {
    [$owner, , $customer] = phaseThreeOwner();
    $this->actingAs($owner)->post(route('quotations.store'), phaseThreePayload($customer));
    $quotation = Quotation::query()->firstOrFail();

    $this->actingAs($owner)->post(route('quotations.transition', $quotation), ['status' => QuotationStatus::Ready->value])->assertRedirect();
    $this->actingAs($owner)->post(route('quotations.transition', $quotation), ['status' => QuotationStatus::Sent->value])->assertRedirect();

    $revision = $quotation->currentRevision()->firstOrFail()->fresh();
    expect($quotation->fresh()->status)->toBe(QuotationStatus::Sent)
        ->and($revision->locked_at)->not->toBeNull()
        ->and($revision->content_hash)->toHaveLength(64)
        ->and($revision->snapshot['quotation']['number'])->toBe($quotation->number);

    $this->actingAs($owner)->putJson(route('quotations.update', $quotation), phaseThreePayload($customer))->assertStatus(409);
});

it('clones locked sections and items into a new editable revision', function (): void {
    [$owner, , $customer] = phaseThreeOwner();
    $this->actingAs($owner)->post(route('quotations.store'), phaseThreePayload($customer));
    $quotation = Quotation::query()->firstOrFail();
    $sourceRevisionId = $quotation->current_revision_id;
    $quotation->update(['status' => QuotationStatus::RevisionRequested]);

    $this->actingAs($owner)->post(route('quotations.revise', $quotation))->assertRedirect();

    $quotation->refresh();
    expect($quotation->status)->toBe(QuotationStatus::Draft)
        ->and($quotation->current_revision_id)->not->toBe($sourceRevisionId)
        ->and($quotation->currentRevision()->firstOrFail()->revision_number)->toBe(2)
        ->and($quotation->currentRevision()->firstOrFail()->sections()->count())->toBe(1)
        ->and($quotation->currentRevision()->firstOrFail()->items()->count())->toBe(1);
});

it('rejects invalid status transitions', function (): void {
    [$owner, , $customer] = phaseThreeOwner();
    $this->actingAs($owner)->post(route('quotations.store'), phaseThreePayload($customer));

    $this->actingAs($owner)->post(route('quotations.transition', Quotation::query()->firstOrFail()), ['status' => QuotationStatus::Approved->value])->assertStatus(422);
});

it('renders a downloadable PDF and seeds three original workspace templates', function (): void {
    [$owner, $workspace, $customer] = phaseThreeOwner();
    app(SeedWorkspaceTemplates::class)->execute($workspace);
    $this->actingAs($owner)->post(route('quotations.store'), phaseThreePayload($customer));

    $response = $this->actingAs($owner)->get(route('quotations.pdf', Quotation::query()->firstOrFail()));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF')
        ->and($workspace->templates()->count())->toBe(3);
});
