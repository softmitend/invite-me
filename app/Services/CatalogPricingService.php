<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\Discount;

class CatalogPricingService
{
    public function discountAmount(Catalog $catalog): int
    {
        $discount = $catalog->discount;

        if (! $discount instanceof Discount || ! $discount->is_active) {
            return 0;
        }

        if (($discount->starts_at && $discount->starts_at->isFuture()) || ($discount->ends_at && $discount->ends_at->isPast())) {
            return 0;
        }

        if ($discount->type === Discount::TYPE_PERCENT) {
            return (int) min($catalog->base_price, round($catalog->base_price * ($discount->value / 100)));
        }

        return min($catalog->base_price, $discount->value);
    }

    public function finalPrice(Catalog $catalog): int
    {
        return max(0, $catalog->base_price - $this->discountAmount($catalog));
    }

    public function format(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
