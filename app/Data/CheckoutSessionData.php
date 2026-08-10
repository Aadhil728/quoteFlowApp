<?php

declare(strict_types=1);

namespace App\Data;

final readonly class CheckoutSessionData
{
    public function __construct(public string $providerId, public string $checkoutUrl) {}
}
