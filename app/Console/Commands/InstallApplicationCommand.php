<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\InstallApplication;
use App\Models\SystemSetting;
use Illuminate\Console\Command;

final class InstallApplicationCommand extends Command
{
    protected $signature = 'quoteflow:install {--mode=single_business} {--name=} {--email=} {--password=} {--workspace=}';

    protected $description = 'Install QuoteFlow AI and create the first administrator';

    public function handle(InstallApplication $installer): int
    {
        if (SystemSetting::valueOf('installed_at') !== null) {
            $this->error('QuoteFlow AI is already installed.');

            return self::FAILURE;
        }
        $installer->execute(['mode' => (string) $this->option('mode'), 'name' => (string) ($this->option('name') ?: $this->ask('Administrator name')), 'email' => (string) ($this->option('email') ?: $this->ask('Administrator email')), 'password' => (string) ($this->option('password') ?: $this->secret('Administrator password')), 'workspace_name' => (string) ($this->option('workspace') ?: $this->ask('Workspace name'))]);
        $this->info('QuoteFlow AI installed successfully.');

        return self::SUCCESS;
    }
}
