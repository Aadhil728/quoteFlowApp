<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_id', 'customer_id', 'name', 'email', 'phone', 'position', 'is_primary'])]
class CustomerContact extends Model
{
    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }
}
