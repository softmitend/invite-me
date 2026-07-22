@extends('layouts.store', ['title' => 'Invoice '.$order->order_number])

@php($price = app(\App\Services\CatalogPricingService::class))

@section('content')
<section class="mx-auto max-w-4xl px-4 py-10 print:max-w-none">
    <div class="rounded-lg border border-[#211f1f] bg-white p-8">
        <div class="flex justify-between gap-4">
            <div><p class="display-serif text-5xl font-black">ivm.</p><p>Invoice {{ $order->order_number }}</p></div>
            <button onclick="window.print()" class="btn-soft print:hidden">Cetak / PDF</button>
        </div>
        <div class="mt-8 grid gap-3 md:grid-cols-2">
            <div><p class="font-black">Customer</p><p>{{ $order->customer_name }}<br>{{ $order->customer_email }}<br>{{ $order->customer_phone }}</p></div>
            <div class="md:text-right"><p class="font-black">Tanggal</p><p>{{ $order->created_at->format('d M Y') }}</p></div>
        </div>
        <table class="mt-8 w-full border-collapse text-left text-sm">
            <thead><tr class="border-b border-[#211f1f]"><th class="py-2">Produk</th><th>Qty</th><th class="text-right">Total</th></tr></thead>
            <tbody>@foreach($order->items as $item)<tr class="border-b border-[#211f1f]/20"><td class="py-3">{{ $item->catalog_name }}</td><td>{{ $item->quantity }}</td><td class="text-right">{{ $price->format($item->line_total) }}</td></tr>@endforeach</tbody>
        </table>
        <div class="ml-auto mt-6 max-w-sm text-sm">
            <div class="flex justify-between"><span>Subtotal</span><strong>{{ $price->format($order->subtotal) }}</strong></div>
            <div class="flex justify-between"><span>Diskon</span><strong>{{ $price->format($order->discount_total) }}</strong></div>
            <div class="flex justify-between border-t border-[#211f1f] pt-2 text-lg"><span>Total</span><strong>{{ $price->format($order->total) }}</strong></div>
            <div class="flex justify-between"><span>Terbayar</span><strong>{{ $price->format($order->paid_amount) }}</strong></div>
        </div>
        <h2 class="mt-8 font-black">Riwayat transaksi</h2>
        @foreach($order->payments as $payment)<p class="mt-2 rounded border border-[#211f1f] p-3 text-sm">{{ $payment->payment_number }} • {{ $payment->type }} • {{ $payment->status }} • {{ $price->format($payment->amount) }}</p>@endforeach
    </div>
</section>
@endsection
