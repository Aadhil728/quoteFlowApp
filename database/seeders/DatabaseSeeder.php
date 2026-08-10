<?php

namespace Database\Seeders;

use App\Actions\SeedWorkspaceTemplates;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! User::query()->where('email', 'test@example.com')->exists()) {
            User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
        }

        Workspace::query()->each(fn (Workspace $workspace) => app(SeedWorkspaceTemplates::class)->execute($workspace));
    }
}
