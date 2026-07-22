@extends('layouts.store', ['title' => 'Katalog InviteMe'])

@section('content')
<section class="catalog-hero paper-grid border-b border-[#211f1f] px-4 py-12">
    <div class="mx-auto max-w-7xl">
        <h1 class="display-serif text-6xl font-black leading-none md:text-8xl">Katalog undangan</h1>
        <p class="mt-4 max-w-2xl">Cari desain undangan web, undangan digital, atau kartu ucapan yang bisa langsung dipersonalisasi.</p>
    </div>
</section>

<section class="catalog-page mx-auto max-w-7xl px-4 py-8">
    <form
        class="catalog-filter rounded-lg border border-[#211f1f] bg-[#b7dfe6] p-4"
        method="GET"
        x-data="{ filtersOpen: false }"
    >
        <div class="catalog-search-row grid gap-3 md:grid-cols-[1fr_auto_auto]">
            <input class="field" name="search" value="{{ request('search') }}" placeholder="Cari nama katalog">
            <button
                type="button"
                class="grid h-12 w-12 place-items-center rounded-lg border border-[#211f1f] bg-[#fffdf5] font-black"
                :class="filtersOpen ? 'bg-[#f7b1c8]' : 'bg-[#fffdf5]'"
                @click="filtersOpen = !filtersOpen"
                :aria-expanded="filtersOpen.toString()"
                aria-controls="catalog-filters"
                title="Tampilkan filter"
            >
                <span class="sr-only">Tampilkan filter</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 5h18"></path>
                    <path d="M7 12h10"></path>
                    <path d="M10 19h4"></path>
                </svg>
            </button>
            <button class="btn-ink h-12 px-8">Cari</button>
        </div>

        <div
            id="catalog-filters"
            class="catalog-filter-panel mt-3 grid gap-3 md:grid-cols-4"
            x-show="filtersOpen"
            x-cloak
            x-transition.opacity.duration.150ms
        >
            <select class="field js-select2" name="category" data-placeholder="Semua kategori">
                <option value="">Semua kategori</option>
                @foreach($categories as $category)<option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>@endforeach
            </select>
            <select class="field js-select2" name="sort" data-placeholder="Urutkan">
                <option value="latest" @selected(request('sort') === 'latest')>Terbaru</option>
                <option value="price_low" @selected(request('sort') === 'price_low')>Harga terendah</option>
                <option value="price_high" @selected(request('sort') === 'price_high')>Harga tertinggi</option>
                <option value="rating" @selected(request('sort') === 'rating')>Rating</option>
            </select>
            <input class="field" name="min_price" value="{{ request('min_price') }}" placeholder="Harga min">
            <input class="field" name="max_price" value="{{ request('max_price') }}" placeholder="Harga max">
        </div>
    </form>

    <div class="catalog-list-grid mt-8 grid gap-5 md:grid-cols-3">
        @forelse($catalogs as $catalog)
            @include('partials.catalog-card', ['catalog' => $catalog])
        @empty
            <div class="rounded-lg border border-[#211f1f] bg-[#f7b1c8] p-8 md:col-span-3">
                <p class="display-serif text-4xl font-black">Belum ketemu.</p>
                <p class="mt-2">Coba kosongkan filter atau pakai kata kunci lain.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">{{ $catalogs->links() }}</div>
</section>
@endsection
