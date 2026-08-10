<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['workspace_id', 'invoice_id', 'payment_id', 'number', 'snapshot', 'snapshot_hash', 'issued_at'])]
final class Receipt extends Model
{
    use HasUlids;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'issued_at' => 'immutable_datetime'];
    }
}
