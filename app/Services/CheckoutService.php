<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private readonly CatalogPricingService $pricing,
        private readonly MidtransService $midtrans,
    ) {
    }

    public function createOrder(User $user, Cart $cart, array $data): array
    {
        return DB::transaction(function () use ($user, $cart, $data) {
            $cart->load(['items.catalog.category', 'items.catalog.discount', 'items.catalog.images', 'items.catalog.specifications', 'items.catalog.inputFields']);

            abort_if($cart->items->isEmpty(), 422, 'Keranjang kosong.');
            abort_if($cart->items->contains(fn ($item) => ! $item->catalog->is_active), 422, 'Ada katalog yang sudah tidak aktif.');

            $subtotal = 0;
            $discountTotal = 0;

            foreach ($cart->items as $item) {
                $subtotal += $item->catalog->base_price * $item->quantity;
                $discountTotal += $this->pricing->discountAmount($item->catalog) * $item->quantity;
            }

            $total = max(0, $subtotal - $discountTotal);
            $deposit = (int) ceil($total * 0.5);
            $paymentType = $data['payment_scheme'] === Payment::TYPE_DEPOSIT ? Payment::TYPE_DEPOSIT : Payment::TYPE_FULL;
            $paymentAmount = $paymentType === Payment::TYPE_DEPOSIT ? $deposit : $total;

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'customer_name' => $data['name'],
                'customer_email' => $data['email'],
                'customer_phone' => $data['phone'],
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'total' => $total,
                'deposit_amount' => $deposit,
                'payment_status' => Order::PAYMENT_PENDING,
                'work_status' => Order::WORK_RECEIVED,
                'preview_token' => Str::random(40),
            ]);

            foreach ($cart->items as $item) {
                $catalog = $item->catalog;
                $itemDiscount = $this->pricing->discountAmount($catalog);
                $orderItem = $order->items()->create([
                    'catalog_id' => $catalog->id,
                    'catalog_name' => $catalog->name,
                    'catalog_slug' => $catalog->slug,
                    'category_name' => $catalog->category->name,
                    'unit_price' => $catalog->base_price,
                    'discount_amount' => $itemDiscount,
                    'quantity' => $item->quantity,
                    'line_total' => ($catalog->base_price - $itemDiscount) * $item->quantity,
                    'snapshot' => [
                        'description' => $catalog->description,
                        'estimated_days' => $catalog->estimated_days,
                        'specifications' => $catalog->specifications->map->only(['label', 'value'])->values(),
                    ],
                ]);

                foreach ($catalog->inputFields as $field) {
                    $value = $data['fields'][$field->id] ?? null;
                    $order->inputValues()->create([
                        'order_item_id' => $orderItem->id,
                        'catalog_input_field_id' => $field->id,
                        'label' => $field->label,
                        'type' => $field->type,
                        'value' => is_array($value) ? json_encode($value) : $value,
                    ]);
                }
            }

            $payment = $order->payments()->create([
                'payment_number' => 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'midtrans_order_id' => 'MID-'.$order->order_number.'-'.Str::upper(Str::random(4)),
                'type' => $paymentType,
                'status' => Payment::STATUS_PENDING,
                'amount' => $paymentAmount,
                'expires_at' => now()->addDay(),
            ]);

            $snap = $this->midtrans->createSnapTransaction($order, $payment);
            $payment->update($snap);
            $order->activities()->create(['user_id' => $user->id, 'type' => 'order_created', 'message' => 'Pesanan dibuat dan menunggu pembayaran.']);

            $cart->items()->delete();

            return [$order->fresh(['items', 'payments']), $payment->fresh()];
        });
    }
}
