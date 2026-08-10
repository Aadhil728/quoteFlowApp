<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['workspace_id', 'invoice_id', 'recorded_by', 'provider', 'status', 'currency', 'amount_minor', 'reference', 'provider_payment_id', 'paid_at', 'metadata'])]
final class Payment extends Model
{
    use HasUlids;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    protected function casts(): array
    {
        return ['status' => PaymentStatus::class, 'amount_minor' => 'integer', 'paid_at' => 'immutable_datetime', 'metadata' => 'array'];
    }
}
