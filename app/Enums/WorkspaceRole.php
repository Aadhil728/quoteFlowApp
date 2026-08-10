<?php

declare(strict_types=1);

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Administrator = 'administrator';
    case Sales = 'sales';
    case Finance = 'finance';
    case Viewer = 'viewer';

    public function allows(string $permission): bool
    {
        return match ($this) {
            self::Owner => true,
            self::Administrator => ! in_array($permission, ['workspace.billing', 'workspace.delete'], true),
            self::Sales => in_array($permission, ['dashboard.view', 'customers.view', 'customers.manage', 'services.view', 'quotations.view', 'quotations.manage'], true),
            self::Finance => in_array($permission, ['dashboard.view', 'customers.view', 'services.view', 'quotations.view', 'invoices.view', 'invoices.manage', 'payments.manage', 'reports.view'], true),
            self::Viewer => str_ends_with($permission, '.view'),
        };
    }
}
