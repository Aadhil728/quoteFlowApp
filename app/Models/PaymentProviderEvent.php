<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_id', 'provider', 'provider_event_id', 'type', 'payload_hash', 'normalized_payload', 'processed_at', 'failure'])]
final class PaymentProviderEvent extends Model
{
    protected function casts(): array
    {
        return ['normalized_payload' => 'array', 'processed_at' => 'immutable_datetime'];
    }
}
