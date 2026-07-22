<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_number' => 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'midtrans_order_id' => 'MID-'.Str::upper(Str::random(12)),
            'type' => Payment::TYPE_FULL,
            'status' => Payment::STATUS_PENDING,
            'amount' => fake()->numberBetween(125000, 950000),
            'expires_at' => now()->addDay(),
        ];
    }
}
