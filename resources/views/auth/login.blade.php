@extends('layouts.store', ['title' => 'Masuk'])

@section('content')
<section class="paper-grid px-4 py-16">
    <form class="mx-auto max-w-md rounded-lg border border-[#211f1f] bg-[#fffdf5] p-6" method="POST" action="{{ route('login.store') }}">
        @csrf
        <h1 class="display-serif text-5xl font-black">Masuk</h1>
        <label class="mt-5 block text-sm font-bold">Email<input class="field mt-1" name="email" type="email" value="{{ old('email') }}" required></label>
        <label class="mt-3 block text-sm font-bold">Password<input class="field mt-1" name="password" type="password" required></label>
        @error('email')<p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p>@enderror
        <button class="btn-ink mt-5 w-full">Masuk</button>
        <p class="mt-4 text-center text-sm">Belum punya akun? <a class="font-black underline" href="{{ route('register') }}">Daftar</a></p>
    </form>
</section>
@endsection
