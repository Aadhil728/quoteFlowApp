<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'quotation_id', 'quotation_revision_id', 'created_by', 'token_hash', 'expires_at', 'revoked_at', 'last_accessed_at'])]
class QuotationPublicToken extends Model
{
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(QuotationRevision::class, 'quotation_revision_id');
    }

    public function selections(): HasMany
    {
        return $this->hasMany(QuotationOptionalSelection::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(QuotationComment::class);
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime', 'last_accessed_at' => 'immutable_datetime'];
    }
}
