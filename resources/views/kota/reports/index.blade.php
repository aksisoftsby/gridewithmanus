@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Operasional Area Anda</h1>
            <p class="text-sm text-gray-500">Rekap order dan revenue periode tertentu</p>
        </div>
        <a href="{{ route('kota.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow border border-gray-100 mb-6">
        <form action="{{ route('kota.reports.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Dari</label>
                <input type="date" name="from" value="{{ $from }}" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Sampai</label>
                <input type="date" name="to" value="{{ $to }}" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Tampilkan</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase">Total Order</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['orders'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow border border-emerald-100 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase">Completed</p>
            <p class="text-3xl font-bold text-emerald-600 mt-1">{{ number_format($stats['completed'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow border border-red-100 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase">Cancelled</p>
            <p class="text-3xl font-bold text-red-600 mt-1">{{ number_format($stats['cancelled'] ?? 0) }}</p>
        </div>
        <div class="bg-pink-50 rounded-xl shadow border border-pink-100 p-5">
            <p class="text-xs font-semibold text-pink-700 uppercase">Revenue (Completed)</p>
            <p class="text-3xl font-bold text-pink-700 mt-1">Rp{{ number_format($stats['revenue'] ?? 0, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Rekap Per Tipe Order</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                            <th class="px-6 py-3">Tipe</th>
                            <th class="px-6 py-3">Total Order</th>
                            <th class="px-6 py-3">Revenue (Completed)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($byType as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3"><span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ ucfirst(strtolower($item->order_type ?? '-')) }}</span></td>
                                <td class="px-6 py-3 font-semibold">{{ number_format($item->total) }}</td>
                                <td class="px-6 py-3 font-semibold text-pink-700">Rp{{ number_format($item->revenue ?? 0, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-6 text-center text-gray-500">Tidak ada data pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Order Per Hari</h3>
                <a href="{{ route('kota.reports.daily', request()->only('from','to')) }}" class="text-xs text-orange-600 hover:underline">Lihat tabel harian &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                            <th class="px-6 py-3">Tanggal</th>
                            <th class="px-6 py-3">Total Order</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($daily as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-mono text-xs text-gray-600">{{ \Carbon\Carbon::parse($item->day)->format('d M Y') }}</td>
                                <td class="px-6 py-3 font-semibold">{{ number_format($item->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-6 py-6 text-center text-gray-500">Tidak ada data pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
