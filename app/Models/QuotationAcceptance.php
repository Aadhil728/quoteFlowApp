<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_id', 'quotation_id', 'quotation_revision_id', 'quotation_public_token_id', 'decision', 'printed_name', 'terms_accepted', 'reason', 'snapshot', 'snapshot_hash', 'ip_hash', 'user_agent_hash', 'decided_at'])]
class QuotationAcceptance extends Model
{
    protected function casts(): array
    {
        return ['terms_accepted' => 'boolean', 'snapshot' => 'array', 'decided_at' => 'immutable_datetime'];
    }
}
