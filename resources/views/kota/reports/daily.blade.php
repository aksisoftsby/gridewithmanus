@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Harian</h1>
            <p class="text-sm text-gray-500">Periode {{ $from }} s/d {{ $to }} — area coverage Anda</p>
        </div>
        <a href="{{ route('kota.reports.index', request()->only('from','to')) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Laporan
        </a>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Total Order</th>
                        <th class="px-6 py-3">Revenue (Completed)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($daily as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-mono text-xs text-gray-600">{{ $item->day }}</td>
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
</div>
@endsection
