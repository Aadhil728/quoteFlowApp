<?php

declare(strict_types=1);

namespace App\Data;

final readonly class CreateInvoiceData
{
    public function __construct(
        public string $type,
        public string $issueDate,
        public string $dueDate,
    ) {}
}
