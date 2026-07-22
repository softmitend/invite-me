<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'InviteMe' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/store.css') }}">
    @if(request()->routeIs('catalog.index'))
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/catalog-select2.css') }}">
    @endif
    <script defer src="{{ asset('js/catalog-gallery.js') }}"></script>
    @stack('styles')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @if(request()->routeIs('catalog.index'))
        <script defer src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script defer src="{{ asset('js/catalog-filters.js') }}"></script>
    @endif
</head>
<body class="min-h-screen">
    <div class="bg-[#a8dce3] px-4 py-2 text-center text-xs font-bold uppercase tracking-wide ink-border border-x-0 border-t-0">Studio undangan digital, web invitation, dan kartu ucapan personal</div>
    <header class="store-header sticky top-0 z-40 border-b border-[#211f1f] bg-[#fffdf5]/95 backdrop-blur">
        <div class="store-header-inner mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
            <a href="{{ route('home') }}" class="display-serif text-4xl font-black leading-none">ivm.</a>
            <nav class="hidden items-center gap-6 text-xs font-bold uppercase md:flex">
                <a href="{{ route('catalog.index') }}">Katalog</a>
                <a href="{{ route('cart.index') }}">Keranjang</a>
                @auth
                    <a href="{{ route('orders.index') }}">Pesanan</a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                    @endif
                @endauth
            </nav>
            <div class="store-header-actions flex items-center gap-2 text-sm">
                @auth
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-soft py-2">Keluar</button></form>
                @else
                    <a class="btn-soft py-2" href="{{ route('login') }}">Masuk</a>
                @endauth
                <a class="btn-ink py-2" href="{{ route('catalog.index') }}">Pilih Desain</a>
            </div>
        </div>
    </header>
    <main>
        @if(session('success'))
            <div class="mx-auto mt-4 max-w-7xl px-4"><div class="rounded-lg border border-[#211f1f] bg-[#c6d99a] p-3 font-semibold">{{ session('success') }}</div></div>
        @endif
        @if(session('error'))
            <div class="mx-auto mt-4 max-w-7xl px-4"><div class="rounded-lg border border-[#211f1f] bg-[#f7b1c8] p-3 font-semibold">{{ session('error') }}</div></div>
        @endif
        @yield('content')
    </main>
    <footer class="store-footer border-t border-[#211f1f] bg-[#211f1f] px-4 py-10 text-[#fffdf5]">
        <div class="mx-auto grid max-w-7xl gap-6 md:grid-cols-3">
            <div><div class="display-serif text-4xl font-black">ivm.</div><p class="mt-2 text-sm text-[#fffdf5]/75">Undangan digital yang rapi, hangat, dan siap dibagikan.</p></div>
            <div class="text-sm"><p class="font-bold uppercase">Alur</p><p class="mt-2">Pilih desain, isi data, bayar, preview, revisi, lalu final.</p></div>
            <div class="text-sm">
                <p class="font-bold uppercase">Akun demo</p>
                <p class="mt-2">Admin: admin@inviteme.test / password</p>
                <p class="mt-1">Customer: nadira@example.test / password</p>
            </div>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
