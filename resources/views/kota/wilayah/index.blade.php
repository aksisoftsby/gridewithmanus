@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Data Wilayah Indonesia</h1>
            <p class="text-sm text-gray-500">Provinsi &rarr; Kota/Kabupaten (untuk dropdown formulir)</p>
        </div>
        <a href="{{ route('kota.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    <!-- Filter Provinsi -->
    <div class="bg-white p-4 rounded-xl shadow border border-gray-100 mb-6">
        <form action="{{ route('kota.wilayah.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Filter Provinsi</label>
                <select name="provinsi" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none w-64">
                    <option value="">-- Semua Provinsi --</option>
                    @foreach($provinsis as $p)
                        <option value="{{ $p->id }}" {{ (string)($searchProvinsi ?? '') === (string)$p->id ? 'selected' : '' }}>{{ $p->nama }} ({{ $p->pulau }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-filter mr-1"></i> Terapkan
            </button>
            @if($searchProvinsi)
                <a href="{{ route('kota.wilayah.index') }}" class="text-sm text-orange-700 hover:underline py-2">Hapus filter</a>
            @endif
        </form>
    </div>

    <!-- Tabel Kota/Kabupaten -->
    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Kota/Kabupaten ({{ $kota->total() }} data)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Provinsi</th>
                        <th class="px-6 py-3">Nama Kota/Kabupaten</th>
                        <th class="px-6 py-3">Tipe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($kota as $k)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-500">{{ $k->id }}</td>
                            <td class="px-6 py-3">{{ $k->provinsi_nama }}</td>
                            <td class="px-6 py-3 font-semibold text-gray-800">{{ $k->nama }}</td>
                            <td class="px-6 py-3">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $k->tipe === 'Kota' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-900' }}">{{ $k->tipe }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-6 text-center text-gray-500">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $kota->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
