<?php

declare(strict_types=1);

use App\Enums\WorkspaceRole;
use App\Models\Customer;
use App\Models\Invitation;
use App\Models\Service;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function phaseTwoOwner(string $name = 'Primary Workspace'): array
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $workspace = Workspace::query()->create(['name' => $name, 'currency' => 'USD']);
    $workspace->users()->attach($user, ['role' => WorkspaceRole::Owner->value, 'is_active' => true]);

    return [$user, $workspace];
}

it('updates only the active workspace business settings', function (): void {
    [$user, $workspace] = phaseTwoOwner();
    $this->actingAs($user)->put('/settings/business', [
        'name' => 'Updated Studio', 'legal_name' => 'Updated Studio LLC', 'email' => 'billing@example.test',
        'currency' => 'AED', 'timezone' => 'Asia/Dubai', 'locale' => 'en', 'quotation_prefix' => 'QT',
        'default_validity_days' => 21, 'brand_color' => '#078A68',
    ])->assertRedirect();

    expect($workspace->fresh()->name)->toBe('Updated Studio')->and($workspace->fresh()->currency)->toBe('AED');
});

it('creates expiring workspace invitations with hashed tokens', function (): void {
    [$user, $workspace] = phaseTwoOwner();
    $this->actingAs($user)->post('/team/invitations', ['email' => 'sales@example.test', 'role' => 'sales'])->assertRedirect()->assertSessionHas('invitation_url');
    $invitation = Invitation::query()->firstOrFail();
    expect($invitation->workspace_id)->toBe($workspace->id)->and($invitation->token_hash)->toHaveLength(64)->and($invitation->expires_at->isFuture())->toBeTrue();
});

it('accepts a valid invitation only for the invited verified user', function (): void {
    [$owner, $workspace] = phaseTwoOwner();
    $invitee = User::factory()->create(['email' => 'invited@example.test', 'email_verified_at' => now()]);
    $this->actingAs($owner)->post('/team/invitations', ['email' => $invitee->email, 'role' => 'sales']);
    $url = session('invitation_url');
    $token = basename((string) $url);
    $this->actingAs($invitee)->post("/invitations/{$token}/accept")->assertRedirect('/dashboard');
    expect($workspace->users()->whereKey($invitee->id)->exists())->toBeTrue()->and(Invitation::query()->first()->accepted_at)->not->toBeNull();
});

it('creates customers with workspace activity and blocks cross-tenant access', function (): void {
    [$owner, $workspace] = phaseTwoOwner();
    [$otherOwner] = phaseTwoOwner('Other Workspace');
    $this->actingAs($owner)->post('/customers', ['type' => 'company', 'name' => 'Northwind Studio', 'email' => 'hello@northwind.test', 'currency' => 'USD', 'locale' => 'en', 'status' => 'active'])->assertRedirect();
    $customer = Customer::query()->firstOrFail();
    expect($customer->workspace_id)->toBe($workspace->id)->and($customer->activities()->count())->toBe(1);
    $this->actingAs($otherOwner)->get(route('customers.show', $customer))->assertNotFound();
});

it('adds customer contacts only inside the active tenant', function (): void {
    [$user, $workspace] = phaseTwoOwner();
    $customer = Customer::query()->create(['workspace_id' => $workspace->id, 'name' => 'Contact Co']);
    $this->actingAs($user)->post(route('customers.contacts.store', $customer), ['name' => 'Primary Person', 'email' => 'person@example.test', 'is_primary' => '1'])->assertRedirect();
    expect($customer->contacts()->count())->toBe(1)->and($customer->contacts()->first()->is_primary)->toBeTrue();
});

it('imports valid customer rows and skips duplicates or invalid rows', function (): void {
    [$user, $workspace] = phaseTwoOwner();
    Customer::query()->create(['workspace_id' => $workspace->id, 'name' => 'Existing Co', 'email' => 'existing@example.test']);
    $csv = "name,type,email,phone,currency,locale\nExisting Co,company,existing@example.test,,USD,en\nNew Client,individual,new@example.test,123,AED,en\nBad Email,company,not-an-email,,USD,en\n";
    $file = UploadedFile::fake()->createWithContent('customers.csv', $csv);
    $this->actingAs($user)->post('/customers/import', ['csv' => $file])->assertRedirect()->assertSessionHas('status', 'Import complete: 1 created, 2 skipped.');
    expect(Customer::query()->where('workspace_id', $workspace->id)->count())->toBe(2);
});

it('stores service rates as integer minor units', function (): void {
    [$user, $workspace] = phaseTwoOwner();
    $this->actingAs($user)->post('/services', ['name' => 'Discovery workshop', 'sku' => 'DISC-01', 'unit' => 'session', 'rate' => '1299.95', 'currency' => 'USD', 'tax_behavior' => 'standard', 'is_active' => '1'])->assertRedirect('/services');
    $service = Service::query()->firstOrFail();
    expect($service->workspace_id)->toBe($workspace->id)->and($service->rate_minor)->toBe(129995);
});

it('prevents a viewer from mutating customer records', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $workspace = Workspace::query()->create(['name' => 'Read Only Workspace']);
    $workspace->users()->attach($user, ['role' => WorkspaceRole::Viewer->value, 'is_active' => true]);
    $this->actingAs($user)->post('/customers', ['type' => 'company', 'name' => 'Denied', 'locale' => 'en', 'status' => 'active'])->assertForbidden();
});
