<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_non_admin_cannot_open_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_dashboard_renders_with_empty_state(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Belum ada pesanan yang membutuhkan tindakan')
            ->assertSee('Belum ada transaksi katalog');
    }

    public function test_dashboard_summary_finance_period_and_priority_are_calculated_from_real_data(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Wedding']);
        $catalog = Catalog::factory()->create(['category_id' => $category->id, 'name' => 'Starlit Couple']);

        $overdue = $this->orderWithItem([
            'user_id' => $admin->id,
            'work_status' => Order::WORK_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_PARTIALLY_PAID,
            'total' => 1000000,
            'paid_amount' => 500000,
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
        ], $catalog, ['estimated_days' => 2]);

        $revision = $this->orderWithItem([
            'user_id' => $admin->id,
            'work_status' => Order::WORK_REVISION,
            'payment_status' => Order::PAYMENT_PENDING,
            'created_at' => now()->subDay(),
        ], $catalog);

        $completed = $this->orderWithItem([
            'user_id' => $admin->id,
            'work_status' => Order::WORK_COMPLETED,
            'payment_status' => Order::PAYMENT_PAID,
            'total' => 800000,
            'paid_amount' => 800000,
            'final_url' => 'https://example.com/final',
            'created_at' => now(),
        ], $catalog);

        $cancelled = $this->orderWithItem([
            'user_id' => $admin->id,
            'work_status' => Order::WORK_CANCELLED,
            'payment_status' => Order::PAYMENT_PENDING,
            'created_at' => now(),
        ], $catalog);

        Payment::factory()->create(['order_id' => $overdue->id, 'type' => Payment::TYPE_DEPOSIT, 'status' => Payment::STATUS_PAID, 'amount' => 500000, 'paid_at' => now()]);
        Payment::factory()->create(['order_id' => $completed->id, 'type' => Payment::TYPE_FINAL, 'status' => Payment::STATUS_PAID, 'amount' => 800000, 'paid_at' => now()]);
        Payment::factory()->create(['order_id' => $revision->id, 'type' => Payment::TYPE_DEPOSIT, 'status' => Payment::STATUS_PENDING, 'amount' => 400000]);
        Payment::factory()->create(['order_id' => $cancelled->id, 'type' => Payment::TYPE_FULL, 'status' => Payment::STATUS_PENDING, 'amount' => 900000]);

        $data = app(AdminDashboardService::class)->data(Request::create(route('admin.dashboard'), 'GET', ['period' => '30_days']));

        $cards = collect($data['summary_cards'])->keyBy('key');
        $this->assertSame(1, $cards['in_progress']['value']);
        $this->assertSame(1, $cards['revision_requested']['value']);
        $this->assertSame(1, $cards['waiting_final_payment']['value']);
        $this->assertSame(1, $cards['overdue_orders']['value']);
        $this->assertSame(1, $cards['completed_orders']['value']);
        $this->assertSame(1, $cards['published_websites']['value']);

        $this->assertSame(1300000, $data['financial_summary']['total_received']);
        $this->assertSame(500000, $data['financial_summary']['dp_received']);
        $this->assertSame(800000, $data['financial_summary']['final_payment_received']);
        $this->assertSame(500000, $data['financial_summary']['outstanding_balance']);
        $this->assertSame(2, $data['financial_summary']['pending_transactions']);

        $this->assertSame($overdue->order_number, $data['action_required_orders']->first()['order']->order_number);
        $this->assertStringContainsString('action=overdue', $cards['overdue_orders']['url']);
        $this->assertStringContainsString('pipeline=awaiting_dp', collect($data['pipeline'])->firstWhere('key', 'awaiting_dp')['url']);
    }

    public function test_dashboard_period_filter_limits_visible_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $catalog = Catalog::factory()->create();

        $this->orderWithItem(['work_status' => Order::WORK_RECEIVED, 'created_at' => now(), 'user_id' => $admin->id], $catalog);
        $this->orderWithItem(['work_status' => Order::WORK_RECEIVED, 'created_at' => now()->subMonths(3), 'user_id' => $admin->id], $catalog);

        $data = app(AdminDashboardService::class)->data(Request::create(route('admin.dashboard'), 'GET', ['period' => 'today']));
        $cards = collect($data['summary_cards'])->keyBy('key');

        $this->assertSame(1, $cards['new_orders']['value']);
    }

    private function orderWithItem(array $orderOverrides, Catalog $catalog, array $snapshot = []): Order
    {
        $order = Order::factory()->create($orderOverrides);
        $order->items()->create([
            'catalog_id' => $catalog->id,
            'catalog_name' => $catalog->name,
            'catalog_slug' => $catalog->slug,
            'category_name' => $catalog->category->name,
            'unit_price' => $catalog->base_price,
            'discount_amount' => 0,
            'quantity' => 1,
            'line_total' => $order->total,
            'snapshot' => array_merge(['estimated_days' => 3], $snapshot),
        ]);

        return $order->fresh(['items']);
    }
}
