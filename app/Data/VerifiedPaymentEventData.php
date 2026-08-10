<?php

declare(strict_types=1);

namespace App\Data;

final readonly class VerifiedPaymentEventData
{
    /** @param array<string,mixed> $normalizedPayload */
    public function __construct(
        public string $provider,
        public string $eventId,
        public string $type,
        public int $workspaceId,
        public int $invoiceId,
        public string $providerPaymentId,
        public string $status,
        public int $amountMinor,
        public string $currency,
        public array $normalizedPayload = [],
    ) {}
}
