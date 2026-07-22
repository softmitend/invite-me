@extends('layouts.store', ['title' => $order->order_number])

@php($price = app(\App\Services\CatalogPricingService::class))

@section('content')
<section class="mx-auto max-w-6xl px-4 py-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-sm font-bold uppercase">Pesanan</p><h1 class="display-serif text-6xl font-black">{{ $order->order_number }}</h1></div>
        <a class="btn-soft" href="{{ route('orders.invoice', $order) }}">Cetak Invoice</a>
    </div>
    <div class="mt-6 grid gap-6 md:grid-cols-[1fr_360px]">
        <div class="grid gap-5">
            <div class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5">
                <h2 class="display-serif text-3xl font-black">Progress {{ $progressPercent }}%</h2>
                <progress class="store-progress-meter mt-3" max="100" value="{{ $progressPercent }}">{{ $progressPercent }}%</progress>
                <div class="mt-4 grid gap-2">
                    @foreach($order->progressSteps as $step)
                        <div class="flex justify-between rounded border border-[#211f1f] p-3 text-sm"><span>{{ $step->label }}</span><strong>{{ $step->is_completed ? 'Selesai' : 'Menunggu' }}</strong></div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-lg border border-[#211f1f] bg-[#c6d99a] p-5">
                <h2 class="display-serif text-3xl font-black">Pembayaran QRIS</h2>
                <div class="mt-4 grid gap-3">
                    @foreach($order->payments as $payment)
                        <div class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-black">{{ $payment->payment_number }}</p>
                                    <p class="text-sm">{{ $payment->type }} • {{ $payment->status }} • {{ $price->format($payment->amount) }}</p>
                                </div>
                                @if($payment->status !== \App\Models\Payment::STATUS_PAID)
                                    <form method="POST" action="{{ route('orders.payments.pay', [$order, $payment]) }}">
                                        @csrf
                                        <button class="btn-ink">Bayar QRIS</button>
                                    </form>
                                @else
                                    <span class="rounded-full border border-[#211f1f] bg-[#c6d99a] px-3 py-2 text-sm font-black">Lunas</span>
                                @endif
                            </div>
                            <p class="mt-3 text-xs">Klik tombol Bayar QRIS untuk membuka halaman Snap Midtrans. QRIS akan muncul di halaman Midtrans dan bisa discan dari aplikasi e-wallet atau mobile banking.</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-lg border border-[#211f1f] bg-[#b7dfe6] p-5">
                <h2 class="display-serif text-3xl font-black">Preview</h2>
                @if($order->preview_url)
                    <a class="mt-3 block rounded border border-[#211f1f] bg-[#fffdf5] p-3 font-bold" rel="nofollow noindex" href="{{ $order->preview_url }}">Buka preview ber-watermark</a>
                    @if($order->work_status === \App\Models\Order::WORK_PREVIEW)
                        <form class="mt-3" method="POST" action="{{ route('orders.approve-preview', $order) }}">@csrf<button class="btn-ink">Setujui Preview</button></form>
                        <form class="mt-3" method="POST" action="{{ route('orders.revision', $order) }}">@csrf<textarea class="field" name="note" placeholder="Catatan revisi"></textarea><button class="btn-soft mt-2">Ajukan Revisi</button></form>
                    @endif
                @else
                    <p class="mt-2">Preview belum tersedia.</p>
                @endif
            </div>
            <div class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5">
                <h2 class="display-serif text-3xl font-black">Timeline</h2>
                <div class="mt-3 grid gap-2 text-sm">
                    @foreach($order->activities as $activity)<p class="rounded border border-[#211f1f] p-3">{{ $activity->created_at->format('d M Y H:i') }} — {{ $activity->message }}</p>@endforeach
                </div>
            </div>
            @if($order->work_status === \App\Models\Order::WORK_COMPLETED)
                <div class="rounded-lg border border-[#211f1f] bg-[#c6d99a] p-5">
                    <h2 class="display-serif text-3xl font-black">Beri ulasan</h2>
                    @foreach($order->items as $item)
                        @if($item->catalog_id)
                            <form class="mt-3 rounded border border-[#211f1f] bg-[#fffdf5] p-3" method="POST" action="{{ route('orders.reviews.store', [$order, $item->catalog_id]) }}">
                                @csrf
                                <p class="font-black">{{ $item->catalog_name }}</p>
                                <select class="field mt-2" name="rating"><option value="5">5 bintang</option><option value="4">4 bintang</option><option value="3">3 bintang</option><option value="2">2 bintang</option><option value="1">1 bintang</option></select>
                                <textarea class="field mt-2" name="comment" placeholder="Ceritakan pengalamanmu"></textarea>
                                <button class="btn-ink mt-2">Kirim ulasan</button>
                            </form>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
        <aside class="h-fit rounded-lg border border-[#211f1f] bg-[#f7b1c8] p-5">
            <h2 class="display-serif text-3xl font-black">Ringkasan</h2>
            <p class="mt-3 text-sm font-bold uppercase">{{ $order->payment_status }} • {{ $order->work_status }}</p>
            <div class="mt-4 grid gap-2 text-sm">
                @foreach($order->items as $item)<div class="border-b border-[#211f1f]/30 pb-2"><strong>{{ $item->catalog_name }}</strong><br>{{ $price->format($item->line_total) }}</div>@endforeach
                <div class="flex justify-between"><span>Total</span><strong>{{ $price->format($order->total) }}</strong></div>
                <div class="flex justify-between"><span>Terbayar</span><strong>{{ $price->format($order->paid_amount) }}</strong></div>
                <div class="flex justify-between"><span>Sisa</span><strong>{{ $price->format(max(0, $order->total - $order->paid_amount)) }}</strong></div>
            </div>
        </aside>
    </div>
</section>
@endsection
