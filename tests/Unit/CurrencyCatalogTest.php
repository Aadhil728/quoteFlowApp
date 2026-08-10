<?php

declare(strict_types=1);

use App\Support\CurrencyCatalog;

it('loads and groups the current official ISO 4217 catalogue', function (): void {
    $currencies = app(CurrencyCatalog::class)->all();

    expect($currencies)->toHaveCount(178)
        ->and($currencies['SAR']['name'])->toBe('Saudi Riyal')
        ->and($currencies['SAR']['countries'])->toContain('Saudi Arabia')
        ->and($currencies['EUR']['countries'])->toContain('Bulgaria')
        ->and($currencies)->not->toHaveKey('BGN')
        ->and(app(CurrencyCatalog::class)->selectOptions())->toHaveCount(178);
});
