@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Driver: {{ $user->full_name }}</h1>
            <p class="text-sm text-gray-500">Update profil, status, dan kota operasi driver</p>
        </div>
        <a href="{{ route('kota.drivers.show', $driver->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Detail
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-900 px-4 py-3 rounded-lg mb-6 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('kota.drivers.update', $driver->id) }}" method="POST" class="bg-white p-6 rounded-xl shadow border border-gray-100 space-y-4">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama (dari akun user)</label>
                <input type="text" value="{{ $user->full_name }}" disabled class="w-full px-4 py-2 border rounded-lg bg-gray-50 text-gray-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    <option value="ONLINE" {{ $driver->status === 'ONLINE' ? 'selected' : '' }}>ONLINE</option>
                    <option value="OFFLINE" {{ $driver->status === 'OFFLINE' ? 'selected' : '' }}>OFFLINE</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Kota Operasi</label>
                <select name="operating_city_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    <option value="">-- Tidak ada --</option>
                    @foreach($allKota as $item)
                        <option value="{{ $item->id }}" {{ $driver->operating_city_id == $item->id ? 'selected' : '' }}>{{ $item->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Rating (0-5)</label>
                <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $driver->rating) }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Latitude</label>
                <input type="number" step="any" name="current_lat" value="{{ old('current_lat', $driver->current_lat) }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Longitude</label>
                <input type="number" step="any" name="current_lng" value="{{ old('current_lng', $driver->current_lng) }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_verified" id="is_verified" value="1" {{ $driver->is_verified ? 'checked' : '' }} class="w-4 h-4 text-pink-600">
            <label for="is_verified" class="text-sm font-semibold text-gray-700">Terverifikasi</label>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-save mr-1"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection
