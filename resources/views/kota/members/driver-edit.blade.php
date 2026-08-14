@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('kota.members.index', ['type' => 'driver']) }}" class="text-sm text-pink-600 hover:underline">
            <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke daftar driver
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Edit Driver: {{ $driver->full_name }}</h1>
        <p class="text-sm text-gray-500">{{ $driver->email ?? '' }} &middot; {{ $driver->phone ?? '' }}</p>
    </div>

    <form method="POST" action="{{ route('kota.members.driver.update', $driver->id) }}" class="bg-white rounded-xl shadow border border-gray-100 p-6 space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Rating (0 - 5)</label>
                <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ $driver->rating }}" class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Terverifikasi</label>
                <label class="inline-flex items-center mt-2">
                    <input type="checkbox" name="is_verified" value="1" {{ !empty($driver->is_verified) ? 'checked' : '' }} class="h-4 w-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                    <span class="ml-2 text-sm text-gray-700">Driver terverifikasi</span>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Latitude Lokasi Saat Ini</label>
                <input type="number" step="any" name="current_lat" value="{{ $driver->current_lat }}" class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Longitude Lokasi Saat Ini</label>
                <input type="number" step="any" name="current_lng" value="{{ $driver->current_lng }}" class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg px-4 py-3 text-xs text-gray-500">
            Status driver (ONLINE/OFFLINE) dapat diubah langsung dari daftar driver pada kolom status.
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Perubahan
            </button>
            <a href="{{ route('kota.members.index', ['type' => 'driver']) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-semibold transition">Batal</a>
        </div>
    </form>
</div>
@endsection
