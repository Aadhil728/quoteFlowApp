<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OperatingMode;
use App\Enums\WorkspaceRole;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class InstallApplication
{
    /** @param array{name:string,email:string,password:string,mode:string,workspace_name:string} $data */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $mode = OperatingMode::from($data['mode']);
            $user = User::query()->create([
                'name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']),
                'email_verified_at' => now(), 'is_platform_admin' => true,
            ]);
            $workspace = Workspace::query()->create(['name' => $data['workspace_name']]);
            app(SeedWorkspaceTemplates::class)->execute($workspace);
            $workspace->users()->attach($user->getKey(), ['role' => WorkspaceRole::Owner->value, 'is_active' => true]);
            SystemSetting::query()->updateOrCreate(['key' => 'operating_mode'], ['value' => $mode->value]);
            SystemSetting::query()->updateOrCreate(['key' => 'installed_at'], ['value' => now()->toIso8601String()]);
            AuditLog::query()->create(['workspace_id' => $workspace->getKey(), 'user_id' => $user->getKey(), 'event' => 'application.installed', 'metadata' => ['mode' => $mode->value]]);

            return $user;
        });
    }
}
