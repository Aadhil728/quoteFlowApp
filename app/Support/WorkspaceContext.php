<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Workspace;
use LogicException;

final class WorkspaceContext
{
    private ?Workspace $workspace = null;

    public function set(Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    public function get(): Workspace
    {
        return $this->workspace ?? throw new LogicException('Workspace context has not been resolved.');
    }

    public function id(): int
    {
        return $this->get()->getKey();
    }
}
