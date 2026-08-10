<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['workspace_id', 'invoice_id', 'name', 'description', 'quantity', 'unit', 'unit_price_minor', 'tax_rate_basis_points', 'subtotal_minor', 'tax_minor', 'total_minor', 'position'])]
final class InvoiceItem extends Model
{
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    protected function casts(): array
    {
        return ['unit_price_minor' => 'integer', 'tax_rate_basis_points' => 'integer', 'subtotal_minor' => 'integer', 'tax_minor' => 'integer', 'total_minor' => 'integer'];
    }
}
