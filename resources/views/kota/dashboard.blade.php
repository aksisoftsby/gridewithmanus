@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Panel Kota — Dashboard</h1>
            <p class="text-sm text-gray-500">Pengelolaan wilayah Indonesia (Provinsi → Kota/Kabupaten) untuk dropdown formulir</p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-xs font-semibold uppercase">Coverage Kota Saya</div>
            <div class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['coverage_kota'] }}</div>
            <a href="{{ route('kota.coverage.index') }}" class="text-xs text-orange-600 hover:underline mt-2 inline-block">Lihat coverage &rarr;</a>
        </div>
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-xs font-semibold uppercase">Merchant dalam Coverage</div>
            <div class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['merchant'] }}</div>
            <a href="{{ route('kota.members.index', ['type' => 'merchant']) }}" class="text-xs text-orange-600 hover:underline mt-2 inline-block">Kelola merchant &rarr;</a>
        </div>
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-xs font-semibold uppercase">Total Driver</div>
            <div class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['driver'] }}
                @if (!empty($stats['driver_catatan']))<span class="text-[10px] font-normal text-gray-400">*</span>@endif
            </div>
            <a href="{{ route('kota.members.index', ['type' => 'driver']) }}" class="text-xs text-orange-600 hover:underline mt-2 inline-block">Kelola driver &rarr;</a>
        </div>
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-xs font-semibold uppercase">Total Pengguna</div>
            <div class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['user'] }}</div>
            <a href="{{ route('kota.users.index') }}" class="text-xs text-orange-600 hover:underline mt-2 inline-block">Kelola pengguna &rarr;</a>
        </div>
    </div>

    <!-- Role Info -->
    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800 text-lg">Sistem Role Panel Kota</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Role</th>
                        <th class="px-6 py-3">Akses Panel /admin/kota</th>
                        <th class="px-6 py-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="px-6 py-4 font-semibold text-gray-800"><span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">ADMIN</span></td>
                        <td class="px-6 py-4 text-red-600 font-semibold">Tidak (login di /admin/login)</td>
                        <td class="px-6 py-4 text-gray-600">Admin super menggunakan panel /admin. LOGIN di /admin/kota akan ditolak untuk role ADMIN</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 font-semibold text-gray-800"><span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">MANAGER</span></td>
                        <td class="px-6 py-4 text-pink-800 font-semibold">Boleh (login di /admin/kota)</td>
                        <td class="px-6 py-4 text-gray-600">Pengelola kota; memanage merchant sesuai coverage dan driver. LOGIN di /admin/login ditolak untuk role MANAGER.</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 font-semibold text-gray-800"><span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">MEMBER</span></td>
                        <td class="px-6 py-4 text-red-600 font-semibold">Tidak boleh (default)</td>
                        <td class="px-6 py-4 text-gray-600">Pengguna baru otomatis ber-role MEMBER</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if (!empty($stats['driver_catatan']))
        <p class="text-xs text-gray-400 mb-3">* Tabel driver tidak menyimpan data kota; daftar driver menampilkan semua driver yang terdaftar. Filtering lokasi driver berdasarkan coverage kota tidak tersedia.</p>
    @endif

    <div class="flex gap-3">
        <a href="{{ route('kota.wilayah.index') }}" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
            <i class="fa-solid fa-map-location-dot mr-1"></i> Kelola Wilayah
        </a>
        <a href="{{ route('kota.users.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
            <i class="fa-solid fa-users mr-1"></i> Kelola Pengguna
        </a>
        <a href="{{ route('kota.members.index', ['type' => 'merchant']) }}" class="bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
            <i class="fa-solid fa-store mr-1"></i> Kelola Members
        </a>
        <a href="{{ route('kota.coverage.index') }}" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
            <i class="fa-solid fa-map-pin mr-1"></i> Coverage Kota
        </a>
    </div>
</div>
@endsection
