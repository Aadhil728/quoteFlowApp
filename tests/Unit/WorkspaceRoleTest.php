<?php

declare(strict_types=1);

use App\Enums\WorkspaceRole;

it('keeps viewer access read only', function (): void {
    expect(WorkspaceRole::Viewer->allows('customers.view'))->toBeTrue()
        ->and(WorkspaceRole::Viewer->allows('customers.manage'))->toBeFalse();
});
