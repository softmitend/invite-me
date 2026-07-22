<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(Request $request, CartService $cartService): View|RedirectResponse
    {
        $cart = $cartService->current($request);

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang masih kosong.');
        }

        return view('checkout.index', ['cart' => $cart, 'totals' => $cartService->totals($cart)]);
    }

    public function store(Request $request, CartService $cartService, CheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'payment_scheme' => ['required', 'in:'.Payment::TYPE_DEPOSIT.','.Payment::TYPE_FULL],
            'terms' => ['accepted'],
            'fields' => ['array'],
        ]);

        [$order] = $checkout->createOrder($request->user(), $cartService->current($request), $data);

        return redirect()->route('orders.show', $order)->with('success', 'Pesanan dibuat. Klik tombol Bayar QRIS untuk membuka halaman pembayaran Midtrans.');
    }
}
