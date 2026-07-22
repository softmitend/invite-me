@extends('layouts.admin', ['heading' => 'Manajemen Katalog'])

@php($price = app(\App\Services\CatalogPricingService::class))

@section('content')
<div class="space-y-5">
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5">
            <p class="text-xs font-black uppercase">Total katalog</p>
            <p class="display-serif mt-2 text-4xl font-black">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-lg border border-[#211f1f] bg-[#c6d99a] p-5">
            <p class="text-xs font-black uppercase">Katalog aktif</p>
            <p class="display-serif mt-2 text-4xl font-black">{{ $stats['active'] }}</p>
        </div>
        <div class="rounded-lg border border-[#211f1f] bg-[#f7b1c8] p-5">
            <p class="text-xs font-black uppercase">Katalog nonaktif</p>
            <p class="display-serif mt-2 text-4xl font-black">{{ $stats['inactive'] }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-[#211f1f] bg-[#b7dfe6] p-4" x-data="{ createOpen: false }">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <form class="flex flex-1 flex-col gap-3 md:flex-row" method="GET" action="{{ route('admin.catalogs.index') }}">
                <input class="field" name="search" value="{{ request('search') }}" placeholder="Cari nama, slug, atau kategori katalog">
                <button class="btn-ink h-12 px-8">Cari</button>
                @if(request('search'))
                    <a class="btn-soft h-12 px-8 text-center" href="{{ route('admin.catalogs.index') }}">Reset</a>
                @endif
            </form>
            <button type="button" class="btn-soft h-12 px-8" @click="createOpen = true">Katalog Baru</button>
        </div>

        <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" @keydown.escape.window="createOpen = false">
            <form class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5" method="POST" action="{{ route('admin.catalogs.store') }}" @click.outside="createOpen = false">
                @csrf
                <div class="flex items-start justify-between gap-4">
                    <h2 class="display-serif text-4xl font-black">Katalog baru</h2>
                    <button type="button" class="rounded-full border border-[#211f1f] px-3 py-1 font-black" @click="createOpen = false">x</button>
                </div>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="text-sm font-bold">Nama<input class="field mt-1" name="name" required></label>
                    <label class="text-sm font-bold">Slug<input class="field mt-1" name="slug"></label>
                    <label class="text-sm font-bold">Kategori
                        <select class="field mt-1" name="category_id" required>
                            @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-sm font-bold">Harga<input class="field mt-1" name="base_price" type="number" required></label>
                    <label class="text-sm font-bold">Estimasi hari<input class="field mt-1" name="estimated_days" type="number" value="3" required></label>
                    <label class="text-sm font-bold">URL gambar utama<input class="field mt-1" name="image_path" type="url" required></label>
                    <label class="text-sm font-bold md:col-span-2">URL preview template/sample yang sudah dideploy<input class="field mt-1" name="preview_url" type="url" placeholder="https://sample-undangan.example.com" required></label>
                    <label class="text-sm font-bold md:col-span-2">Deskripsi<textarea class="field mt-1" name="description" required></textarea></label>
                </div>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div x-data="{ active: '1' }">
                        <p class="text-sm font-bold">Status</p>
                        <input type="hidden" name="is_active" :value="active">
                        <div class="mt-2 flex gap-2">
                            <button type="button" class="rounded-full border border-[#211f1f] px-4 py-2 text-sm font-black" :class="active === '1' ? 'bg-[#c6d99a]' : 'bg-[#fffdf5]'" @click="active = '1'">Aktif</button>
                            <button type="button" class="rounded-full border border-[#211f1f] px-4 py-2 text-sm font-black" :class="active === '0' ? 'bg-[#f7b1c8]' : 'bg-[#fffdf5]'" @click="active = '0'">Nonaktif</button>
                        </div>
                    </div>
                    <div x-data="{ featured: '0' }">
                        <p class="text-sm font-bold">Unggulan</p>
                        <input type="hidden" name="is_featured" :value="featured">
                        <div class="mt-2 flex gap-2">
                            <button type="button" class="rounded-full border border-[#211f1f] px-4 py-2 text-sm font-black" :class="featured === '1' ? 'bg-[#c6d99a]' : 'bg-[#fffdf5]'" @click="featured = '1'">Ya</button>
                            <button type="button" class="rounded-full border border-[#211f1f] px-4 py-2 text-sm font-black" :class="featured === '0' ? 'bg-[#f7b1c8]' : 'bg-[#fffdf5]'" @click="featured = '0'">Tidak</button>
                        </div>
                    </div>
                </div>
                <button class="btn-ink mt-5">Simpan Katalog</button>
            </form>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-[#211f1f] bg-[#fffdf5]">
        <table class="w-full border-collapse text-left text-sm">
            <thead class="bg-[#211f1f] text-[#fffdf5]">
                <tr>
                    <th class="px-4 py-3">Katalog</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3">Harga</th>
                    <th class="px-4 py-3">Estimasi</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Preview</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($catalogs as $catalog)
                    <tr class="border-t border-[#211f1f]" x-data="{ detailOpen: false, editMode: false, active: '{{ $catalog->is_active ? '1' : '0' }}', featured: '{{ $catalog->is_featured ? '1' : '0' }}' }">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img class="h-14 w-14 rounded-md border border-[#211f1f] object-cover" src="{{ $catalog->images->first()->path ?? 'https://images.unsplash.com/photo-1523438885200-e635ba2c371e?auto=format&fit=crop&w=300&q=80' }}" alt="{{ $catalog->name }}">
                                <div>
                                    <p class="font-black">{{ $catalog->name }}</p>
                                    <p class="text-xs">{{ $catalog->slug }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $catalog->category->name }}</td>
                        <td class="px-4 py-3 font-black">{{ $price->format($catalog->base_price) }}</td>
                        <td class="px-4 py-3">{{ $catalog->estimated_days }} hari</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <span class="rounded-full border border-[#211f1f] px-3 py-1 text-xs font-black {{ $catalog->is_active ? 'bg-[#c6d99a]' : 'bg-[#f7b1c8]' }}">{{ $catalog->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                @if($catalog->is_featured)<span class="rounded-full border border-[#211f1f] bg-[#b7dfe6] px-3 py-1 text-xs font-black">Unggulan</span>@endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if($catalog->preview_url)
                                <a class="font-black underline" href="{{ $catalog->preview_url }}" target="_blank" rel="noopener nofollow">Buka</a>
                            @else
                                <span class="text-xs font-black">Belum ada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" class="btn-soft py-2" @click="detailOpen = true; editMode = false">Detail</button>

                            <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4 text-left" @keydown.escape.window="detailOpen = false">
                                <div class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5" @click.outside="detailOpen = false">
                                    <form method="POST" action="{{ route('admin.catalogs.update', $catalog) }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="text-xs font-black uppercase">Detail katalog</p>
                                                <h2 class="display-serif text-4xl font-black">{{ $catalog->name }}</h2>
                                            </div>
                                            <button type="button" class="rounded-full border border-[#211f1f] px-3 py-1 font-black" @click="detailOpen = false">x</button>
                                        </div>

                                        <div class="mt-5 grid gap-5 md:grid-cols-[180px_1fr]">
                                            <img class="aspect-square w-full rounded-lg border border-[#211f1f] object-cover" src="{{ $catalog->images->first()->path ?? 'https://images.unsplash.com/photo-1523438885200-e635ba2c371e?auto=format&fit=crop&w=300&q=80' }}" alt="{{ $catalog->name }}">
                                            <div class="grid gap-4 md:grid-cols-2">
                                                <label class="text-sm font-bold">Nama<input class="field mt-1" name="name" value="{{ $catalog->name }}" :disabled="!editMode" required></label>
                                                <label class="text-sm font-bold">Slug<input class="field mt-1" name="slug" value="{{ $catalog->slug }}" :disabled="!editMode"></label>
                                                <label class="text-sm font-bold">Kategori
                                                    <select class="field mt-1" name="category_id" :disabled="!editMode" required>
                                                        @foreach($categories as $category)<option value="{{ $category->id }}" @selected($category->id === $catalog->category_id)>{{ $category->name }}</option>@endforeach
                                                    </select>
                                                </label>
                                                <label class="text-sm font-bold">Harga<input class="field mt-1" name="base_price" type="number" value="{{ $catalog->base_price }}" :disabled="!editMode" required></label>
                                                <label class="text-sm font-bold">Estimasi hari<input class="field mt-1" name="estimated_days" type="number" value="{{ $catalog->estimated_days }}" :disabled="!editMode" required></label>
                                                <label class="text-sm font-bold">URL gambar utama<input class="field mt-1" name="image_path" type="url" value="{{ $catalog->images->first()->path ?? '' }}" :disabled="!editMode"></label>
                                                <label class="text-sm font-bold md:col-span-2">URL preview template/sample<input class="field mt-1" name="preview_url" type="url" value="{{ $catalog->preview_url }}" :disabled="!editMode" required></label>
                                                <label class="text-sm font-bold md:col-span-2">Deskripsi<textarea class="field mt-1" name="description" :disabled="!editMode" required>{{ $catalog->description }}</textarea></label>
                                            </div>
                                        </div>

                                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                                            <div>
                                                <p class="text-sm font-bold">Status</p>
                                                <input type="hidden" name="is_active" :value="active">
                                                <div class="mt-2 flex gap-2">
                                                    <button type="button" class="rounded-full border border-[#211f1f] px-4 py-2 text-sm font-black disabled:opacity-60" :class="active === '1' ? 'bg-[#c6d99a]' : 'bg-[#fffdf5]'" :disabled="!editMode" @click="active = '1'">Aktif</button>
                                                    <button type="button" class="rounded-full border border-[#211f1f] px-4 py-2 text-sm font-black disabled:opacity-60" :class="active === '0' ? 'bg-[#f7b1c8]' : 'bg-[#fffdf5]'" :disabled="!editMode" @click="active = '0'">Nonaktif</button>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold">Unggulan</p>
                                                <input type="hidden" name="is_featured" :value="featured">
                                                <div class="mt-2 flex gap-2">
                                                    <button type="button" class="rounded-full border border-[#211f1f] px-4 py-2 text-sm font-black disabled:opacity-60" :class="featured === '1' ? 'bg-[#c6d99a]' : 'bg-[#fffdf5]'" :disabled="!editMode" @click="featured = '1'">Ya</button>
                                                    <button type="button" class="rounded-full border border-[#211f1f] px-4 py-2 text-sm font-black disabled:opacity-60" :class="featured === '0' ? 'bg-[#f7b1c8]' : 'bg-[#fffdf5]'" :disabled="!editMode" @click="featured = '0'">Tidak</button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-5 flex flex-wrap items-center justify-between gap-2">
                                            <div>
                                                @if($catalog->preview_url)
                                                    <a class="btn-soft bg-[#b7dfe6]" href="{{ $catalog->preview_url }}" target="_blank" rel="noopener nofollow">Buka Preview</a>
                                                @else
                                                    <span class="rounded-full border border-[#211f1f] bg-[#f7b1c8] px-3 py-2 text-sm font-black">Belum ada preview</span>
                                                @endif
                                            </div>
                                            <div class="flex gap-2">
                                                <button type="button" class="btn-soft" x-show="!editMode" @click="editMode = true">Edit</button>
                                                <button type="button" class="btn-soft bg-[#fffdf5]" x-show="editMode" @click="editMode = false; active = '{{ $catalog->is_active ? '1' : '0' }}'; featured = '{{ $catalog->is_featured ? '1' : '0' }}'">Batal</button>
                                                <button class="btn-ink" x-show="editMode">Simpan Perubahan</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-8 text-center font-black" colspan="7">Katalog tidak ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $catalogs->links() }}</div>
</div>
@endsection
