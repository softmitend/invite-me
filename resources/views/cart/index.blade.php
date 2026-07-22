@extends('layouts.store', ['title' => 'Keranjang'])

@php($price = app(\App\Services\CatalogPricingService::class))

@section('content')
<section class="cart-page mx-auto max-w-6xl px-4 py-10">
    <h1 class="display-serif text-6xl font-black">Keranjang</h1>
    <div class="mt-6 grid gap-6 md:grid-cols-[1fr_360px]">
        <div class="grid gap-3">
            @forelse($cart->items as $item)
                <div class="cart-item grid gap-4 rounded-lg border border-[#211f1f] bg-[#fffdf5] p-3 md:grid-cols-[120px_1fr_auto]">
                    <img class="cart-item-image aspect-square rounded-md object-cover" src="{{ $item->catalog->images->first()->path }}" alt="{{ $item->catalog->name }}">
                    <div class="cart-item-body">
                        <p class="text-xs font-bold uppercase">{{ $item->catalog->category->name }}</p>
                        <h2 class="display-serif text-3xl font-black">{{ $item->catalog->name }}</h2>
                        <p class="mt-2 font-bold">{{ $price->format($price->finalPrice($item->catalog)) }} × {{ $item->quantity }}</p>
                        @unless($item->catalog->is_active)<p class="mt-2 rounded bg-[#f7b1c8] p-2 text-sm font-bold">Katalog ini tidak aktif.</p>@endunless
                    </div>
                    <form method="POST" action="{{ route('cart.destroy', $item) }}">@csrf @method('DELETE')<button class="btn-soft">Hapus</button></form>
                </div>
            @empty
                <div class="rounded-lg border border-[#211f1f] bg-[#b7dfe6] p-8">Keranjang masih kosong.</div>
            @endforelse
        </div>
        <aside class="h-fit rounded-lg border border-[#211f1f] bg-[#c6d99a] p-5">
            <h2 class="display-serif text-3xl font-black">Ringkasan</h2>
            <div class="mt-4 grid gap-2 text-sm">
                <div class="flex justify-between"><span>Subtotal</span><strong>{{ $price->format($totals['subtotal']) }}</strong></div>
                <div class="flex justify-between"><span>Diskon</span><strong>{{ $price->format($totals['discount']) }}</strong></div>
                <div class="flex justify-between border-t border-[#211f1f] pt-3 text-lg"><span>Total</span><strong>{{ $price->format($totals['total']) }}</strong></div>
            </div>
            @auth
                <a class="btn-ink mt-5 block text-center" href="{{ route('checkout.index') }}">Lanjut Checkout</a>
            @else
                <a class="btn-ink mt-5 block text-center" href="{{ route('login') }}">Masuk untuk Checkout</a>
            @endauth
        </aside>
    </div>
</section>
@endsection
