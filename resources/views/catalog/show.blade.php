@extends('layouts.store', ['title' => $catalog->name])

@php($price = app(\App\Services\CatalogPricingService::class))
@php($images = $catalog->images->take(5)->values())
@php($galleryImages = $images->map(fn ($image) => ['src' => $image->path, 'alt' => $image->alt_text ?: $catalog->name])->values())

@section('content')
<section class="product-detail mx-auto grid max-w-7xl gap-8 px-4 py-10 md:grid-cols-2">
    <div
        class="min-w-0"
        x-data="catalogGallery($el)"
        x-init="start()"
        data-gallery-images='@json($galleryImages->isNotEmpty() ? $galleryImages : [['src' => 'https://images.unsplash.com/photo-1523438885200-e635ba2c371e?auto=format&fit=crop&w=900&q=80', 'alt' => $catalog->name]])'
    >
        <div class="relative overflow-hidden rounded-lg border border-[#211f1f] bg-[#b7dfe6]">
            <img
                class="aspect-square w-full object-cover"
                :src="images[image].src"
                :alt="images[image].alt"
                src="{{ $galleryImages->first()['src'] ?? 'https://images.unsplash.com/photo-1523438885200-e635ba2c371e?auto=format&fit=crop&w=900&q=80' }}"
                alt="{{ $galleryImages->first()['alt'] ?? $catalog->name }}"
            >

            @if($images->count() > 1)
                <button type="button" class="absolute left-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full border border-[#211f1f] bg-[#fffdf5] font-black" @click="select((image - 1 + total) % total)" aria-label="Gambar sebelumnya">‹</button>
                <button type="button" class="absolute right-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full border border-[#211f1f] bg-[#fffdf5] font-black" @click="select((image + 1) % total)" aria-label="Gambar berikutnya">›</button>
            @endif
        </div>

        @if($images->count() > 1)
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($images as $image)
                    <button
                        type="button"
                        class="catalog-thumb overflow-hidden rounded border border-[#211f1f] bg-[#fffdf5] p-0.5"
                        :class="image === {{ $loop->index }} ? 'ring-2 ring-[#211f1f]' : ''"
                        @click="select({{ $loop->index }})"
                        aria-label="Pilih gambar {{ $loop->iteration }}"
                    >
                        <img class="rounded-sm" src="{{ $image->path }}" alt="{{ $image->alt_text ?: $catalog->name }}">
                    </button>
                @endforeach
            </div>
        @endif
    </div>
    <div>
        <p class="inline-flex rounded-full border border-[#211f1f] bg-[#c6d99a] px-3 py-1 text-xs font-black uppercase">{{ $catalog->category->name }}</p>
        <h1 class="display-serif mt-4 text-6xl font-black leading-none">{{ $catalog->name }}</h1>
        <p class="mt-4">{{ $catalog->description }}</p>
        <p class="mt-6 text-3xl font-black">{{ $price->format($price->finalPrice($catalog)) }}</p>
        <div class="mt-6 rounded-lg border border-[#211f1f] bg-[#fffdf5] p-4">
            @foreach($catalog->specifications as $spec)
                <div class="flex justify-between border-b border-[#211f1f]/20 py-2"><span class="font-bold">{{ $spec->label }}</span><span>{{ $spec->value }}</span></div>
            @endforeach
        </div>
        <div class="mt-6 rounded-lg border border-[#211f1f] bg-[#f7b1c8] p-4">
            <p class="font-black">Data personalisasi</p>
            <ul class="mt-2 list-inside list-disc text-sm">@foreach($catalog->inputFields as $field)<li>{{ $field->label }}{{ $field->is_required ? ' wajib' : '' }}</li>@endforeach</ul>
        </div>
        <div class="product-actions mt-6 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('cart.store', $catalog) }}">@csrf<button class="btn-ink">Masukkan Keranjang</button></form>
            @if($catalog->preview_url)
                <a class="btn-soft" href="{{ $catalog->preview_url }}" target="_blank" rel="noopener nofollow">Lihat Preview Produk</a>
            @else
                <span class="rounded-full border border-[#211f1f] bg-[#fffdf5] px-4 py-3 text-sm font-black">Preview belum tersedia</span>
            @endif
        </div>
    </div>
</section>
@endsection
