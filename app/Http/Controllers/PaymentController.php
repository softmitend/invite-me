<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class PaymentController extends Controller
{
    public function pay(Request $request, Order $order, Payment $payment, MidtransService $midtrans): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id || $request->user()?->isAdmin(), 403);
        abort_unless($payment->order_id === $order->id, 404);
        abort_if($payment->status === Payment::STATUS_PAID, 422, 'Pembayaran ini sudah lunas.');

        try {
            if (blank($payment->snap_redirect_url) || str_starts_with($payment->snap_redirect_url, url('/'))) {
                $payment->update($midtrans->createSnapTransaction($order, $payment));
                $payment->refresh();
            }
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Belum bisa membuka QRIS. Periksa MIDTRANS_SERVER_KEY sandbox/production di .env.');
        }

        if (blank($payment->snap_redirect_url)) {
            return back()->with('error', 'Pembayaran QRIS belum siap. MIDTRANS_SERVER_KEY belum diisi.');
        }

        return redirect()->away($payment->snap_redirect_url);
    }
}
