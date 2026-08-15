@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Merchants — Merchant Area Anda</h1>
            <p class="text-sm text-gray-500">Merchant yang terdaftar di coverage kota Anda</p>
        </div>
        <a href="{{ route('kota.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow border border-gray-100 mb-6">
        <form action="{{ route('kota.merchants.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Nama merchant / nama pemilik" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none w-72">
            </div>
            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari
            </button>
            @if($search)
                <a href="{{ route('kota.merchants.index') }}" class="text-sm text-orange-700 hover:underline py-2">Hapus pencarian</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Daftar Merchant ({{ $merchants->total() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">Pemilik</th>
                        <th class="px-6 py-3">Tipe</th>
                        <th class="px-6 py-3">Kota</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Buka</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($merchants as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-mono text-xs text-gray-500">{{ $item->id }}</td>
                            <td class="px-6 py-3 font-semibold text-gray-800">{{ $item->name }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->owner_name ?? '-' }}</td>
                            <td class="px-6 py-3"><span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">{{ ucfirst(strtolower($item->type ?? '-')) }}</span></td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->kota_nama ?? '-' }}</td>
                            <td class="px-6 py-3">{!! statusBadge($item->status) !!}</td>
                            <td class="px-6 py-3">
                                @if($item->is_open) <span class="text-emerald-600 text-xs font-semibold">Buka</span>
                                @else <span class="text-gray-400 text-xs">Tutup</span> @endif
                            </td>
                            <td class="px-6 py-3 space-x-2">
                                <a href="{{ route('kota.merchants.show', $item->id) }}" class="text-orange-600 hover:text-orange-800 font-medium text-xs"><i class="fa-solid fa-eye mr-1"></i>Detail</a>
                                <a href="{{ route('kota.merchants.edit', $item->id) }}" class="text-pink-600 hover:text-pink-800 font-medium text-xs"><i class="fa-solid fa-pen mr-1"></i>Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-6 text-center text-gray-500">Tidak ada merchant di area Anda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $merchants->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
