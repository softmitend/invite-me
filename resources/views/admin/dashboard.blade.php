@extends('layouts.admin', ['heading' => 'Dashboard'])

@php
    $price = app(\App\Services\CatalogPricingService::class);
    $chartMax = max(1, $chart['max']);
    $chartValues = $chart['points']->values();
    $chartWidth = max(520, $chartValues->count() * 58);
    $barWidth = 24;
@endphp

@section('content')
<div class="admin-dashboard space-y-5">
    <section class="dash-card dashboard-header p-4">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(420px,520px)]">
            <div>
                <div class="flex flex-wrap items-center gap-2 text-sm text-[#6b7280]">
                    <a class="font-bold text-[#0d6efd]" href="{{ route('admin.dashboard') }}">Admin</a>
                    <span>/</span>
                    <span>Dashboard</span>
                </div>
                <h1 class="mt-2 text-2xl font-extrabold">Dashboard</h1>
                <p class="mt-1 text-sm text-[#6b7280]">Selamat datang, {{ auth()->user()->name }}. Ini pusat kendali pesanan undangan, pembayaran, revisi, dan publikasi.</p>
                <p class="mt-2 text-xs font-bold uppercase text-[#9ca3af]">Diperbarui {{ $updated_at->translatedFormat('d M Y H:i') }}</p>
            </div>

            <div class="dashboard-controls">
                <form class="dashboard-filter" method="GET" action="{{ route('admin.dashboard') }}">
                    <select class="field" name="period">
                        @foreach($period_options as $value => $label)
                            <option value="{{ $value }}" @selected($period['key'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input class="field" type="date" name="start_date" value="{{ request('start_date', $period['start_date']) }}" aria-label="Tanggal mulai">
                    <input class="field" type="date" name="end_date" value="{{ request('end_date', $period['end_date']) }}" aria-label="Tanggal akhir">
                    <input type="hidden" name="metric" value="{{ $chart['metric'] }}">
                    <button class="btn-ink">Terapkan</button>
                </form>

                <div class="quick-actions">
                    <a class="btn-soft" href="{{ route('admin.dashboard', request()->query()) }}">Refresh</a>
                    <a class="btn-soft" href="{{ route('admin.catalogs.index') }}">Tambah katalog</a>
                    <a class="btn-soft" href="{{ route('admin.categories.index') }}">Tambah kategori</a>
                    <a class="btn-soft" href="{{ route('catalog.index') }}">Buat pesanan manual</a>
                    <a class="btn-ink" href="{{ route('admin.orders.index') }}">Lihat semua pesanan</a>
                </div>
            </div>
        </div>
    </section>

    <div class="summary-grid">
        @foreach($summary_cards as $card)
            <a class="summary-card summary-{{ $card['tone'] }}" href="{{ $card['url'] }}" title="{{ $card['description'] }}">
                <div class="flex items-start justify-between gap-3">
                    <span class="summary-icon">{{ $card['icon'] }}</span>
                    <span class="summary-delta {{ $card['delta'] >= 0 ? 'is-up' : 'is-down' }}">
                        {{ $card['delta'] >= 0 ? '+' : '' }}{{ $card['delta'] }}
                    </span>
                </div>
                <p class="mt-3 text-sm font-bold text-[#6b7280]">{{ $card['title'] }}</p>
                <p class="mt-1 text-3xl font-extrabold">{{ $card['value'] }}</p>
                <p class="summary-description">{{ $card['description'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <section class="dash-card p-5">
            <div class="section-heading px-0 pt-0">
                <div>
                    <p class="section-kicker">Grafik</p>
                    <h2 class="section-title">{{ $chart['label'] }}</h2>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($chart_metric_options as $metric => $label)
                        <a class="metric-tab {{ $chart['metric'] === $metric ? 'is-active' : '' }}" href="{{ route('admin.dashboard', array_merge(request()->except('metric'), ['metric' => $metric])) }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            @if($chart['has_data'])
                <div class="chart-scroll">
                    <svg viewBox="0 0 {{ $chartWidth }} 230" class="dashboard-chart" role="img" aria-label="Grafik {{ $chart['label'] }}">
                        @foreach([40, 80, 120, 160, 200] as $y)
                            <line x1="20" y1="{{ $y }}" x2="{{ $chartWidth - 20 }}" y2="{{ $y }}" stroke="#edf2f7" stroke-width="1" />
                        @endforeach
                        @foreach($chartValues as $index => $point)
                            @php
                                $x = 34 + ($index * 58);
                                $height = max(6, (int) round(($point['value'] / $chartMax) * 150));
                                $y = 200 - $height;
                            @endphp
                            <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $height }}" rx="6" fill="#0d6efd">
                                <title>{{ $point['label'] }}: {{ $point['formatted'] }}</title>
                            </rect>
                            <text x="{{ $x + 12 }}" y="220" text-anchor="middle" fill="#6b7280" font-size="10" font-weight="700">{{ $point['label'] }}</text>
                        @endforeach
                    </svg>
                </div>
            @else
                <div class="empty-state">Belum ada data untuk grafik pada periode {{ $period['label'] }}.</div>
            @endif
        </section>

        <section class="dash-card p-5">
            <p class="section-kicker">Keuangan</p>
            <h2 class="section-title">Ringkasan Pembayaran</h2>
            <div class="mt-4 grid gap-3">
                <div class="finance-row"><span>Total pembayaran masuk</span><strong>{{ $price->format($financial_summary['total_received']) }}</strong></div>
                <div class="finance-row"><span>Total DP diterima</span><strong>{{ $price->format($financial_summary['dp_received']) }}</strong></div>
                <div class="finance-row"><span>Total pelunasan diterima</span><strong>{{ $price->format($financial_summary['final_payment_received']) }}</strong></div>
                <div class="finance-row"><span>Sisa tagihan aktif</span><strong>{{ $price->format($financial_summary['outstanding_balance']) }}</strong></div>
                <div class="finance-row"><span>Transaksi pending</span><strong>{{ $financial_summary['pending_transactions'] }}</strong></div>
                <div class="finance-row"><span>Gagal / kedaluwarsa</span><strong>{{ $financial_summary['failed_or_expired_transactions'] }}</strong></div>
            </div>
        </section>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <section class="dash-card overflow-hidden">
            <div class="section-heading">
                <div>
                    <p class="section-kicker">Order</p>
                    <h2 class="section-title">Pesanan Terbaru</h2>
                </div>
                <a class="text-sm font-bold text-[#0d6efd]" href="{{ route('admin.orders.index') }}">Lihat semua</a>
            </div>

            <div class="overflow-x-auto">
                <table class="dashboard-table w-full min-w-[980px] text-left text-sm">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Client</th>
                            <th>Katalog</th>
                            <th>Jenis</th>
                            <th class="text-right">Total Harga</th>
                            <th>Status Web</th>
                            <th>Pembayaran</th>
                            <th>Tanggal</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent_orders as $row)
                            <tr>
                                <td><strong>{{ $row['order']->order_number }}</strong></td>
                                <td>{{ $row['order']->customer_name }}</td>
                                <td>{{ $row['catalogs'] ?: '-' }}</td>
                                <td><span class="badge-soft badge-neutral">{{ $row['order_type'] }}</span></td>
                                <td class="text-right font-bold">{{ $price->format($row['order']->total) }}</td>
                                <td><span class="badge-soft badge-blue">{{ $row['work_label'] }}</span></td>
                                <td><span class="badge-soft badge-yellow">{{ $row['payment_label'] }}</span></td>
                                <td>{{ $row['order']->created_at->translatedFormat('d M Y') }}</td>
                                <td class="text-right"><a class="btn-soft inline-flex py-2" href="{{ $row['url'] }}">Lihat</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">Belum ada pesanan terbaru pada periode {{ $period['label'] }}.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dash-card p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="section-kicker">Katalog</p>
                    <h2 class="section-title">Katalog Terlaris</h2>
                </div>
                <a class="text-sm font-bold text-[#0d6efd]" href="{{ route('admin.catalogs.index') }}">Lihat semua</a>
            </div>

            <div class="mt-4 grid gap-3">
                @forelse($top_catalogs as $catalog)
                    <div class="top-catalog">
                        @if($catalog['thumbnail'])
                            <img src="{{ $catalog['thumbnail'] }}" alt="{{ $catalog['name'] }}">
                        @else
                            <span class="top-catalog-empty">IV</span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold">{{ $catalog['name'] }}</p>
                            <p class="text-xs text-[#6b7280]">{{ $catalog['category'] }} - {{ $catalog['order_type'] }}</p>
                            <p class="text-xs text-[#6b7280]">{{ $catalog['orders_count'] }} pesanan - {{ $price->format($catalog['revenue']) }}</p>
                        </div>
                        <span class="badge-soft badge-green">{{ $catalog['contribution'] }}%</span>
                    </div>
                @empty
                    <div class="empty-state">Belum ada transaksi katalog pada periode {{ $period['label'] }}.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
