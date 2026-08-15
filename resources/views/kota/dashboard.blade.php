@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Panel Kota — Dashboard</h1>
            <p class="text-sm text-gray-500">Monitoring area coverage Anda: {{ $user->full_name }} ({{ $user->email }})</p>
        </div>
        <span class="bg-blue-100 text-blue-800 px-3 py-1.5 rounded-full text-xs font-semibold">Coverage: {{ $stats['coverage_kota'] }} kota</span>
    </div>

    @if(!empty($cityNames))
        <div class="flex flex-wrap gap-2 mb-6">
            @foreach($cityNames as $nama)
                <span class="bg-pink-50 border border-pink-200 text-pink-800 px-3 py-1 rounded-full text-xs font-medium"><i class="fa-solid fa-map-pin mr-1"></i>{{ $nama }}</span>
            @endforeach
        </div>
    @endif

    <!-- Operations Stats -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-5 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-[10px] font-bold uppercase tracking-wide">Order Hari Ini</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['orders_today'] }}</div>
            <a href="{{ route('kota.orders.index') }}" class="text-[11px] text-orange-600 hover:underline mt-1 inline-block">Lihat semua &rarr;</a>
        </div>
        <div class="bg-white p-5 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-[10px] font-bold uppercase tracking-wide">Selesai Hari Ini</div>
            <div class="text-2xl font-bold text-emerald-700 mt-1">{{ $stats['orders_completed_today'] }}</div>
            <div class="text-[11px] text-gray-400 mt-1">dari {{ $stats['orders_total'] }} total order</div>
        </div>
        <div class="bg-white p-5 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-[10px] font-bold uppercase tracking-wide">Dibatalkan Hari Ini</div>
            <div class="text-2xl font-bold text-red-600 mt-1">{{ $stats['orders_cancelled_today'] }}</div>
        </div>
        <div class="bg-white p-5 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-[10px] font-bold uppercase tracking-wide">Revenue Hari Ini</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">Rp{{ number_format($stats['revenue_today'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white p-5 rounded-xl shadow border border-pink-100 bg-pink-50">
            <div class="text-pink-600 text-[10px] font-bold uppercase tracking-wide">Earning Driver Hari Ini</div>
            <div class="text-2xl font-bold text-pink-700 mt-1">Rp{{ number_format($stats['earnings_today'] ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- People Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-[10px] font-bold uppercase tracking-wide">Merchant</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['merchant'] }}</div>
            <div class="text-[11px] text-emerald-600 mt-1">{{ $stats['merchant_active'] }} aktif</div>
            <a href="{{ route('kota.merchants.index') }}" class="text-[11px] text-orange-600 hover:underline inline-block">Kelola &rarr;</a>
        </div>
        <div class="bg-white p-5 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-[10px] font-bold uppercase tracking-wide">Driver</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['driver'] }}</div>
            <div class="text-[11px] text-gray-500 mt-1">{{ $stats['driver_online'] }} online · {{ $stats['driver_offline'] }} offline · {{ $stats['driver_verified'] }} terverifikasi</div>
            <a href="{{ route('kota.drivers.index') }}" class="text-[11px] text-orange-600 hover:underline inline-block">Kelola &rarr;</a>
        </div>
        <div class="bg-white p-5 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-[10px] font-bold uppercase tracking-wide">Customer (Member)</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['customer'] }}</div>
            <a href="{{ route('kota.customers.index') }}" class="text-[11px] text-orange-600 hover:underline inline-block">Kelola &rarr;</a>
        </div>
        <div class="bg-white p-5 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-[10px] font-bold uppercase tracking-wide">Pengguna Sistem</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['user'] }}</div>
            <a href="{{ route('kota.users.index') }}" class="text-[11px] text-orange-600 hover:underline inline-block">Kelola &rarr;</a>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Operasional Cepat</h3>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6">
            <a href="{{ route('kota.rides.index') }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-100 hover:border-pink-300 hover:bg-pink-50 transition">
                <div class="w-10 h-10 rounded-full bg-pink-100 text-pink-700 flex items-center justify-center"><i class="fa-solid fa-car-side"></i></div>
                <div>
                    <div class="text-sm font-semibold text-gray-800">Rides</div>
                    <div class="text-[11px] text-gray-500">Order antar penumpang</div>
                </div>
            </a>
            <a href="{{ route('kota.deliveries.index') }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-100 hover:border-blue-300 hover:bg-blue-50 transition">
                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center"><i class="fa-solid fa-box"></i></div>
                <div>
                    <div class="text-sm font-semibold text-gray-800">Deliveries</div>
                    <div class="text-[11px] text-gray-500">Kirim barang</div>
                </div>
            </a>
            <a href="{{ route('kota.wallet.transactions.index') }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-100 hover:border-emerald-300 hover:bg-emerald-50 transition">
                <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center"><i class="fa-solid fa-money-bill-transfer"></i></div>
                <div>
                    <div class="text-sm font-semibold text-gray-800">Wallet Trans.</div>
                    <div class="text-[11px] text-gray-500">View only (read-only)</div>
                </div>
            </a>
            <a href="{{ route('kota.reports.index') }}" class="flex items-center gap-3 p-4 rounded-lg border border-gray-100 hover:border-orange-300 hover:bg-orange-50 transition">
                <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-700 flex items-center justify-center"><i class="fa-solid fa-chart-column"></i></div>
                <div>
                    <div class="text-sm font-semibold text-gray-800">Reports</div>
                    <div class="text-[11px] text-gray-500">Laporan area</div>
                </div>
            </a>
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
                        <td class="px-6 py-4 text-gray-600">Pengelola kota; memanage merchant, driver, dan customer sesuai coverage area. Login /admin/login ditolak untuk role MANAGER.</td>
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

    <div class="flex gap-3 flex-wrap">
        <a href="{{ route('kota.wilayah.index') }}" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
            <i class="fa-solid fa-map-location-dot mr-1"></i> Kelola Wilayah
        </a>
        <a href="{{ route('kota.coverage.index') }}" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
            <i class="fa-solid fa-map-pin mr-1"></i> Coverage Kota
        </a>
        <a href="{{ route('kota.profile') }}" class="bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
            <i class="fa-solid fa-user-gear mr-1"></i> My Profile
        </a>
    </div>
</div>
@endsection
