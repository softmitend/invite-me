<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request, CartService $cartService): View
    {
        $cart = $cartService->current($request);

        return view('cart.index', ['cart' => $cart, 'totals' => $cartService->totals($cart)]);
    }

    public function store(Request $request, Catalog $catalog, CartService $cartService): RedirectResponse
    {
        $cartService->add($request, $catalog, (int) $request->input('quantity', 1));

        if ($request->boolean('buy_now')) {
            return redirect()->route($request->user() ? 'checkout.index' : 'login')->with('success', 'Katalog ditambahkan. Silakan lanjutkan checkout.');
        }

        return back()->with('success', 'Katalog ditambahkan ke keranjang.');
    }

    public function destroy(Request $request, int $item, CartService $cartService): RedirectResponse
    {
        $cart = $cartService->current($request);
        $cart->items()->whereKey($item)->delete();

        return back()->with('success', 'Item dihapus dari keranjang.');
    }
}
