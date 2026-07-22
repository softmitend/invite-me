@extends('layouts.store', ['title' => 'Checkout'])

@php($price = app(\App\Services\CatalogPricingService::class))

@section('content')
<section class="mx-auto max-w-6xl px-4 py-10">
    <h1 class="display-serif text-6xl font-black">Checkout</h1>
    <form class="mt-6 grid gap-6 md:grid-cols-[1fr_360px]" method="POST" action="{{ route('checkout.store') }}">
        @csrf
        <div class="grid gap-5">
            <div class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5">
                <h2 class="display-serif text-3xl font-black">Data customer</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <label class="text-sm font-bold">Nama<input class="field mt-1" name="name" value="{{ old('name', auth()->user()->name) }}" required></label>
                    <label class="text-sm font-bold">Email<input class="field mt-1" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required></label>
                    <label class="text-sm font-bold md:col-span-2">WhatsApp<input class="field mt-1" name="phone" value="{{ old('phone', auth()->user()->phone) }}" required></label>
                </div>
            </div>
            <div class="rounded-lg border border-[#211f1f] bg-[#b7dfe6] p-5">
                <h2 class="display-serif text-3xl font-black">Data personalisasi</h2>
                @foreach($cart->items as $item)
                    <div class="mt-4 rounded-lg border border-[#211f1f] bg-[#fffdf5] p-4">
                        <p class="font-black">{{ $item->catalog->name }}</p>
                        <div class="mt-3 grid gap-3">
                            @foreach($item->catalog->inputFields as $field)
                                <label class="text-sm font-bold">{{ $field->label }}{{ $field->is_required ? ' *' : '' }}
                                    @if($field->type === 'long_text')
                                        <textarea class="field mt-1" name="fields[{{ $field->id }}]" placeholder="{{ $field->placeholder }}"></textarea>
                                    @else
                                        <input class="field mt-1" name="fields[{{ $field->id }}]" type="{{ $field->type === 'date' ? 'date' : 'text' }}" placeholder="{{ $field->placeholder }}" @required($field->is_required)>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <aside class="h-fit rounded-lg border border-[#211f1f] bg-[#c6d99a] p-5">
            <h2 class="display-serif text-3xl font-black">Pembayaran</h2>
            <div class="mt-4 grid gap-2 text-sm">
                <div class="flex justify-between"><span>Subtotal</span><strong>{{ $price->format($totals['subtotal']) }}</strong></div>
                <div class="flex justify-between"><span>Diskon</span><strong>{{ $price->format($totals['discount']) }}</strong></div>
                <div class="flex justify-between border-t border-[#211f1f] pt-3 text-lg"><span>Total</span><strong>{{ $price->format($totals['total']) }}</strong></div>
                <div class="flex justify-between"><span>DP 50%</span><strong>{{ $price->format((int) ceil($totals['total'] * .5)) }}</strong></div>
                <div class="flex justify-between"><span>Sisa pelunasan</span><strong>{{ $price->format($totals['total'] - (int) ceil($totals['total'] * .5)) }}</strong></div>
            </div>
            <label class="mt-5 block rounded-lg border border-[#211f1f] bg-[#fffdf5] p-3 text-sm font-bold"><input type="radio" name="payment_scheme" value="deposit" checked> Bayar DP</label>
            <label class="mt-2 block rounded-lg border border-[#211f1f] bg-[#fffdf5] p-3 text-sm font-bold"><input type="radio" name="payment_scheme" value="full_payment"> Bayar penuh</label>
            <label class="mt-4 flex gap-2 text-sm"><input type="checkbox" name="terms" value="1" required> Saya menyetujui syarat layanan.</label>
            @if($errors->any())<div class="mt-3 rounded border border-[#211f1f] bg-[#f7b1c8] p-3 text-sm font-bold">{{ $errors->first() }}</div>@endif
            <button class="btn-ink mt-5 w-full">Konfirmasi dan Bayar</button>
        </aside>
    </form>
</section>
@endsection
