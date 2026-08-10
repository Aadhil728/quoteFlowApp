<?php

declare(strict_types=1);

use App\Enums\WorkspaceRole;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('installs either operating mode and creates the first administrator atomically', function (string $mode): void {
    $response = $this->post('/install', [
        'mode' => $mode, 'workspace_name' => 'Brill Studio', 'name' => 'Brill Owner',
        'email' => 'owner@example.test', 'password' => 'a-secure-password', 'password_confirmation' => 'a-secure-password',
    ]);
    $response->assertRedirect('/dashboard');
    expect(SystemSetting::valueOf('operating_mode'))->toBe($mode)
        ->and(User::query()->first()->is_platform_admin)->toBeTrue()
        ->and(Workspace::query()->first()->users()->first()->pivot->role)->toBe(WorkspaceRole::Owner->value);
})->with(['single_business', 'saas']);

it('registers a workspace owner and requires email verification', function (): void {
    Notification::fake();
    $response = $this->post('/register', [
        'name' => 'Aadhil Owner', 'email' => 'aadhil@example.test', 'workspace_name' => 'Aadhil Works',
        'password' => 'a-secure-password', 'password_confirmation' => 'a-secure-password',
    ]);
    $response->assertRedirect('/verify-email');
    $user = User::query()->where('email', 'aadhil@example.test')->firstOrFail();
    Notification::assertSentTo($user, VerifyEmail::class);
    $this->actingAs($user)->get('/dashboard')->assertRedirect('/verify-email');
});

it('resolves only an active workspace membership and rejects switching to another tenant', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $owned = Workspace::query()->create(['name' => 'Owned Workspace']);
    $other = Workspace::query()->create(['name' => 'Other Workspace']);
    $owned->users()->attach($user, ['role' => WorkspaceRole::Owner->value, 'is_active' => true]);

    $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Owned Workspace');
    $this->actingAs($user)->post('/workspaces/switch', ['workspace_id' => $other->id])->assertForbidden();
});

it('enforces centralized role permissions', function (): void {
    $user = User::factory()->create();
    $workspace = Workspace::query()->create(['name' => 'Permission Workspace']);
    $workspace->users()->attach($user, ['role' => WorkspaceRole::Finance->value, 'is_active' => true]);

    expect($user->canInWorkspace($workspace, 'payments.manage'))->toBeTrue()
        ->and($user->canInWorkspace($workspace, 'customers.manage'))->toBeFalse();
});
