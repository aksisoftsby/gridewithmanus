@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-16 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-12 h-12 bg-amber-100 text-amber-600 rounded-full mb-3">
            <i class="fa-solid fa-map-location-dot text-xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Login Panel Kota</h2>
        <p class="text-sm text-gray-500">Gride Superapp — Pengelolaan Wilayah Provinsi &amp; Kota</p>
    </div>

    <form action="{{ route('kota.login') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
            <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
            <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:outline-none">
        </div>

        <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2.5 rounded-lg shadow transition">
            Login ke Panel Kota
        </button>
    </form>

    <div class="mt-6 text-center bg-gray-50 p-3 rounded-lg text-xs text-gray-600">
        <p><strong>Panel Kota</strong> hanya dapat diakses pengguna dengan role <strong>ADMIN</strong> atau <strong>MANAGER</strong>.</p>
        <p class="mt-1">Role default pengguna baru adalah <strong>MEMBER</strong>.</p>
        <a href="{{ route('admin.login') }}" class="inline-block mt-2 text-amber-700 hover:underline">← Kembali ke Admin Login</a>
    </div>
</div>
@endsection
