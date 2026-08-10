<?php

declare(strict_types=1);

namespace App\Domain\Quotations;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class MoneyCalculator
{
    /** @param list<array{quantity:string,unit_price_minor:int,tax_rate_basis_points:int,is_optional?:bool,is_selected?:bool}> $items */
    public function calculate(array $items, string $taxMode = 'exclusive', ?string $discountType = null, int $discountValue = 0, int $depositPercentage = 0): array
    {
        $subtotal = 0;
        $tax = 0;
        $lines = [];
        foreach ($items as $item) {
            $selected = ! ($item['is_optional'] ?? false) || ($item['is_selected'] ?? false);
            $lineSubtotal = $selected ? $this->multiply($item['unit_price_minor'], $item['quantity']) : 0;
            $rate = $item['tax_rate_basis_points'];
            $lineTax = $taxMode === 'inclusive' ? $this->inclusiveTax($lineSubtotal, $rate) : $this->percentage($lineSubtotal, $rate);
            $lines[] = ['subtotal_minor' => $lineSubtotal, 'tax_minor' => $lineTax, 'total_minor' => $taxMode === 'inclusive' ? $lineSubtotal : $lineSubtotal + $lineTax];
            $subtotal += $taxMode === 'inclusive' ? $lineSubtotal - $lineTax : $lineSubtotal;
            $tax += $lineTax;
        }
        $discount = $discountType === 'percentage' ? $this->percentage($subtotal, $discountValue) : ($discountType === 'fixed' ? min($discountValue, $subtotal) : 0);
        $discountTax = $subtotal > 0 ? $this->ratio($tax, $discount, $subtotal) : 0;
        $taxAfter = max(0, $tax - $discountTax);
        $total = max(0, $subtotal - $discount + $taxAfter);
        $deposit = $this->percentage($total, min(10000, max(0, $depositPercentage * 100)));

        return ['lines' => $lines, 'subtotal_minor' => $subtotal, 'discount_minor' => $discount, 'tax_minor' => $taxAfter, 'total_minor' => $total, 'deposit_minor' => $deposit];
    }

    private function multiply(int $minor, string $quantity): int
    {
        return BigDecimal::of($minor)->multipliedBy($quantity)->toScale(0, RoundingMode::HalfUp)->toInt();
    }

    private function percentage(int $amount, int $basisPoints): int
    {
        return $this->ratio($amount, $basisPoints, 10000);
    }

    private function inclusiveTax(int $amount, int $basisPoints): int
    {
        return $basisPoints === 0 ? 0 : $this->ratio($amount, $basisPoints, 10000 + $basisPoints);
    }

    private function ratio(int $amount, int $numerator, int $denominator): int
    {
        return BigDecimal::of($amount)->multipliedBy($numerator)->dividedBy($denominator, 0, RoundingMode::HalfUp)->toInt();
    }
}
