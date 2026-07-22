@extends('layouts.admin', ['heading' => 'Manajemen Pesanan'])

@php
    $price = app(\App\Services\CatalogPricingService::class);
    $workLabels = [
        'received' => 'Diterima',
        'in_progress' => 'Sedang Diproses',
        'preview' => 'Preview',
        'revision' => 'Revisi',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];
@endphp

@section('content')
<div class="space-y-5">
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5">
            <p class="text-xs font-black uppercase">Total pesanan</p>
            <p class="display-serif mt-2 text-4xl font-black">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-lg border border-[#211f1f] bg-[#b7dfe6] p-5">
            <p class="text-xs font-black uppercase">Pesanan baru</p>
            <p class="display-serif mt-2 text-4xl font-black">{{ $stats['new'] }}</p>
        </div>
        <div class="rounded-lg border border-[#211f1f] bg-[#c6d99a] p-5">
            <p class="text-xs font-black uppercase">Sedang dikerjakan</p>
            <p class="display-serif mt-2 text-4xl font-black">{{ $stats['in_progress'] }}</p>
        </div>
        <div class="rounded-lg border border-[#211f1f] bg-[#f7b1c8] p-5">
            <p class="text-xs font-black uppercase">Tagihan belum lunas</p>
            <p class="display-serif mt-2 text-4xl font-black">{{ $stats['pending_payment'] }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-[#211f1f] bg-[#b7dfe6] p-4">
        <form class="flex flex-col gap-3 md:flex-row" method="GET" action="{{ route('admin.orders.index') }}">
            <input class="field" name="search" value="{{ request('search') }}" placeholder="Cari nomor pesanan, customer, WhatsApp, email, atau produk">
            <button class="btn-ink h-12 px-8">Cari</button>
            @if(request('search'))
                <a class="btn-soft h-12 px-8 text-center" href="{{ route('admin.orders.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto rounded-lg border border-[#211f1f] bg-[#fffdf5]">
        <table class="w-full min-w-[980px] border-collapse text-left text-sm">
            <thead class="bg-[#211f1f] text-[#fffdf5]">
                <tr>
                    <th class="px-4 py-3">Pesanan</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Produk</th>
                    <th class="px-4 py-3">Pembayaran</th>
                    <th class="px-4 py-3">Status Web</th>
                    <th class="px-4 py-3">Progress</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $stepTotal = $order->progressSteps->count();
                        $stepDone = $order->progressSteps->where('is_completed', true)->count();
                        $progressPercent = $stepTotal > 0 ? (int) round(($stepDone / $stepTotal) * 100) : 0;
                    @endphp
                    <tr class="border-t border-[#211f1f]">
                        <td class="px-4 py-3">
                            <p class="font-black">{{ $order->order_number }}</p>
                            <p class="text-xs">{{ $order->created_at->format('d M Y H:i') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-black">{{ $order->customer_name }}</p>
                            <p class="text-xs">{{ $order->customer_email }}</p>
                            <p class="text-xs">{{ $order->customer_phone }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $order->items->pluck('catalog_name')->join(', ') }}</td>
                        <td class="px-4 py-3">
                            <p class="font-black">{{ $price->format($order->total) }}</p>
                            <p class="text-xs">{{ $order->payment_status }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full border border-[#211f1f] bg-[#b7dfe6] px-3 py-1 text-xs font-black">{{ $workLabels[$order->work_status] ?? $order->work_status }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <progress class="progress-meter" max="100" value="{{ $progressPercent }}">{{ $progressPercent }}%</progress>
                            <p class="mt-1 text-xs font-black">{{ $progressPercent }}% - {{ $stepDone }}/{{ $stepTotal }} checklist</p>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a class="btn-soft inline-flex py-2" href="{{ route('admin.orders.show', $order) }}">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-8 text-center font-black" colspan="7">Pesanan tidak ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $orders->links() }}</div>
</div>
@endsection
