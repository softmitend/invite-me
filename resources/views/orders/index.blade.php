@extends('layouts.store', ['title' => 'Pesanan Saya'])

@php($price = app(\App\Services\CatalogPricingService::class))

@section('content')
<section class="mx-auto max-w-6xl px-4 py-10">
    <h1 class="display-serif text-6xl font-black">Pesanan Saya</h1>
    <div class="mt-6 grid gap-4">
        @forelse($orders as $order)
            <article class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-black">{{ $order->order_number }}</p>
                        <p class="text-sm">{{ $order->created_at->format('d M Y') }} • {{ $order->items->pluck('catalog_name')->join(', ') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-black">{{ $price->format($order->total) }}</p>
                        <p class="text-xs font-bold uppercase">{{ $order->payment_status }} • {{ $order->work_status }}</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a class="btn-soft" href="{{ route('orders.show', $order) }}">Detail</a>
                    <a class="btn-soft bg-[#b7dfe6]" href="{{ route('orders.invoice', $order) }}">Invoice</a>
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-[#211f1f] bg-[#b7dfe6] p-8">Belum ada pesanan.</div>
        @endforelse
    </div>
    <div class="mt-8">{{ $orders->links() }}</div>
</section>
@endsection
