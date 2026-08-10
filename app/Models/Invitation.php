<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['workspace_id', 'invited_by', 'email', 'role', 'token_hash', 'expires_at', 'accepted_at'])]
class Invitation extends Model
{
    protected static function booted(): void
    {
        static::creating(fn (Invitation $invitation) => $invitation->ulid ??= (string) Str::ulid());
    }

    protected function casts(): array
    {
        return ['role' => WorkspaceRole::class, 'expires_at' => 'immutable_datetime', 'accepted_at' => 'immutable_datetime'];
    }
}
