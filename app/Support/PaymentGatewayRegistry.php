<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

final class PaymentGatewayRegistry
{
    /** @param iterable<PaymentGateway> $gateways */
    public function __construct(private readonly iterable $gateways) {}

    public function get(string $provider): PaymentGateway
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->provider() === $provider) {
                return $gateway;
            }
        }

        throw new InvalidArgumentException('Unsupported payment provider.');
    }
}
