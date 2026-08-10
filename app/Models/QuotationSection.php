<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_id', 'quotation_revision_id', 'title', 'description', 'position'])]
class QuotationSection extends Model {}
