<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['workspace_id', 'template_id', 'version', 'content'])]
class TemplateVersion extends Model
{
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    protected function casts(): array
    {
        return ['content' => 'array', 'version' => 'integer'];
    }
}
