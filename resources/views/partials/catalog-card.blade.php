@php($price = app(\App\Services\CatalogPricingService::class))
<article class="catalog-card overflow-hidden rounded-lg border border-[#211f1f] bg-[#fffdf5]" x-data="{ open: false, image: 0 }">
    <button type="button" class="catalog-card-main block w-full text-left" @click="open = true">
        <img class="catalog-card-image aspect-[4/3] w-full object-cover" src="{{ $catalog->images->first()->path ?? 'https://images.unsplash.com/photo-1523438885200-e635ba2c371e?auto=format&fit=crop&w=900&q=80' }}" alt="{{ $catalog->name }}">
        <div class="catalog-card-body p-4">
            <div class="catalog-card-meta flex items-center justify-between gap-3 text-xs font-bold uppercase">
                <span class="rounded-full border border-[#211f1f] bg-[#b7dfe6] px-2 py-1">{{ $catalog->category->name }}</span>
                <span>{{ number_format((float) $catalog->rating_average, 1) }} ★ ({{ $catalog->reviews_count }})</span>
            </div>
            <h3 class="catalog-card-title display-serif mt-3 text-2xl font-black leading-tight">{{ $catalog->name }}</h3>
            <div class="catalog-card-price mt-3 flex items-end gap-2">
                @if($price->discountAmount($catalog) > 0)
                    <span class="text-sm line-through opacity-60">{{ $price->format($catalog->base_price) }}</span>
                @endif
                <span class="text-lg font-black">{{ $price->format($price->finalPrice($catalog)) }}</span>
            </div>
        </div>
    </button>
    <div class="catalog-card-actions grid grid-cols-2 border-t border-[#211f1f]">
        <form method="POST" action="{{ route('cart.store', $catalog) }}">@csrf<button class="w-full border-r border-[#211f1f] bg-[#f7b1c8] px-3 py-3 text-sm font-black">Keranjang</button></form>
        <form method="POST" action="{{ route('cart.store', $catalog) }}">@csrf<input type="hidden" name="buy_now" value="1"><button class="w-full bg-[#c6d99a] px-3 py-3 text-sm font-black">Beli</button></form>
    </div>
    <div x-show="open" x-cloak class="catalog-modal fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" @keydown.escape.window="open = false">
        <div class="catalog-modal-panel max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-lg border border-[#211f1f] bg-[#fffdf5]" @click.outside="open = false">
            <div class="catalog-modal-grid grid md:grid-cols-2">
                <div class="bg-[#b7dfe6] p-4">
                    @foreach($catalog->images->take(5) as $image)
                        <img x-show="image === {{ $loop->index }}" class="aspect-square w-full rounded-lg border border-[#211f1f] object-cover" src="{{ $image->path }}" alt="{{ $image->alt_text }}">
                    @endforeach
                    <div class="mt-3 flex gap-2">
                        @foreach($catalog->images->take(5) as $image)
                            <button class="h-12 w-12 overflow-hidden rounded border border-[#211f1f]" @click="image = {{ $loop->index }}"><img class="h-full w-full object-cover" src="{{ $image->path }}" alt=""></button>
                        @endforeach
                    </div>
                </div>
                <div class="p-5">
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="display-serif text-4xl font-black leading-none">{{ $catalog->name }}</h2>
                        <button class="rounded-full border border-[#211f1f] px-3 py-1 font-black" @click="open = false">×</button>
                    </div>
                    <p class="mt-3 text-sm">{{ $catalog->description }}</p>
                    <p class="mt-4 text-2xl font-black">{{ $price->format($price->finalPrice($catalog)) }}</p>
                    <dl class="mt-4 grid gap-2 text-sm">
                        @foreach($catalog->specifications as $spec)
                            <div class="flex justify-between border-b border-[#211f1f]/20 py-2"><dt class="font-bold">{{ $spec->label }}</dt><dd>{{ $spec->value }}</dd></div>
                        @endforeach
                    </dl>
                    <div class="mt-5 rounded-lg border border-[#211f1f] bg-[#f7b1c8] p-3 text-sm">
                        <p class="font-black">Data yang perlu disiapkan</p>
                        <ul class="mt-2 list-inside list-disc">
                            @foreach($catalog->inputFields as $field)<li>{{ $field->label }}{{ $field->is_required ? ' *' : '' }}</li>@endforeach
                        </ul>
                    </div>
                    <div class="catalog-modal-actions mt-5 flex gap-2">
                        <form method="POST" action="{{ route('cart.store', $catalog) }}">@csrf<button class="btn-soft">Masukkan keranjang</button></form>
                        @if($catalog->preview_url)
                            <a class="btn-soft bg-[#b7dfe6]" href="{{ $catalog->preview_url }}" target="_blank" rel="noopener nofollow">Preview</a>
                        @endif
                        <a class="btn-ink" href="{{ route('catalog.show', $catalog) }}">Buka detail</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</article>
