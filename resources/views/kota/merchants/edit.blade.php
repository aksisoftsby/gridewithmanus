@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Merchant: {{ $merchant->name }}</h1>
            <p class="text-sm text-gray-500">Update profil, pemilik, kota, dan status merchant</p>
        </div>
        <a href="{{ route('kota.merchants.show', $merchant->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
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

    <form action="{{ route('kota.merchants.update', $merchant->id) }}" method="POST" class="bg-white p-6 rounded-xl shadow border border-gray-100 space-y-4">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Merchant *</label>
                <input type="text" name="name" value="{{ old('name', $merchant->name) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tipe *</label>
                <select name="type" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    <option value="FOOD" {{ $merchant->type === 'FOOD' ? 'selected' : '' }}>FOOD</option>
                    <option value="MART" {{ $merchant->type === 'MART' ? 'selected' : '' }}>MART</option>
                    <option value="SHOP" {{ $merchant->type === 'SHOP' ? 'selected' : '' }}>SHOP</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Pemilik (User) *</label>
                <select name="owner_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" {{ $merchant->owner_id == $owner->id ? 'selected' : '' }}>{{ $owner->full_name }} ({{ $owner->email }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Kota *</label>
                <input type="text" name="city" value="{{ old('city', $merchant->city) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Kota (relasi kota_kabupatens)</label>
                <select name="city_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    <option value="">-- Tidak ada --</option>
                    @foreach($allKota as $kotaList)
                        <option value="{{ $kotaList->id }}" {{ $merchant->city_id == $kotaList->id ? 'selected' : '' }}>{{ $kotaList->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $merchant->phone) }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat *</label>
                <input type="text" name="address_line" value="{{ old('address_line', $merchant->address_line) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">{{ old('description', $merchant->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    <option value="ACTIVE" {{ $merchant->status === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                    <option value="INACTIVE" {{ $merchant->status === 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                    <option value="SUSPENDED" {{ $merchant->status === 'SUSPENDED' ? 'selected' : '' }}>SUSPENDED</option>
                    <option value="CLOSED" {{ $merchant->status === 'CLOSED' ? 'selected' : '' }}>CLOSED</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Lokasi (lat, lng)</label>
                <div class="flex gap-2">
                    <input type="number" step="any" name="latitude" value="{{ old('latitude', $merchant->latitude) }}" placeholder="Latitude" class="w-1/2 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    <input type="number" step="any" name="longitude" value="{{ old('longitude', $merchant->longitude) }}" placeholder="Longitude" class="w-1/2 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_open" id="is_open" value="1" {{ $merchant->is_open ? 'checked' : '' }} class="w-4 h-4 text-pink-600">
            <label for="is_open" class="text-sm font-semibold text-gray-700">Buka (is_open)</label>
        </div>
        <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2 rounded-lg text-sm font-semibold shadow transition">
            <i class="fa-solid fa-save mr-1"></i> Simpan Perubahan
        </button>
    </form>
</div>
@endsection
