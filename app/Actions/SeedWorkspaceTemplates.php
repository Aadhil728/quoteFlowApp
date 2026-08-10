<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Template;
use App\Models\Workspace;

final class SeedWorkspaceTemplates
{
    public function execute(Workspace $workspace): void
    {
        foreach ($this->definitions() as $definition) {
            $template = Template::query()->firstOrCreate(
                ['workspace_id' => $workspace->getKey(), 'name' => $definition['name']],
                ['style' => $definition['style'], 'is_active' => true],
            );

            $template->versions()->firstOrCreate(
                ['workspace_id' => $workspace->getKey(), 'version' => 1],
                ['content' => $definition['content']],
            );
        }
    }

    /** @return list<array{name:string,style:string,content:array<string, mixed>}> */
    private function definitions(): array
    {
        return [
            ['name' => 'Essential', 'style' => 'minimal', 'content' => ['accent' => '#0f766e', 'density' => 'compact', 'header' => 'clean']],
            ['name' => 'Studio', 'style' => 'professional', 'content' => ['accent' => '#2563eb', 'density' => 'comfortable', 'header' => 'banded']],
            ['name' => 'Signature', 'style' => 'modern', 'content' => ['accent' => '#7c3aed', 'density' => 'spacious', 'header' => 'editorial']],
        ];
    }
}
