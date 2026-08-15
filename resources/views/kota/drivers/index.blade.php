@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Drivers — Driver Area Anda</h1>
            <p class="text-sm text-gray-500">Driver yang beroperasi di coverage kota Anda</p>
        </div>
        <a href="{{ route('kota.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow border border-gray-100 mb-6">
        <form action="{{ route('kota.drivers.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Nama / email / phone / plat" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none w-72">
            </div>
            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari
            </button>
            @if($search)
                <a href="{{ route('kota.drivers.index') }}" class="text-sm text-orange-700 hover:underline py-2">Hapus pencarian</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Daftar Driver ({{ $drivers->total() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">Phone</th>
                        <th class="px-6 py-3">Kota Operasi</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Verifikasi</th>
                        <th class="px-6 py-3">Rating</th>
                        <th class="px-6 py-3">Trip</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($drivers as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-mono text-xs text-gray-500">{{ $item->id }}</td>
                            <td class="px-6 py-3 font-semibold text-gray-800">{{ $item->full_name }}</td>
                            <td class="px-6 py-3">{{ $item->phone ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->kota_nama ?? '-' }}</td>
                            <td class="px-6 py-3">{!! statusBadge($item->status) !!}</td>
                            <td class="px-6 py-3">
                                @if($item->is_verified) <span class="text-emerald-600 text-xs font-semibold"><i class="fa-solid fa-circle-check mr-1"></i>Verified</span>
                                @else <span class="text-gray-400 text-xs">Belum</span> @endif
                            </td>
                            <td class="px-6 py-3">
                                @if($item->rating)<span class="text-yellow-500"><i class="fa-solid fa-star mr-1"></i></span>{{ number_format($item->rating, 1) }}@else - @endif
                            </td>
                            <td class="px-6 py-3">{{ $item->total_trips ?? 0 }}</td>
                            <td class="px-6 py-3">
                                <a href="{{ route('kota.drivers.show', $item->id) }}" class="text-orange-600 hover:text-orange-800 font-medium text-xs"><i class="fa-solid fa-eye mr-1"></i>Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-6 py-6 text-center text-gray-500">Tidak ada driver di area Anda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $drivers->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
