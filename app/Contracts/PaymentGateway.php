<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\CheckoutSessionData;
use App\Data\VerifiedPaymentEventData;
use App\Models\Invoice;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function provider(): string;

    public function createCheckout(Invoice $invoice, string $successUrl, string $cancelUrl): CheckoutSessionData;

    public function verifyWebhook(Request $request): VerifiedPaymentEventData;
}
