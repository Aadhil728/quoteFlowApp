<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'quotation_id', 'revision_number', 'title', 'introduction', 'notes', 'terms', 'exclusions', 'client_responsibilities', 'tax_mode', 'discount_type', 'discount_value', 'subtotal_minor', 'discount_minor', 'tax_minor', 'total_minor', 'deposit_percentage', 'deposit_minor', 'snapshot', 'content_hash', 'locked_at'])]
class QuotationRevision extends Model
{
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(QuotationSection::class)->orderBy('position');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('position');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'locked_at' => 'immutable_datetime', 'discount_value' => 'integer', 'subtotal_minor' => 'integer', 'discount_minor' => 'integer', 'tax_minor' => 'integer', 'total_minor' => 'integer', 'deposit_percentage' => 'integer', 'deposit_minor' => 'integer'];
    }
}
