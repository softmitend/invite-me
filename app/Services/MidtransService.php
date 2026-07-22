<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransService
{
    public function createSnapTransaction(Order $order, Payment $payment): array
    {
        $serverKey = config('services.midtrans.server_key');

        if (blank($serverKey)) {
            return [
                'snap_token' => null,
                'snap_redirect_url' => null,
            ];
        }

        $order->loadMissing('items');

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->asJson()
            ->post($this->snapEndpoint(), [
                'transaction_details' => [
                    'order_id' => $payment->midtrans_order_id,
                    'gross_amount' => $payment->amount,
                ],
                'enabled_payments' => ['qris'],
                'customer_details' => [
                    'first_name' => $order->customer_name,
                    'email' => $order->customer_email,
                    'phone' => $order->customer_phone,
                ],
                'item_details' => $this->itemDetails($order, $payment),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gagal membuat transaksi Snap Midtrans: '.$response->body());
        }

        $payload = $response->json();

        return [
            'snap_token' => $payload['token'] ?? null,
            'snap_redirect_url' => $payload['redirect_url'] ?? null,
        ];
    }

    public function verifySignature(array $payload): bool
    {
        $serverKey = config('services.midtrans.server_key', env('MIDTRANS_SERVER_KEY', ''));
        $signature = $payload['signature_key'] ?? '';
        $source = ($payload['order_id'] ?? '').($payload['status_code'] ?? '').($payload['gross_amount'] ?? '').$serverKey;

        return hash_equals(hash('sha512', $source), $signature);
    }

    public function handleNotification(array $payload): Payment
    {
        abort_unless($this->verifySignature($payload), 403, 'Signature Midtrans tidak valid.');

        return DB::transaction(function () use ($payload) {
            $payment = Payment::where('midtrans_order_id', $payload['order_id'] ?? '')->lockForUpdate()->firstOrFail();
            $hash = hash('sha256', json_encode($payload));

            if ($payment->notification_hash === $hash) {
                return $payment;
            }

            abort_if((int) round((float) ($payload['gross_amount'] ?? 0)) !== $payment->amount, 422, 'Nominal pembayaran tidak cocok.');

            $payment->fill([
                'status' => $this->mapStatus($payload['transaction_status'] ?? '', $payload['fraud_status'] ?? null),
                'payment_method' => $payload['payment_type'] ?? null,
                'transaction_id' => $payload['transaction_id'] ?? null,
                'notification_payload' => $payload,
                'notification_hash' => $hash,
            ]);

            if ($payment->status === Payment::STATUS_PAID) {
                $payment->paid_at = now();
            }

            $payment->save();
            $this->syncOrderPaymentStatus($payment->order()->lockForUpdate()->first());

            return $payment->fresh();
        });
    }

    public function mapStatus(string $transactionStatus, ?string $fraudStatus = null): string
    {
        return match ($transactionStatus) {
            'settlement' => Payment::STATUS_PAID,
            'capture' => $fraudStatus === 'challenge' ? Payment::STATUS_PENDING : Payment::STATUS_PAID,
            'expire' => Payment::STATUS_EXPIRED,
            'cancel', 'deny', 'failure' => Payment::STATUS_FAILED,
            default => Payment::STATUS_PENDING,
        };
    }

    private function itemDetails(Order $order, Payment $payment): array
    {
        $details = $order->items->map(fn ($item) => [
            'id' => (string) ($item->catalog_id ?: $item->id),
            'price' => (int) round($item->line_total / max(1, $item->quantity)),
            'quantity' => $item->quantity,
            'name' => mb_substr($item->catalog_name, 0, 50),
        ])->values()->all();

        $sum = collect($details)->sum(fn ($item) => $item['price'] * $item['quantity']);

        if ($sum !== $payment->amount) {
            $details = [[
                'id' => $payment->payment_number,
                'price' => $payment->amount,
                'quantity' => 1,
                'name' => mb_substr('Pembayaran '.$order->order_number, 0, 50),
            ]];
        }

        return $details;
    }

    private function syncOrderPaymentStatus(Order $order): void
    {
        $paid = $order->payments()->where('status', Payment::STATUS_PAID)->sum('amount');
        $order->paid_amount = $paid;

        if ($paid >= $order->total) {
            $order->payment_status = Order::PAYMENT_PAID;
        } elseif ($paid > 0) {
            $order->payment_status = Order::PAYMENT_PARTIALLY_PAID;
        } elseif ($order->payments()->where('status', Payment::STATUS_FAILED)->exists()) {
            $order->payment_status = Order::PAYMENT_FAILED;
        } elseif ($order->payments()->where('status', Payment::STATUS_EXPIRED)->exists()) {
            $order->payment_status = Order::PAYMENT_EXPIRED;
        } else {
            $order->payment_status = Order::PAYMENT_PENDING;
        }

        $order->save();
    }

    private function snapEndpoint(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }
}
