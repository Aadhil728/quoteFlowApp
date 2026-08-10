<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['workspace_id', 'type', 'name', 'email', 'phone', 'tax_id', 'currency', 'locale', 'billing_address', 'notes', 'status'])]
class Customer extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(fn (Customer $customer) => $customer->ulid ??= (string) Str::ulid());
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CustomerActivity::class)->latest('created_at');
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
