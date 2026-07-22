@extends('layouts.admin', ['heading' => 'Detail Pesanan'])

@php
    $price = app(\App\Services\CatalogPricingService::class);
    $workLabels = [
        'received' => 'Diterima',
        'in_progress' => 'Sedang Diproses',
        'preview' => 'Preview',
        'revision' => 'Revisi',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    $paymentLabels = [
        'unpaid' => 'Belum Dibayar',
        'pending' => 'Menunggu Pembayaran',
        'partially_paid' => 'DP Terbayar',
        'paid' => 'Lunas',
        'expired' => 'Kedaluwarsa',
        'failed' => 'Gagal',
        'refunded' => 'Refund',
        'partially_refunded' => 'Refund Sebagian',
    ];

    $stepTotal = $order->progressSteps->count();
    $stepDone = $order->progressSteps->where('is_completed', true)->count();
    $progressPercent = $stepTotal > 0 ? (int) round(($stepDone / $stepTotal) * 100) : 0;
@endphp

@section('content')
<div class="space-y-5" x-data="{ updateOpen: false, checklistFormOpen: false, confirmStep: null }">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <a class="text-sm font-bold text-[#0d6efd]" href="{{ route('admin.orders.index') }}">Kembali ke pesanan</a>
            <h1 class="mt-2 text-2xl font-bold">{{ $order->order_number }}</h1>
            <p class="text-sm text-muted">Dibuat {{ $order->created_at->format('d M Y H:i') }}</p>
        </div>
        <span class="rounded-full border border-[#211f1f] bg-[#b7dfe6] px-3 py-1 text-xs font-bold">
            {{ $workLabels[$order->work_status] ?? $order->work_status }}
        </span>
    </div>

    <section class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5">
        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase">Informasi pesanan</p>
                <h2 class="text-xl font-bold">Ringkasan order</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($order->preview_url)
                    <a class="btn-soft inline-flex py-2" href="{{ $order->preview_url }}" target="_blank" rel="noopener nofollow">Buka Preview</a>
                @endif
                @if($order->final_url)
                    <a class="btn-soft inline-flex py-2" href="{{ $order->final_url }}" target="_blank" rel="noopener nofollow">Buka Final</a>
                @endif
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded border border-[#211f1f] p-4">
                <p class="text-xs font-bold uppercase">Customer</p>
                <p class="mt-2 font-bold">{{ $order->customer_name }}</p>
                <p class="text-sm">{{ $order->customer_email }}</p>
                <p class="text-sm">{{ $order->customer_phone }}</p>
            </div>

            <div class="rounded border border-[#211f1f] p-4">
                <p class="text-xs font-bold uppercase">Template dipesan</p>
                <div class="mt-2 grid gap-2">
                    @foreach($order->items as $item)
                        <div>
                            <p class="font-bold">{{ $item->catalog_name }}</p>
                            <p class="text-sm">{{ $item->category_name }} - Qty {{ $item->quantity }} - {{ $price->format($item->line_total) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded border border-[#211f1f] p-4">
                <p class="text-xs font-bold uppercase">Pembayaran</p>
                <div class="mt-2 grid gap-1 text-sm">
                    <div class="flex justify-between gap-3"><span>Status</span><strong>{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</strong></div>
                    <div class="flex justify-between gap-3"><span>Total</span><strong>{{ $price->format($order->total) }}</strong></div>
                    <div class="flex justify-between gap-3"><span>Terbayar</span><strong>{{ $price->format($order->paid_amount) }}</strong></div>
                    <div class="flex justify-between gap-3"><span>Sisa</span><strong>{{ $price->format(max(0, $order->total - $order->paid_amount)) }}</strong></div>
                </div>
            </div>
        </div>

        <div class="mt-4 rounded border border-[#211f1f] p-4">
            <p class="text-xs font-bold uppercase">Note / data personalisasi</p>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                @forelse($order->inputValues as $value)
                    <div class="rounded border border-[#211f1f] bg-[#fffdf5] p-3">
                        <p class="text-xs font-bold uppercase">{{ $value->label }}</p>
                        <p class="mt-1 text-sm">{{ $value->value ?: $value->file_path ?: '-' }}</p>
                    </div>
                @empty
                    <p class="text-sm">Belum ada note atau data personalisasi.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="rounded-lg border border-[#211f1f] bg-[#fffdf5] p-5">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="w-full">
                <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase">Progress pengerjaan</p>
                        <h2 class="text-xl font-bold">{{ $stepTotal > 0 ? $progressPercent.'% selesai' : 'Checklist belum dibuat' }}</h2>
                    </div>
                    <p class="text-sm font-bold">{{ $stepDone }}/{{ $stepTotal }} checklist selesai</p>
                </div>

                @if($stepTotal > 0)
                    <progress class="progress-meter mt-3" max="100" value="{{ $progressPercent }}">{{ $progressPercent }}%</progress>
                @else
                    <p class="mt-3 rounded border border-[#211f1f] bg-[#fffdf5] p-3 text-sm">Pesanan masih diterima. Progress akan muncul setelah admin membuat checklist pertama.</p>
                @endif
            </div>
            <button type="button" class="btn-ink shrink-0" @click="updateOpen = !updateOpen">
                <span x-text="updateOpen ? 'Tutup Update' : 'Update Progress'"></span>
            </button>
        </div>

        <div class="mt-5 rounded border border-[#211f1f] bg-[#fffdf5] p-4" x-show="updateOpen" x-cloak x-transition>
            <div class="grid gap-4 lg:grid-cols-[320px_1fr]">
                <form class="rounded border border-[#211f1f] bg-[#b7dfe6] p-4" method="POST" action="{{ route('admin.orders.update', $order) }}">
                    @csrf
                    @method('PATCH')
                    <h3 class="text-base font-bold">Status dan link</h3>
                    <label class="mt-3 block">Status pengerjaan
                        <select class="field mt-1" name="work_status" required>
                            @foreach($workLabels as $value => $label)
                                <option value="{{ $value }}" @selected($order->work_status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="mt-3 block">URL preview
                        <input class="field mt-1" name="preview_url" value="{{ $order->preview_url }}" placeholder="https://preview.example.com">
                    </label>
                    <label class="mt-3 block">URL final
                        <input class="field mt-1" name="final_url" value="{{ $order->final_url }}" placeholder="https://final.example.com">
                    </label>
                    <button class="btn-ink mt-4 w-full">Simpan Status</button>
                </form>

                <div class="rounded border border-[#211f1f] bg-[#fffdf5] p-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-base font-bold">Checklist</h3>
                            <p class="text-sm text-muted">Centang checklist ketika pekerjaan benar-benar selesai.</p>
                        </div>
                        <button type="button" class="btn-soft" @click="checklistFormOpen = !checklistFormOpen">
                            <span x-text="checklistFormOpen ? 'Tutup Form' : 'Update Checklist'"></span>
                        </button>
                    </div>

                    <form class="mt-4 grid gap-3 rounded border border-[#211f1f] bg-[#f8f9fa] p-3 md:grid-cols-[1fr_110px_auto]" x-show="checklistFormOpen" x-cloak x-transition method="POST" action="{{ route('admin.orders.progress-steps.store', $order) }}">
                        @csrf
                        <input class="field" name="label" placeholder="Nama checklist, contoh: Buat halaman utama" required>
                        <input class="field" name="sort_order" type="number" placeholder="Urutan">
                        <button class="btn-soft">Tambah</button>
                    </form>

                    <div class="mt-4 grid gap-2">
                        @forelse($order->progressSteps as $step)
                            <div class="flex flex-col gap-3 rounded border border-[#211f1f] p-3 md:flex-row md:items-center md:justify-between">
                                <div class="flex items-center gap-3">
                                    @if($step->is_completed)
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded border border-[#211f1f] bg-[#c6d99a] text-xs font-bold">OK</span>
                                    @else
                                        <button type="button" class="h-5 w-5 shrink-0 rounded border border-[#211f1f] bg-white" aria-label="Tandai {{ $step->label }} selesai" @click="confirmStep = '{{ $step->id }}'"></button>
                                    @endif
                                    <div>
                                        <p class="font-bold">{{ $step->label }}</p>
                                        <p class="text-xs">{{ $step->is_completed ? 'Selesai '.$step->completed_at?->format('d M Y H:i') : 'Belum selesai' }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @if($step->is_completed)
                                        <form method="POST" action="{{ route('admin.orders.progress-steps.update', [$order, $step]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="label" value="{{ $step->label }}">
                                            <input type="hidden" name="sort_order" value="{{ $step->sort_order }}">
                                            <button name="is_completed" value="0" class="btn-soft py-2">Buka Lagi</button>
                                        </form>
                                    @else
                                        <button type="button" class="btn-soft py-2" @click="confirmStep = '{{ $step->id }}'">Selesai</button>
                                    @endif

                                    <form class="flex gap-2" method="POST" action="{{ route('admin.orders.progress-steps.update', [$order, $step]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_completed" value="{{ $step->is_completed ? '1' : '0' }}">
                                        <input class="field min-w-48" name="label" value="{{ $step->label }}" required>
                                        <input class="field w-20" name="sort_order" type="number" value="{{ $step->sort_order }}">
                                        <button class="btn-soft py-2">Simpan</button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.orders.progress-steps.destroy', [$order, $step]) }}" onsubmit="return confirm('Hapus checklist ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-soft py-2">Hapus</button>
                                    </form>
                                </div>

                                @unless($step->is_completed)
                                    <div x-show="confirmStep === '{{ $step->id }}'" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" @keydown.escape.window="confirmStep = null">
                                        <div class="w-full max-w-md rounded border border-[#211f1f] bg-[#fffdf5] p-5" @click.outside="confirmStep = null">
                                            <h3 class="text-lg font-bold">Selesaikan checklist?</h3>
                                            <p class="mt-2 text-sm">Apakah benar checklist "{{ $step->label }}" sudah selesai?</p>
                                            <div class="mt-4 flex justify-end gap-2">
                                                <button type="button" class="btn-soft" @click="confirmStep = null">Batal</button>
                                                <form method="POST" action="{{ route('admin.orders.progress-steps.update', [$order, $step]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="label" value="{{ $step->label }}">
                                                    <input type="hidden" name="sort_order" value="{{ $step->sort_order }}">
                                                    <button name="is_completed" value="1" class="btn-ink">Ya, Selesaikan</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endunless
                            </div>
                        @empty
                            <p class="rounded border border-[#211f1f] bg-[#f8f9fa] p-3 text-sm">Checklist masih kosong. Klik Update Checklist untuk membuat daftar progress.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
