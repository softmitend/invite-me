@extends('layouts.store', ['title' => 'Daftar'])

@section('content')
<section class="paper-grid px-4 py-16">
    <form class="mx-auto max-w-lg rounded-lg border border-[#211f1f] bg-[#fffdf5] p-6" method="POST" action="{{ route('register.store') }}">
        @csrf
        <h1 class="display-serif text-5xl font-black">Daftar</h1>
        <label class="mt-5 block text-sm font-bold">Nama<input class="field mt-1" name="name" value="{{ old('name') }}" required></label>
        <label class="mt-3 block text-sm font-bold">Email<input class="field mt-1" name="email" type="email" value="{{ old('email') }}" required></label>
        <label class="mt-3 block text-sm font-bold">WhatsApp<input class="field mt-1" name="phone" value="{{ old('phone') }}" required></label>
        <label class="mt-3 block text-sm font-bold">Password<input class="field mt-1" name="password" type="password" required></label>
        <label class="mt-3 block text-sm font-bold">Konfirmasi Password<input class="field mt-1" name="password_confirmation" type="password" required></label>
        @if($errors->any())<div class="mt-3 rounded border border-[#211f1f] bg-[#f7b1c8] p-3 text-sm font-bold">{{ $errors->first() }}</div>@endif
        <button class="btn-ink mt-5 w-full">Buat akun</button>
    </form>
</section>
@endsection
