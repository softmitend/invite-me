@extends('layouts.admin', ['heading' => 'Manajemen Kategori'])

@section('content')
<div class="space-y-5">
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5">
            <p class="text-xs font-black uppercase">Total kategori</p>
            <p class="display-serif mt-2 text-4xl font-black">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-lg border border-[#211f1f] bg-[#c6d99a] p-5">
            <p class="text-xs font-black uppercase">Kategori aktif</p>
            <p class="display-serif mt-2 text-4xl font-black">{{ $stats['active'] }}</p>
        </div>
        <div class="rounded-lg border border-[#211f1f] bg-[#f7b1c8] p-5">
            <p class="text-xs font-black uppercase">Kategori nonaktif</p>
            <p class="display-serif mt-2 text-4xl font-black">{{ $stats['inactive'] }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-[#211f1f] bg-[#b7dfe6] p-4" x-data="{ createOpen: false }">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <form class="flex flex-1 flex-col gap-3 md:flex-row" method="GET" action="{{ route('admin.categories.index') }}">
                <input class="field" name="search" value="{{ request('search') }}" placeholder="Cari nama atau slug kategori">
                <button class="btn-ink h-12 px-8">Cari</button>
                @if(request('search'))
                    <a class="btn-soft h-12 px-8 text-center" href="{{ route('admin.categories.index') }}">Reset</a>
                @endif
            </form>
            <button type="button" class="btn-soft h-12 px-8" @click="createOpen = true">Kategori Baru</button>
        </div>

        <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" @keydown.escape.window="createOpen = false">
            <form class="w-full max-w-xl rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5" method="POST" action="{{ route('admin.categories.store') }}" @click.outside="createOpen = false">
                @csrf
                <div class="flex items-start justify-between gap-4">
                    <h2 class="display-serif text-4xl font-black">Kategori baru</h2>
                    <button type="button" class="rounded-full border border-[#211f1f] px-3 py-1 font-black" @click="createOpen = false">x</button>
                </div>
                <label class="mt-4 block text-sm font-bold">Nama<input class="field mt-1" name="name" required></label>
                <label class="mt-3 block text-sm font-bold">Slug<input class="field mt-1" name="slug"></label>
                <label class="mt-3 block text-sm font-bold">Urutan<input class="field mt-1" name="sort_order" type="number" value="0"></label>
                <div class="mt-4" x-data="{ active: '1' }">
                    <p class="text-sm font-bold">Status</p>
                    <input type="hidden" name="is_active" :value="active">
                    <div class="mt-2 flex gap-2">
                        <button type="button" class="rounded-full border border-[#211f1f] px-4 py-2 text-sm font-black" :class="active === '1' ? 'bg-[#c6d99a]' : 'bg-[#fffdf5]'" @click="active = '1'">Aktif</button>
                        <button type="button" class="rounded-full border border-[#211f1f] px-4 py-2 text-sm font-black" :class="active === '0' ? 'bg-[#f7b1c8]' : 'bg-[#fffdf5]'" @click="active = '0'">Nonaktif</button>
                    </div>
                </div>
                <button class="btn-ink mt-5">Simpan</button>
            </form>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-[#211f1f] bg-[#fffdf5]">
        <table class="w-full border-collapse text-left text-sm">
            <thead class="bg-[#211f1f] text-[#fffdf5]">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Urutan</th>
                    <th class="px-4 py-3">Produk</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr class="border-t border-[#211f1f]" x-data="{ detailOpen: false, editMode: false, active: '{{ $category->is_active ? '1' : '0' }}' }">
                        <td class="px-4 py-3 font-black">{{ $category->name }}</td>
                        <td class="px-4 py-3">{{ $category->slug }}</td>
                        <td class="px-4 py-3">{{ $category->sort_order }}</td>
                        <td class="px-4 py-3">{{ $category->catalogs_count }} katalog</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full border border-[#211f1f] px-3 py-1 text-xs font-black {{ $category->is_active ? 'bg-[#c6d99a]' : 'bg-[#f7b1c8]' }}">
                                {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" class="btn-soft py-2" @click="detailOpen = true; editMode = false">Detail</button>

                            <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" @keydown.escape.window="detailOpen = false">
                                <div class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5" @click.outside="detailOpen = false">
                                    <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="text-xs font-black uppercase">Detail kategori</p>
                                                <h2 class="display-serif text-4xl font-black">{{ $category->name }}</h2>
                                            </div>
                                            <button type="button" class="rounded-full border border-[#211f1f] px-3 py-1 font-black" @click="detailOpen = false">x</button>
                                        </div>

                                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                                            <label class="text-sm font-bold">Nama
                                                <input class="field mt-1" name="name" value="{{ $category->name }}" :disabled="!editMode" required>
                                            </label>
                                            <label class="text-sm font-bold">Slug
                                                <input class="field mt-1" name="slug" value="{{ $category->slug }}" :disabled="!editMode" required>
                                            </label>
                                            <label class="text-sm font-bold">Urutan
                                                <input class="field mt-1" name="sort_order" type="number" value="{{ $category->sort_order }}" :disabled="!editMode">
                                            </label>
                                            <div>
                                                <p class="text-sm font-bold">Jumlah katalog</p>
                                                <p class="mt-1 rounded-lg border border-[#211f1f] bg-[#b7dfe6] px-4 py-3 font-black">{{ $category->catalogs_count }} katalog</p>
                                            </div>
                                            <div class="md:col-span-2">
                                                <p class="text-sm font-bold">Status</p>
                                                <input type="hidden" name="is_active" :value="active">
                                                <div class="mt-2 flex gap-2">
                                                    <button type="button" class="rounded-full border border-[#211f1f] px-4 py-2 text-sm font-black disabled:opacity-60" :class="active === '1' ? 'bg-[#c6d99a]' : 'bg-[#fffdf5]'" :disabled="!editMode" @click="active = '1'">Aktif</button>
                                                    <button type="button" class="rounded-full border border-[#211f1f] px-4 py-2 text-sm font-black disabled:opacity-60" :class="active === '0' ? 'bg-[#f7b1c8]' : 'bg-[#fffdf5]'" :disabled="!editMode" @click="active = '0'">Nonaktif</button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-5 flex flex-wrap justify-end gap-2">
                                            <button type="button" class="btn-soft" x-show="!editMode" @click="editMode = true">Edit</button>
                                            <button type="button" class="btn-soft bg-[#fffdf5]" x-show="editMode" @click="editMode = false; active = '{{ $category->is_active ? '1' : '0' }}'">Batal</button>
                                            <button class="btn-ink" x-show="editMode">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-8 text-center font-black" colspan="6">Kategori tidak ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $categories->links() }}</div>
</div>
@endsection
