<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'InviteMe') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#fffdf5] text-[#211f1f]">
    <main class="mx-auto grid min-h-screen max-w-3xl place-items-center px-4 text-center">
        <div>
            <h1 class="display-serif text-6xl font-black">ivm.</h1>
            <p class="mt-3 text-lg">Studio undangan digital.</p>
            <a class="btn-ink mt-6 inline-flex" href="{{ route('home') }}">Masuk ke Situs</a>
        </div>
    </main>
</body>
</html>
