<?php

declare(strict_types=1);

namespace App\Contracts;

interface PdfRenderer
{
    public function render(string $html): string;
}
