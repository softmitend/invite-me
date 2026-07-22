<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\CatalogPricingService;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_are_protected_by_admin_role(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->get(route('orders.show', $order))->assertForbidden();
    }

    public function test_subtotal_discount_deposit_and_remaining_are_calculated_server_side(): void
    {
        $category = Category::factory()->create();
        $discount = Discount::create(['name' => 'Ten', 'type' => Discount::TYPE_PERCENT, 'value' => 10, 'is_active' => true]);
        $catalog = Catalog::factory()->create(['category_id' => $category->id, 'discount_id' => $discount->id, 'base_price' => 200000]);

        $pricing = app(CatalogPricingService::class);

        $this->assertSame(20000, $pricing->discountAmount($catalog->load('discount')));
        $this->assertSame(180000, $pricing->finalPrice($catalog));
        $this->assertSame(90000, (int) ceil($pricing->finalPrice($catalog) * .5));
    }

    public function test_checkout_creates_order_snapshot_and_payment(): void
    {
        $user = User::factory()->create();
        $catalog = $this->activeCatalog();

        $this->actingAs($user)->post(route('cart.store', $catalog))->assertRedirect();
        $this->actingAs($user)->post(route('checkout.store'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'payment_scheme' => Payment::TYPE_DEPOSIT,
            'terms' => '1',
            'fields' => [],
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'payment_status' => Order::PAYMENT_PENDING]);
        $this->assertDatabaseHas('order_items', ['catalog_name' => $catalog->name, 'unit_price' => $catalog->base_price]);
        $this->assertDatabaseHas('payments', ['type' => Payment::TYPE_DEPOSIT, 'status' => Payment::STATUS_PENDING]);
    }

    public function test_non_active_catalog_cannot_be_ordered(): void
    {
        $catalog = $this->activeCatalog(['is_active' => false]);

        $this->post(route('cart.store', $catalog))->assertStatus(422);
    }

    public function test_progress_percentage_uses_completed_checklist(): void
    {
        $order = Order::factory()->create();
        $order->progressSteps()->createMany([
            ['label' => 'A', 'sort_order' => 1, 'is_completed' => true],
            ['label' => 'B', 'sort_order' => 2, 'is_completed' => false],
            ['label' => 'C', 'sort_order' => 3, 'is_completed' => true],
            ['label' => 'D', 'sort_order' => 4, 'is_completed' => false],
        ]);

        $this->assertSame(50, app(ProgressService::class)->percent($order->load('progressSteps')));
    }

    public function test_midtrans_webhook_marks_payment_paid_and_is_idempotent(): void
    {
        $order = Order::factory()->create(['total' => 180000, 'paid_amount' => 0]);
        $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 180000, 'midtrans_order_id' => 'MID-TEST-1']);
        $payload = $this->midtransPayload($payment, 'settlement');

        $this->postJson(route('payments.midtrans.webhook'), $payload)->assertOk();
        $this->postJson(route('payments.midtrans.webhook'), $payload)->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => Payment::STATUS_PAID]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => Order::PAYMENT_PAID, 'paid_amount' => 180000]);
    }

    public function test_midtrans_webhook_rejects_invalid_signature(): void
    {
        $payment = Payment::factory()->create(['amount' => 180000, 'midtrans_order_id' => 'MID-TEST-2']);
        $payload = $this->midtransPayload($payment, 'settlement');
        $payload['signature_key'] = 'bad';

        $this->postJson(route('payments.midtrans.webhook'), $payload)->assertForbidden();
    }

    public function test_midtrans_expired_payment_updates_payment_status(): void
    {
        $payment = Payment::factory()->create(['amount' => 180000, 'midtrans_order_id' => 'MID-TEST-3']);

        $this->postJson(route('payments.midtrans.webhook'), $this->midtransPayload($payment, 'expire'))->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => Payment::STATUS_EXPIRED]);
    }

    public function test_review_requires_completed_order_that_contains_catalog(): void
    {
        $user = User::factory()->create();
        $catalog = $this->activeCatalog();
        $order = Order::factory()->create(['user_id' => $user->id, 'work_status' => Order::WORK_COMPLETED]);
        $order->items()->create([
            'catalog_id' => $catalog->id,
            'catalog_name' => $catalog->name,
            'catalog_slug' => $catalog->slug,
            'category_name' => $catalog->category->name,
            'unit_price' => $catalog->base_price,
            'quantity' => 1,
            'line_total' => $catalog->base_price,
        ]);

        $this->actingAs($user)->post(route('orders.reviews.store', [$order, $catalog]), [
            'rating' => 5,
            'comment' => 'Sangat membantu dan hasilnya rapi.',
        ])->assertRedirect();

        $this->assertDatabaseHas('reviews', ['user_id' => $user->id, 'catalog_id' => $catalog->id, 'rating' => 5]);
    }

    private function activeCatalog(array $overrides = []): Catalog
    {
        $category = Category::factory()->create();
        $catalog = Catalog::factory()->create(array_merge(['category_id' => $category->id, 'base_price' => 180000, 'is_active' => true], $overrides));
        $catalog->images()->create(['path' => 'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=900&q=80', 'sort_order' => 1, 'is_primary' => true]);

        return $catalog->load(['category', 'images']);
    }

    private function midtransPayload(Payment $payment, string $status): array
    {
        $gross = number_format($payment->amount, 2, '.', '');
        $statusCode = '200';
        $signature = hash('sha512', $payment->midtrans_order_id.$statusCode.$gross.config('services.midtrans.server_key', 'sandbox-server-key'));

        return [
            'order_id' => $payment->midtrans_order_id,
            'status_code' => $statusCode,
            'gross_amount' => $gross,
            'signature_key' => $signature,
            'transaction_status' => $status,
            'payment_type' => 'qris',
            'transaction_id' => 'trx-'.uniqid(),
        ];
    }
}
