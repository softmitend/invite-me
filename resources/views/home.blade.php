@extends('layouts.store', ['title' => 'InviteMe'])

@section('content')
<section class="paper-grid border-b border-[#211f1f]">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-16 md:grid-cols-[1.1fr_.9fr] md:items-center">
        <div>
            <p class="mb-4 inline-flex rounded-full border border-[#211f1f] bg-[#f7b1c8] px-3 py-1 text-xs font-black uppercase">Studio undangan personal</p>
            <h1 class="display-serif max-w-4xl text-6xl font-black leading-[.9] md:text-8xl">Undangan digital yang terasa personal, rapi, dan siap dibagikan.</h1>
            <p class="mt-6 max-w-2xl text-lg">Pilih katalog, isi detail acara, bayar aman, pantau preview, ajukan revisi, lalu dapatkan hasil final setelah lunas.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a class="btn-ink" href="{{ route('catalog.index') }}">Lihat Semua Katalog</a>
                <a class="btn-soft" href="#alur">Lihat Alur</a>
            </div>
        </div>
        <div class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-3">
            <div class="grid gap-3">
                @foreach($featuredCatalogs->take(3) as $catalog)
                    <div class="grid grid-cols-[120px_1fr] gap-3 rounded-md border border-[#211f1f] bg-[#b7dfe6] p-2">
                        <img class="aspect-square rounded-md object-cover" src="{{ $catalog->images->first()->path }}" alt="{{ $catalog->name }}">
                        <div class="self-center">
                            <p class="text-xs font-black uppercase">{{ $catalog->category->name }}</p>
                            <p class="display-serif text-2xl font-black leading-tight">{{ $catalog->name }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="border-y border-[#211f1f] bg-[#c6d99a] py-2 text-center text-xs font-black uppercase">Pilih desain • isi data • preview • revisi • final siap pakai</div>
</section>

<section class="mx-auto max-w-7xl px-4 py-14">
    <div class="grid gap-4 md:grid-cols-3">
        @foreach(['Undangan web dengan RSVP, galeri, musik, dan peta.', 'Kartu ucapan digital untuk momen cepat dan hangat.', 'Preview aman dengan revisi dan status progress jelas.'] as $copy)
            <div class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-6"><p class="display-serif text-3xl font-black">0{{ $loop->iteration }}</p><p class="mt-3 text-sm">{{ $copy }}</p></div>
        @endforeach
    </div>
</section>

<section class="border-y border-[#211f1f] bg-[#f7b1c8] px-4 py-14">
    <div class="mx-auto max-w-7xl">
        <h2 class="display-serif text-center text-5xl font-black">Kategori populer</h2>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            @forelse($categories as $category)
                <a href="{{ route('catalog.index', ['category' => $category->slug]) }}" class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-4 text-center">
                    <p class="display-serif text-2xl font-black">{{ $category->name }}</p>
                    <p class="mt-2 text-xs font-bold uppercase">{{ $category->catalogs_count }} katalog</p>
                </a>
            @empty
                <p class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-6">Belum ada kategori aktif.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-14">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h2 class="display-serif text-5xl font-black">Katalog unggulan</h2>
        <a class="btn-soft" href="{{ route('catalog.index') }}">Lihat Semua Katalog</a>
    </div>
    <div class="catalog-list-grid mt-8 grid gap-5 md:grid-cols-3">
        @forelse($featuredCatalogs as $catalog)
            @include('partials.catalog-card', ['catalog' => $catalog])
        @empty
            <div class="rounded-lg border border-[#211f1f] bg-[#b7dfe6] p-8">Belum ada katalog unggulan.</div>
        @endforelse
    </div>
</section>

<section id="alur" class="paper-grid border-y border-[#211f1f] px-4 py-14">
    <div class="mx-auto max-w-5xl">
        <h2 class="display-serif text-center text-5xl font-black">Alur pemesanan</h2>
        <div class="mt-8 grid gap-3 md:grid-cols-5">
            @foreach(['Pilih katalog', 'Checkout', 'Isi data', 'Preview', 'Final'] as $step)
                <div class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-4 text-center font-black">{{ $step }}</div>
            @endforeach
        </div>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-14">
    <h2 class="display-serif text-center text-5xl font-black">Kata customer</h2>
    <div class="mt-8 grid gap-4 md:grid-cols-3">
        @forelse($reviews as $review)
            <div class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5">
                <p class="font-black">{{ $review->user->name }}</p>
                <p class="mt-2 text-sm">{{ $review->comment }}</p>
                <p class="mt-3 text-xs font-bold uppercase">{{ $review->catalog->name }} • {{ $review->rating }} ★</p>
            </div>
        @empty
            <p class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-6">Ulasan akan muncul setelah pesanan selesai.</p>
        @endforelse
    </div>
</section>

<section class="border-t border-[#211f1f] bg-[#b7dfe6] px-4 py-14 text-center">
    <h2 class="display-serif text-5xl font-black">Mulai dari desain yang paling dekat dengan ceritamu.</h2>
    <a class="btn-ink mt-6 inline-flex" href="{{ route('catalog.index') }}">Pilih katalog</a>
</section>
@endsection
