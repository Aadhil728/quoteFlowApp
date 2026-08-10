<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuotationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'customer_id', 'created_by', 'assigned_to', 'current_revision_id', 'number', 'status', 'currency', 'reference', 'issue_date', 'expiry_date', 'last_saved_at'])]
class Quotation extends Model
{
    use HasUlids;
    use SoftDeletes;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(QuotationRevision::class, 'current_revision_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(QuotationRevision::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(QuotationEvent::class)->latest('created_at');
    }

    public function publicTokens(): HasMany
    {
        return $this->hasMany(QuotationPublicToken::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(QuotationComment::class);
    }

    public function acceptance(): HasOne
    {
        return $this->hasOne(QuotationAcceptance::class);
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected function casts(): array
    {
        return ['status' => QuotationStatus::class, 'issue_date' => 'date', 'expiry_date' => 'date', 'last_saved_at' => 'datetime'];
    }
}
