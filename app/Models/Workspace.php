<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'legal_name', 'email', 'phone', 'address', 'tax_id', 'currency', 'timezone', 'locale', 'quotation_prefix', 'default_validity_days', 'payment_instructions', 'brand_color', 'is_active'])]
class Workspace extends Model
{
    use HasFactory;

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Workspace $workspace): void {
            $workspace->ulid ??= (string) Str::ulid();
            $workspace->slug ??= Str::slug($workspace->name).'-'.Str::lower(Str::random(5));
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_memberships')
            ->withPivot(['id', 'role', 'is_active'])
            ->withTimestamps();
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'default_validity_days' => 'integer'];
    }
}
