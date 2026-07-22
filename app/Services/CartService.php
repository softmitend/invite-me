<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Catalog;
use App\Models\User;
use Illuminate\Http\Request;

class CartService
{
    public function __construct(private readonly CatalogPricingService $pricing)
    {
    }

    public function current(Request $request): Cart
    {
        $sessionId = $request->session()->getId();
        $user = $request->user();

        $cart = Cart::firstOrCreate(
            $user ? ['user_id' => $user->id] : ['session_id' => $sessionId],
            ['session_id' => $user ? null : $sessionId]
        );

        if ($user) {
            $guestCart = Cart::where('session_id', $sessionId)->whereNull('user_id')->with('items')->first();

            if ($guestCart && $guestCart->isNot($cart)) {
                foreach ($guestCart->items as $item) {
                    $cart->items()->updateOrCreate(
                        ['catalog_id' => $item->catalog_id],
                        ['quantity' => $item->quantity]
                    );
                }

                $guestCart->delete();
            }
        }

        return $cart->load(['items.catalog.category', 'items.catalog.discount', 'items.catalog.images']);
    }

    public function add(Request $request, Catalog $catalog, int $quantity = 1): Cart
    {
        abort_unless($catalog->is_active, 422, 'Katalog tidak aktif.');

        $cart = $this->current($request);
        $item = $cart->items()->firstOrNew(['catalog_id' => $catalog->id]);
        $item->quantity = max(1, $item->exists ? $item->quantity + $quantity : $quantity);
        $item->save();

        return $this->current($request);
    }

    public function totals(Cart $cart): array
    {
        $subtotal = 0;
        $discount = 0;

        foreach ($cart->items as $item) {
            $catalog = $item->catalog;
            $subtotal += $catalog->base_price * $item->quantity;
            $discount += $this->pricing->discountAmount($catalog) * $item->quantity;
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
        ];
    }

    public function count(Request $request): int
    {
        return $this->current($request)->items->sum('quantity');
    }
}
