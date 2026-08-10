<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessPaymentWebhook;
use App\Support\PaymentGatewayRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentGatewayRegistry $registry, ProcessPaymentWebhook $processor): JsonResponse
    {
        try {
            $event = $registry->get($provider)->verifyWebhook($request);
            $processor->execute($event, $request->getContent());

            return response()->json(['received' => true]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['received' => false], 400);
        }
    }
}
