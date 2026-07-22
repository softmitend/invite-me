<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin InviteMe' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @if(request()->routeIs('admin.dashboard'))
        <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
    @endif
    @stack('styles')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @if(request()->routeIs('admin.dashboard'))
        <script defer src="{{ asset('js/admin-dashboard.js') }}"></script>
    @endif
</head>
<body class="admin-shell bg-[#f4f6f9] text-[#34395e]">
    <div class="min-h-screen md:flex">
        <aside class="border-b border-[#211f1f] bg-[#211f1f] p-4 text-[#fffdf5] md:min-h-screen md:w-64 md:border-b-0 md:border-r">
            <a href="{{ route('admin.dashboard') }}" class="display-serif text-4xl font-black">ivm.</a>
            <nav class="mt-8 grid gap-2 text-sm font-bold">
                <a class="rounded-md px-3 py-2 hover:bg-white/10" href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a class="rounded-md px-3 py-2 hover:bg-white/10" href="{{ route('admin.categories.index') }}">Kategori</a>
                <a class="rounded-md px-3 py-2 hover:bg-white/10" href="{{ route('admin.catalogs.index') }}">Katalog</a>
                <a class="rounded-md px-3 py-2 hover:bg-white/10" href="{{ route('admin.orders.index') }}">Pesanan</a>
                <a class="rounded-md px-3 py-2 hover:bg-white/10" href="{{ route('home') }}">Ke situs</a>
            </nav>
        </aside>
        <div class="flex-1">
            <header class="admin-content flex items-center justify-between border-b border-[#f2f2f2] bg-white px-6 py-4 shadow-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#98a6ad]">Admin Panel</p>
                    <p class="text-lg font-bold text-[#34395e]">{{ $heading ?? 'Admin' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-soft py-2">Keluar</button></form>
            </header>
            <main class="admin-content p-6">
                @if(session('success'))<div class="admin-alert mb-4 rounded-lg border border-[#bbf7d0] bg-[#f0fdf4] p-3 font-semibold text-[#166534]">{{ session('success') }}</div>@endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
