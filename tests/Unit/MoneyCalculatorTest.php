<?php

declare(strict_types=1);

use App\Domain\Quotations\MoneyCalculator;

it('calculates exclusive tax discounts deposits and optional selections in minor units', function (): void {
    $result = app(MoneyCalculator::class)->calculate([
        ['quantity' => '2', 'unit_price_minor' => 10000, 'tax_rate_basis_points' => 1500],
        ['quantity' => '1', 'unit_price_minor' => 5000, 'tax_rate_basis_points' => 1500, 'is_optional' => true, 'is_selected' => false],
    ], 'exclusive', 'percentage', 1000, 25);

    expect($result)->toMatchArray([
        'subtotal_minor' => 20000,
        'discount_minor' => 2000,
        'tax_minor' => 2700,
        'total_minor' => 20700,
        'deposit_minor' => 5175,
    ])->and($result['lines'][1]['total_minor'])->toBe(0);
});

it('extracts inclusive tax without using floating point arithmetic', function (): void {
    $result = app(MoneyCalculator::class)->calculate([
        ['quantity' => '1', 'unit_price_minor' => 11500, 'tax_rate_basis_points' => 1500],
    ], 'inclusive');

    expect($result)->toMatchArray(['subtotal_minor' => 10000, 'tax_minor' => 1500, 'total_minor' => 11500]);
});
