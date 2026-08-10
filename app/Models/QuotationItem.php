<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_id', 'quotation_revision_id', 'quotation_section_id', 'service_id', 'name', 'description', 'quantity', 'unit', 'unit_price_minor', 'tax_rate_basis_points', 'is_optional', 'is_selected', 'position', 'line_subtotal_minor', 'line_tax_minor', 'line_total_minor'])]
class QuotationItem extends Model
{
    protected function casts(): array
    {
        return ['is_optional' => 'boolean', 'is_selected' => 'boolean', 'unit_price_minor' => 'integer', 'tax_rate_basis_points' => 'integer', 'line_subtotal_minor' => 'integer', 'line_tax_minor' => 'integer', 'line_total_minor' => 'integer'];
    }
}
