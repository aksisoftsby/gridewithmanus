@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('kota.members.index', ['type' => 'merchant']) }}" class="text-sm text-pink-600 hover:underline">
            <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke daftar merchant
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Edit Merchant: {{ $merchant->name }}</h1>
        <p class="text-sm text-gray-500">Ubah data merchant sesuai kebutuhan</p>
    </div>

    <form method="POST" action="{{ route('kota.members.merchant.update', $merchant->id) }}" class="bg-white rounded-xl shadow border border-gray-100 p-6 space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Merchant</label>
                <input type="text" name="name" value="{{ $merchant->name }}" required class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tipe</label>
                <select name="type" required class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
                    @foreach (['FOOD' => 'Makanan (FOOD)', 'MART' => 'Minimarket (MART)', 'SHOP' => 'Toko (SHOP)'] as $v => $l)
                        <option value="{{ $v }}" {{ ($merchant->type ?? '') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Pemilik (User)</label>
            <select name="owner_id" required class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
                @foreach ($owners ?? [] as $o)
                    <option value="{{ $o->id }}" {{ ($merchant->owner_id ?? 0) == $o->id ? 'selected' : '' }}>{{ $o->full_name }} ({{ $o->email }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat</label>
            <input type="text" name="address_line" value="{{ $merchant->address_line }}" required class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Kota</label>
                <input type="text" name="city" value="{{ $merchant->city }}" required class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Telepon</label>
                <input type="text" name="phone" value="{{ $merchant->phone }}" class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
                    @foreach (['ACTIVE' => 'Aktif', 'INACTIVE' => 'Nonaktif', 'SUSPENDED' => 'Suspended'] as $v => $l)
                        <option value="{{ $v }}" {{ ($merchant->status ?? 'ACTIVE') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Latitude</label>
                <input type="number" step="any" name="latitude" value="{{ $merchant->latitude }}" class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Longitude</label>
                <input type="number" step="any" name="longitude" value="{{ $merchant->longitude }}" class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi</label>
            <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">{{ $merchant->description }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Perubahan
            </button>
            <a href="{{ route('kota.members.index', ['type' => 'merchant']) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-semibold transition">Batal</a>
        </div>
    </form>
</div>
@endsection
