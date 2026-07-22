<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(250000, 1200000);
        $discount = fake()->numberBetween(0, 100000);
        $total = max(0, $subtotal - $discount);

        return [
            'user_id' => User::factory(),
            'order_number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => '08'.fake()->numerify('##########'),
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'total' => $total,
            'deposit_amount' => (int) ceil($total * 0.5),
            'paid_amount' => 0,
            'payment_status' => Order::PAYMENT_PENDING,
            'work_status' => Order::WORK_RECEIVED,
            'preview_token' => Str::random(40),
        ];
    }
}
