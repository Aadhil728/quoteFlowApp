<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_id', 'quotation_id', 'quotation_revision_id', 'quotation_public_token_id', 'author_name', 'author_email', 'message'])]
class QuotationComment extends Model {}
