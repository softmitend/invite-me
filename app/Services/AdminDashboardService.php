<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\Order;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function __construct(private readonly CatalogPricingService $pricing)
    {
    }

    public function data(Request $request): array
    {
        $period = $this->period($request);
        $previous = $this->previousPeriod($period);
        $orders = $this->ordersForPeriod($period)->get();
        $previousOrders = $this->ordersForPeriod($previous)->get();

        $summary = $this->summary($orders);
        $previousSummary = $this->summary($previousOrders);
        $financialSummary = $this->financialSummary($period);

        return [
            'period' => $period,
            'period_options' => $this->periodOptions(),
            'summary_cards' => $this->summaryCards($summary, $previousSummary),
            'financial_summary' => $financialSummary,
            'action_required_orders' => $this->actionRequiredOrders($orders),
            'pipeline' => $this->pipeline(),
            'chart' => $this->chart($period, $request->string('metric', 'orders')->toString()),
            'chart_metric_options' => $this->chartMetricOptions(),
            'recent_orders' => $this->recentOrders($period),
            'top_catalogs' => $this->topCatalogs($period),
            'alerts' => $this->alerts($orders, $financialSummary),
            'updated_at' => now(),
        ];
    }

    public function period(Request $request): array
    {
        $key = $request->string('period', '30_days')->toString();
        $now = CarbonImmutable::now();

        [$start, $end] = match ($key) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            '7_days' => [$now->subDays(6)->startOfDay(), $now->endOfDay()],
            'month' => [$now->startOfMonth(), $now->endOfDay()],
            'year' => [$now->startOfYear(), $now->endOfDay()],
            'custom' => [
                CarbonImmutable::parse($request->input('start_date', $now->subDays(29)->toDateString()))->startOfDay(),
                CarbonImmutable::parse($request->input('end_date', $now->toDateString()))->endOfDay(),
            ],
            default => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
        };

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return [
            'key' => $key,
            'start' => $start,
            'end' => $end,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'label' => $this->periodLabel($key, $start, $end),
        ];
    }

    public function dashboardUrl(array $filters = []): string
    {
        return route('admin.dashboard', $filters);
    }

    private function ordersForPeriod(array $period): Builder
    {
        return Order::query()
            ->with(['items.catalog.images', 'progressSteps', 'payments', 'revisions'])
            ->whereBetween('created_at', [$period['start'], $period['end']]);
    }

    private function previousPeriod(array $period): array
    {
        $days = $period['start']->diffInDays($period['end']) + 1;
        $end = $period['start']->subSecond();
        $start = $end->subDays($days - 1)->startOfDay();

        return [
            'key' => 'previous',
            'start' => $start,
            'end' => $end,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'label' => 'Periode sebelumnya',
        ];
    }

    private function summary(Collection $orders): array
    {
        return [
            'new_orders' => $orders->where('work_status', Order::WORK_RECEIVED)->count(),
            'active_orders' => $orders
                ->whereNotIn('work_status', [Order::WORK_COMPLETED, Order::WORK_CANCELLED])
                ->count(),
            'in_progress' => $orders->where('work_status', Order::WORK_IN_PROGRESS)->count(),
            'waiting_review' => $orders->where('work_status', Order::WORK_PREVIEW)->count(),
            'revision_requested' => $orders->where('work_status', Order::WORK_REVISION)->count(),
            'waiting_final_payment' => $orders
                ->where('payment_status', Order::PAYMENT_PARTIALLY_PAID)
                ->whereNotIn('work_status', [Order::WORK_CANCELLED, Order::WORK_COMPLETED])
                ->count(),
            'overdue_orders' => $orders->filter(fn (Order $order) => $this->isOverdue($order))->count(),
            'completed_orders' => $orders->where('work_status', Order::WORK_COMPLETED)->count(),
            'published_websites' => $orders->filter(fn (Order $order) => $this->publicationStatus($order) === 'published')->count(),
        ];
    }

    private function summaryCards(array $summary, array $previousSummary): array
    {
        return collect([
            ['key' => 'new_orders', 'title' => 'Pesanan Baru', 'icon' => 'PB', 'tone' => 'neutral', 'description' => 'Order baru yang perlu ditinjau.', 'filters' => ['work_status' => Order::WORK_RECEIVED]],
            ['key' => 'active_orders', 'title' => 'Pesanan Aktif', 'icon' => 'AK', 'tone' => 'blue', 'description' => 'Semua pesanan yang belum selesai.', 'filters' => ['dashboard_group' => 'active']],
            ['key' => 'waiting_review', 'title' => 'Menunggu Client', 'icon' => 'RV', 'tone' => 'yellow', 'description' => 'Preview menunggu review client.', 'filters' => ['work_status' => Order::WORK_PREVIEW]],
            ['key' => 'revision_requested', 'title' => 'Revisi Masuk', 'icon' => 'RS', 'tone' => 'orange', 'description' => 'Catatan revisi yang perlu ditindaklanjuti.', 'filters' => ['work_status' => Order::WORK_REVISION]],
            ['key' => 'waiting_final_payment', 'title' => 'Belum Lunas', 'icon' => 'PL', 'tone' => 'yellow', 'description' => 'DP sudah masuk, sisa tagihan belum lunas.', 'filters' => ['payment_status' => Order::PAYMENT_PARTIALLY_PAID]],
            ['key' => 'overdue_orders', 'title' => 'Terlambat', 'icon' => 'TL', 'tone' => 'red', 'description' => 'Pesanan melewati estimasi pengerjaan.', 'filters' => ['action' => 'overdue']],
        ])->map(function (array $card) use ($summary, $previousSummary) {
            $value = $summary[$card['key']] ?? 0;
            $previous = $previousSummary[$card['key']] ?? 0;

            return array_merge($card, [
                'value' => $value,
                'delta' => $value - $previous,
                'url' => route('admin.orders.index', $card['filters']),
            ]);
        })->all();
    }

    private function financialSummary(array $period): array
    {
        $received = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->whereBetween('paid_at', [$period['start'], $period['end']]);

        $totalReceived = (clone $received)->sum('amount');
        $dpReceived = (clone $received)->where('type', Payment::TYPE_DEPOSIT)->sum('amount');
        $finalPaymentReceived = (clone $received)->whereIn('type', [Payment::TYPE_FINAL, Payment::TYPE_FULL])->sum('amount');
        $pendingTransactions = Payment::where('status', Payment::STATUS_PENDING)
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->count();
        $failedOrExpiredTransactions = Payment::whereIn('status', [Payment::STATUS_FAILED, Payment::STATUS_EXPIRED])
            ->whereBetween('updated_at', [$period['start'], $period['end']])
            ->count();
        $outstandingBalance = Order::where('payment_status', Order::PAYMENT_PARTIALLY_PAID)
            ->whereNotIn('work_status', [Order::WORK_CANCELLED])
            ->selectRaw('COALESCE(SUM(GREATEST(total - paid_amount, 0)), 0) as total')
            ->value('total');

        return [
            'total_received' => (int) $totalReceived,
            'dp_received' => (int) $dpReceived,
            'final_payment_received' => (int) $finalPaymentReceived,
            'outstanding_balance' => (int) $outstandingBalance,
            'pending_transactions' => $pendingTransactions,
            'failed_or_expired_transactions' => $failedOrExpiredTransactions,
        ];
    }

    private function actionRequiredOrders(Collection $orders): Collection
    {
        return $orders
            ->reject(fn (Order $order) => in_array($order->work_status, [Order::WORK_COMPLETED, Order::WORK_CANCELLED], true))
            ->map(function (Order $order) {
                $action = $this->nextAction($order);

                return [
                    'order' => $order,
                    'catalogs' => $order->items->pluck('catalog_name')->join(', '),
                    'order_type' => $this->orderType($order),
                    'work_label' => $this->workLabel($order->work_status),
                    'payment_label' => $this->paymentLabel($order->payment_status),
                    'deadline' => $this->deadline($order),
                    'deadline_label' => $this->deadlineLabel($order),
                    'next_action' => $action['label'],
                    'priority' => $action['priority'],
                    'priority_label' => $action['priority_label'],
                    'url' => route('admin.orders.show', $order),
                ];
            })
            ->sortBy([
                ['priority', 'asc'],
                ['deadline', 'asc'],
            ])
            ->take(10)
            ->values();
    }

    private function pipeline(): array
    {
        $counts = [
            'awaiting_dp' => Order::where('work_status', Order::WORK_RECEIVED)->whereIn('payment_status', [Order::PAYMENT_UNPAID, Order::PAYMENT_PENDING])->count(),
            'queued' => Order::where('work_status', Order::WORK_RECEIVED)->whereIn('payment_status', [Order::PAYMENT_PARTIALLY_PAID, Order::PAYMENT_PAID])->count(),
            'in_progress' => Order::where('work_status', Order::WORK_IN_PROGRESS)->count(),
            'preview_ready' => Order::where('work_status', Order::WORK_PREVIEW)->count(),
            'revision_requested' => Order::where('work_status', Order::WORK_REVISION)->count(),
            'revision_in_progress' => Order::where('work_status', Order::WORK_REVISION)->whereHas('progressSteps', fn ($query) => $query->where('is_completed', false))->count(),
            'approved' => Order::whereNotNull('preview_approved_at')->where('work_status', '!=', Order::WORK_COMPLETED)->count(),
            'completed' => Order::where('work_status', Order::WORK_COMPLETED)->count(),
        ];

        return collect([
            ['key' => 'awaiting_dp', 'label' => 'Pesanan Baru', 'hint' => 'Menunggu DP pertama', 'filters' => ['pipeline' => 'awaiting_dp']],
            ['key' => 'queued', 'label' => 'DP Dibayar', 'hint' => 'Siap masuk antrean', 'filters' => ['pipeline' => 'queued']],
            ['key' => 'in_progress', 'label' => 'Sedang Dikerjakan', 'hint' => 'Proses pembuatan website', 'filters' => ['work_status' => Order::WORK_IN_PROGRESS]],
            ['key' => 'preview_ready', 'label' => 'Preview', 'hint' => 'Menunggu review client', 'filters' => ['work_status' => Order::WORK_PREVIEW]],
            ['key' => 'revision_requested', 'label' => 'Revisi', 'hint' => 'Ada revisi masuk', 'filters' => ['work_status' => Order::WORK_REVISION]],
            ['key' => 'revision_in_progress', 'label' => 'Revisi Diproses', 'hint' => 'Checklist revisi berjalan', 'filters' => ['pipeline' => 'revision_in_progress']],
            ['key' => 'approved', 'label' => 'Disetujui', 'hint' => 'Preview disetujui, cek pelunasan', 'filters' => ['pipeline' => 'approved']],
            ['key' => 'completed', 'label' => 'Dipublikasikan', 'hint' => 'Pekerjaan selesai', 'filters' => ['work_status' => Order::WORK_COMPLETED]],
        ])->map(fn (array $stage) => array_merge($stage, [
            'count' => $counts[$stage['key']],
            'url' => route('admin.orders.index', $stage['filters']),
        ]))->all();
    }

    private function chart(array $period, string $metric): array
    {
        $metric = array_key_exists($metric, $this->chartMetricOptions()) ? $metric : 'orders';
        $buckets = $this->chartBuckets($period);

        $values = $buckets->map(function (array $bucket) use ($metric) {
            $value = match ($metric) {
                'revenue' => Payment::where('status', Payment::STATUS_PAID)->whereBetween('paid_at', [$bucket['start'], $bucket['end']])->sum('amount'),
                'dp' => Payment::where('status', Payment::STATUS_PAID)->where('type', Payment::TYPE_DEPOSIT)->whereBetween('paid_at', [$bucket['start'], $bucket['end']])->sum('amount'),
                'final' => Payment::where('status', Payment::STATUS_PAID)->whereIn('type', [Payment::TYPE_FINAL, Payment::TYPE_FULL])->whereBetween('paid_at', [$bucket['start'], $bucket['end']])->sum('amount'),
                default => Order::where('work_status', '!=', Order::WORK_CANCELLED)->whereBetween('created_at', [$bucket['start'], $bucket['end']])->count(),
            };

            return [
                'label' => $bucket['label'],
                'value' => (int) $value,
                'formatted' => $metric === 'orders' ? (string) $value : $this->pricing->format((int) $value),
            ];
        });

        $max = max(1, $values->max('value') ?: 0);

        return [
            'metric' => $metric,
            'label' => $this->chartMetricOptions()[$metric],
            'is_money' => $metric !== 'orders',
            'max' => $max,
            'points' => $values,
            'has_data' => $values->sum('value') > 0,
        ];
    }

    private function recentOrders(array $period): Collection
    {
        return Order::with(['items'])
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->latest()
            ->take(8)
            ->get()
            ->map(fn (Order $order) => [
                'order' => $order,
                'catalogs' => $order->items->pluck('catalog_name')->join(', '),
                'order_type' => $this->orderType($order),
                'work_label' => $this->workLabel($order->work_status),
                'payment_label' => $this->paymentLabel($order->payment_status),
                'url' => route('admin.orders.show', $order),
            ]);
    }

    private function topCatalogs(array $period): Collection
    {
        $totalOrders = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$period['start'], $period['end']])
            ->where('orders.work_status', '!=', Order::WORK_CANCELLED)
            ->sum('order_items.quantity');

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('catalogs', 'catalogs.id', '=', 'order_items.catalog_id')
            ->leftJoin('categories', 'categories.id', '=', 'catalogs.category_id')
            ->leftJoin('catalog_images', function ($join) {
                $join->on('catalog_images.catalog_id', '=', 'catalogs.id')
                    ->where('catalog_images.is_primary', true);
            })
            ->whereBetween('orders.created_at', [$period['start'], $period['end']])
            ->where('orders.work_status', '!=', Order::WORK_CANCELLED)
            ->selectRaw('order_items.catalog_id, order_items.catalog_name, order_items.category_name, COALESCE(categories.name, order_items.category_name) as category, COALESCE(catalog_images.path, MIN(catalog_images.path)) as thumbnail, SUM(order_items.quantity) as orders_count, SUM(order_items.line_total) as revenue')
            ->groupBy('order_items.catalog_id', 'order_items.catalog_name', 'order_items.category_name', 'categories.name', 'catalog_images.path')
            ->orderByDesc('orders_count')
            ->take(5)
            ->get()
            ->map(fn ($catalog) => [
                'catalog_id' => $catalog->catalog_id,
                'name' => $catalog->catalog_name,
                'category' => $catalog->category,
                'order_type' => $this->catalogType($catalog->category),
                'thumbnail' => $catalog->thumbnail,
                'orders_count' => (int) $catalog->orders_count,
                'revenue' => (int) $catalog->revenue,
                'contribution' => $totalOrders > 0 ? (int) round(($catalog->orders_count / $totalOrders) * 100) : 0,
            ]);
    }

    private function alerts(Collection $orders, array $financialSummary): Collection
    {
        $alerts = collect();
        $overdue = $orders->filter(fn (Order $order) => $this->isOverdue($order))->count();
        $revision = $orders->where('work_status', Order::WORK_REVISION)->count();
        $oldPreview = $orders
            ->where('work_status', Order::WORK_PREVIEW)
            ->filter(fn (Order $order) => $order->updated_at->lte(now()->subDays(3)))
            ->count();
        $waitingFinal = $orders->where('payment_status', Order::PAYMENT_PARTIALLY_PAID)->count();
        $catalogsWithoutThumb = Catalog::where('is_active', true)->whereDoesntHave('images')->count();

        $items = [
            ['count' => $overdue, 'priority' => 1, 'label' => 'Kritis', 'message' => 'pesanan melewati deadline', 'url' => route('admin.orders.index', ['action' => 'overdue'])],
            ['count' => $revision, 'priority' => 2, 'label' => 'Tinggi', 'message' => 'revisi belum dikerjakan', 'url' => route('admin.orders.index', ['work_status' => Order::WORK_REVISION])],
            ['count' => $oldPreview, 'priority' => 3, 'label' => 'Sedang', 'message' => 'preview belum diperiksa lebih dari tiga hari', 'url' => route('admin.orders.index', ['work_status' => Order::WORK_PREVIEW])],
            ['count' => $waitingFinal, 'priority' => 3, 'label' => 'Sedang', 'message' => 'pesanan menunggu pelunasan', 'url' => route('admin.orders.index', ['payment_status' => Order::PAYMENT_PARTIALLY_PAID])],
            ['count' => $financialSummary['failed_or_expired_transactions'], 'priority' => 3, 'label' => 'Sedang', 'message' => 'transaksi pembayaran gagal atau kedaluwarsa', 'url' => route('admin.orders.index', ['payment_status' => Order::PAYMENT_EXPIRED])],
            ['count' => $catalogsWithoutThumb, 'priority' => 4, 'label' => 'Info', 'message' => 'katalog aktif belum memiliki thumbnail', 'url' => route('admin.catalogs.index')],
        ];

        foreach ($items as $item) {
            if ($item['count'] > 0) {
                $alerts->push($item);
            }
        }

        return $alerts->sortBy('priority')->values();
    }

    private function periodOptions(): array
    {
        return [
            'today' => 'Hari ini',
            '7_days' => '7 hari terakhir',
            '30_days' => '30 hari terakhir',
            'month' => 'Bulan ini',
            'year' => 'Tahun ini',
            'custom' => 'Rentang tanggal khusus',
        ];
    }

    private function chartMetricOptions(): array
    {
        return [
            'orders' => 'Jumlah pesanan',
            'revenue' => 'Pendapatan total',
            'dp' => 'Pembayaran DP',
            'final' => 'Pembayaran pelunasan',
        ];
    }

    private function chartBuckets(array $period): Collection
    {
        $days = $period['start']->diffInDays($period['end']) + 1;
        $step = $days > 120 ? 'month' : ($days > 45 ? 'week' : 'day');
        $cursor = $period['start'];
        $buckets = collect();

        while ($cursor->lte($period['end'])) {
            $end = match ($step) {
                'month' => $cursor->endOfMonth(),
                'week' => $cursor->endOfWeek(),
                default => $cursor->endOfDay(),
            };

            if ($end->gt($period['end'])) {
                $end = $period['end'];
            }

            $buckets->push([
                'start' => $cursor,
                'end' => $end,
                'label' => match ($step) {
                    'month' => $cursor->translatedFormat('M Y'),
                    'week' => $cursor->translatedFormat('d M'),
                    default => $cursor->translatedFormat('d M'),
                },
            ]);

            $cursor = match ($step) {
                'month' => $cursor->addMonth()->startOfMonth(),
                'week' => $cursor->addWeek()->startOfWeek(),
                default => $cursor->addDay()->startOfDay(),
            };
        }

        return $buckets;
    }

    private function periodLabel(string $key, CarbonInterface $start, CarbonInterface $end): string
    {
        return match ($key) {
            'today' => 'Hari ini',
            '7_days' => '7 hari terakhir',
            'month' => 'Bulan ini',
            'year' => 'Tahun ini',
            'custom' => $start->translatedFormat('d M Y').' - '.$end->translatedFormat('d M Y'),
            default => '30 hari terakhir',
        };
    }

    private function deadline(Order $order): CarbonInterface
    {
        $days = (int) ($order->items->first()?->snapshot['estimated_days'] ?? 3);

        return $order->created_at->copy()->addDays(max(1, $days))->endOfDay();
    }

    private function isOverdue(Order $order): bool
    {
        return $this->deadline($order)->lt(now())
            && ! in_array($order->work_status, [Order::WORK_COMPLETED, Order::WORK_CANCELLED], true);
    }

    private function deadlineLabel(Order $order): string
    {
        $deadline = $this->deadline($order);

        if ($deadline->isToday()) {
            return 'Deadline hari ini';
        }

        if ($deadline->isPast()) {
            return 'Terlambat '.$deadline->diffInDays(now()).' hari';
        }

        return 'Sisa '.$deadline->diffInDays(now()).' hari';
    }

    private function nextAction(Order $order): array
    {
        if ($this->isOverdue($order)) {
            return ['label' => 'Hubungi client / percepat pengerjaan', 'priority' => 1, 'priority_label' => 'Kritis'];
        }

        if ($this->deadline($order)->isToday()) {
            return ['label' => 'Selesaikan target hari ini', 'priority' => 2, 'priority_label' => 'Tinggi'];
        }

        if ($order->work_status === Order::WORK_REVISION) {
            return ['label' => 'Periksa dan kerjakan revisi', 'priority' => 3, 'priority_label' => 'Tinggi'];
        }

        if ($order->work_status === Order::WORK_IN_PROGRESS) {
            return ['label' => 'Upload preview', 'priority' => 5, 'priority_label' => 'Sedang'];
        }

        if ($order->work_status === Order::WORK_PREVIEW) {
            return ['label' => 'Pantau review client', 'priority' => 6, 'priority_label' => 'Sedang'];
        }

        if ($order->payment_status === Order::PAYMENT_PARTIALLY_PAID) {
            return ['label' => 'Buat tagihan pelunasan', 'priority' => 7, 'priority_label' => 'Sedang'];
        }

        if (in_array($order->payment_status, [Order::PAYMENT_EXPIRED, Order::PAYMENT_FAILED], true)) {
            return ['label' => 'Periksa pembayaran', 'priority' => 8, 'priority_label' => 'Info'];
        }

        return ['label' => 'Mulai pengerjaan', 'priority' => 4, 'priority_label' => 'Sedang'];
    }

    private function orderType(Order $order): string
    {
        $text = strtolower($order->items->pluck('category_name')->join(' ').' '.$order->items->pluck('catalog_name')->join(' '));

        return str_contains($text, 'custom') ? 'Custom' : 'Template';
    }

    private function catalogType(?string $category): string
    {
        return str_contains(strtolower((string) $category), 'custom') ? 'Custom' : 'Template';
    }

    private function publicationStatus(Order $order): string
    {
        if ($order->final_url && $order->work_status === Order::WORK_COMPLETED) {
            return 'published';
        }

        if ($order->preview_url) {
            return 'preview';
        }

        return 'draft';
    }

    private function workLabel(string $status): string
    {
        return [
            Order::WORK_RECEIVED => 'Antrean',
            Order::WORK_IN_PROGRESS => 'Sedang dikerjakan',
            Order::WORK_PREVIEW => 'Menunggu review',
            Order::WORK_REVISION => 'Revisi',
            Order::WORK_COMPLETED => 'Selesai',
            Order::WORK_CANCELLED => 'Dibatalkan',
        ][$status] ?? $status;
    }

    private function paymentLabel(string $status): string
    {
        return [
            Order::PAYMENT_UNPAID => 'Belum bayar',
            Order::PAYMENT_PENDING => 'Menunggu DP',
            Order::PAYMENT_PARTIALLY_PAID => 'Menunggu pelunasan',
            Order::PAYMENT_PAID => 'Lunas',
            Order::PAYMENT_EXPIRED => 'Kedaluwarsa',
            Order::PAYMENT_FAILED => 'Gagal',
            Order::PAYMENT_REFUNDED => 'Dikembalikan',
            Order::PAYMENT_PARTIALLY_REFUNDED => 'Refund sebagian',
        ][$status] ?? $status;
    }
}
