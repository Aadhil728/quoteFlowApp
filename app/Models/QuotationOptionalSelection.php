<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['workspace_id', 'quotation_public_token_id', 'quotation_revision_id', 'quotation_item_id', 'is_selected'])]
class QuotationOptionalSelection extends Model
{
    public function item(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class, 'quotation_item_id');
    }

    protected function casts(): array
    {
        return ['is_selected' => 'boolean'];
    }
}
