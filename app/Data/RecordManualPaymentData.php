<?php

declare(strict_types=1);

namespace App\Data;

final readonly class RecordManualPaymentData
{
    public function __construct(
        public int $amountMinor,
        public string $reference,
        public string $paidAt,
    ) {}
}
