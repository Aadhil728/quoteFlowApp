<?php

namespace App\Providers;

use App\Contracts\PdfRenderer;
use App\Infrastructure\Payments\PayPalGateway;
use App\Infrastructure\Payments\StripeGateway;
use App\Infrastructure\Pdf\DompdfRenderer;
use App\Support\PaymentGatewayRegistry;
use App\Support\WorkspaceContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(WorkspaceContext::class);
        $this->app->bind(PdfRenderer::class, DompdfRenderer::class);
        $this->app->singleton(PaymentGatewayRegistry::class, fn ($app) => new PaymentGatewayRegistry([$app->make(StripeGateway::class), $app->make(PayPalGateway::class)]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('public-quotation', fn (Request $request) => Limit::perMinute(30)->by(hash('sha256', (string) $request->route('token').'|'.$request->ip())));
    }
}
