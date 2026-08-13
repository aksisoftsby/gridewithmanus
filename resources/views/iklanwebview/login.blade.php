@extends('layouts.webview', ['title' => 'Login — Iklan Baris Gride'])

@section('content')
<div class="flex justify-center py-6">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
        <div class="text-center mb-5">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full brand-gradient text-white text-2xl mb-3">
                <i class="fa-solid fa-user"></i>
            </div>
            <h1 class="text-lg font-bold text-gray-900">Login Gride</h1>
            <p class="text-xs text-gray-500 mt-1">Masuk dengan akun Gride Anda untuk memasang iklan</p>
        </div>

        <form action="{{ route('webview.login') }}?intended={{ urlencode($intended) }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required value="{{ old('email') }}" class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="email@anda.com">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white font-bold py-3.5 rounded-xl transition shadow">
                <i class="fa-solid fa-right-to-bracket mr-2"></i>Login
            </button>
        </form>

        <p class="text-[11px] text-gray-400 text-center mt-4">Satu akun Gride berlaku untuk semua layanan (Customer, Driver, Merchant).</p>
    </div>
</div>
@endsection
