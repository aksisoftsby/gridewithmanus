@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kendaraan Driver: {{ $driver ? 'ID ' . $driver->id : '' }}</h1>
            <p class="text-sm text-gray-500">Tambah dan kelola kendaraan driver</p>
        </div>
        <a href="{{ route('kota.drivers.show', $driver->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-900 px-4 py-3 rounded-lg mb-6 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif
    @if(session('success'))
        <div class="bg-pink-50 border border-pink-200 text-pink-900 px-4 py-3 rounded-lg mb-6 text-sm">{{ session('success') }}</div>
    @endif

    <form action="{{ route('kota.drivers.vehicles.store', $driver->id) }}" method="POST" class="bg-white p-6 rounded-xl shadow border border-gray-100 space-y-4 mb-6">
        @csrf
        <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Tambah Kendaraan</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tipe Kendaraan *</label>
                <select name="vehicle_type" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    <option value="MOTOR">Motor</option>
                    <option value="MOBIL">Mobil</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Kapasitas Penumpang *</label>
                <input type="number" name="capacity" min="1" max="20" value="1" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Merk</label>
                <input type="text" name="brand" value="{{ old('brand') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Model</label>
                <input type="text" name="model" value="{{ old('model') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tahun</label>
                <input type="text" name="year_kendaraan" value="{{ old('year_kendaraan') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Warna</label>
                <input type="text" name="color" value="{{ old('color') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Plat Nomor</label>
                <input type="text" name="plate_number" value="{{ old('plate_number') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none uppercase">
            </div>
        </div>
        <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2 rounded-lg text-sm font-semibold shadow transition">
            <i class="fa-solid fa-plus mr-1"></i> Tambah Kendaraan
        </button>
    </form>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Daftar Kendaraan</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Tipe</th>
                        <th class="px-6 py-3">Merk/Model</th>
                        <th class="px-6 py-3">Plat</th>
                        <th class="px-6 py-3">Warna</th>
                        <th class="px-6 py-3">Kapasitas</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($vehicles as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3"><span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ ucfirst(strtolower($item->vehicle_type ?? '-')) }}</span></td>
                            <td class="px-6 py-3 font-semibold text-gray-800">{{ $item->brand ?? '-' }} {{ $item->model ?? '' }}</td>
                            <td class="px-6 py-3 font-mono text-gray-600">{{ $item->plate_number ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->color ?? '-' }}</td>
                            <td class="px-6 py-3">{{ $item->capacity ?? '-' }}</td>
                            <td class="px-6 py-3">{!! statusBadge($item->status_verifikasi ?? 'unknown') !!}</td>
                            <td class="px-6 py-3">
                                <form action="{{ route('kota.drivers.vehicles.destroy', [$driver->id, $item->id]) }}" method="POST" onsubmit="return confirm('Hapus kendaraan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-xs"><i class="fa-solid fa-trash mr-1"></i>Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-6 text-center text-gray-500">Belum ada kendaraan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
