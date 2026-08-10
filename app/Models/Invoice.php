<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'customer_id', 'quotation_id', 'quotation_acceptance_id', 'created_by', 'number', 'type', 'status', 'currency', 'issue_date', 'due_date', 'subtotal_minor', 'tax_minor', 'total_minor', 'paid_minor', 'balance_minor', 'snapshot', 'snapshot_hash', 'payment_instructions'])]
final class Invoice extends Model
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

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'snapshot' => 'array',
            'subtotal_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
            'paid_minor' => 'integer',
            'balance_minor' => 'integer',
        ];
    }
}
