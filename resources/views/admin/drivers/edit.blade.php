@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Driver: {{ $driver->full_name }}</h1>
            <p class="text-sm text-gray-500">Perbarui status, verifikasi, rating, dan lokasi GPS driver.</p>
        </div>
        <a href="{{ route('admin.drivers.index') }}" class="text-pink-700 hover:underline text-sm font-semibold">&larr; Kembali</a>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
        <form action="{{ route('admin.drivers.update', $driver->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Driver</label>
                <div class="border border-gray-200 rounded-lg px-4 py-2 bg-gray-50">
                    {{ $driver->full_name }} &lt;{{ $driver->email }}&gt; @if($driver->phone) | {{ $driver->phone }} @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
                        <option value="ONLINE" @selected(old('status', $driver->status) == 'ONLINE')>ONLINE</option>
                        <option value="OFFLINE" @selected(old('status', $driver->status) == 'OFFLINE')>OFFLINE</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                    <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $driver->rating) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Verified</label>
                    <label class="flex items-center space-x-2 mt-2">
                        <input type="checkbox" name="is_verified" value="1" @checked(old('is_verified', (bool)$driver->is_verified)) class="rounded border-gray-300 text-pink-700 focus:ring-pink-600">
                        <span class="text-sm text-gray-700">Driver terverifikasi</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Latitude</label>
                    <input type="number" step="any" name="current_lat" value="{{ old('current_lat', $driver->current_lat) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Longitude</label>
                    <input type="number" step="any" name="current_lng" value="{{ old('current_lng', $driver->current_lng) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
                </div>
            </div>

            @if($driver->last_location_at)
                <p class="text-xs text-gray-400">Terakhir lokasi diperbarui: {{ \Carbon\Carbon::parse($driver->last_location_at)->format('d M Y H:i') }}</p>
            @endif

            <button type="submit" class="bg-pink-700 hover:bg-pink-800 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Perubahan
            </button>
        </form>
    </div>
</div>
@endsection
