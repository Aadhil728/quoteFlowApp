<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['workspace_id', 'service_category_id', 'name', 'sku', 'description', 'unit', 'rate_minor', 'currency', 'tax_behavior', 'is_active'])]
class Service extends Model
{
    protected static function booted(): void
    {
        static::creating(fn (Service $service) => $service->ulid ??= (string) Str::ulid());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'rate_minor' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
