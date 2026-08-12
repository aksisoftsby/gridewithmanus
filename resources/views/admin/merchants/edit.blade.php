@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Merchant: {{ $merchant->name }}</h1>
            <p class="text-sm text-gray-500">Perbarui data merchant termasuk lokasi GPS.</p>
        </div>
        <a href="{{ route('admin.merchants.index') }}" class="text-emerald-600 hover:underline text-sm font-semibold">&larr; Kembali</a>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
        <form action="{{ route('admin.merchants.update', $merchant->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Merchant Name</label>
                <input type="text" name="name" value="{{ old('name', $merchant->name) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Owner</label>
                    <select name="owner_id" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        @foreach($owners as $owner)
                            <option value="{{ $owner->id }}" @selected(old('owner_id', $merchant->owner_id) == $owner->id)>{{ $owner->full_name }} ({{ $owner->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        @foreach(['FOOD','MART','SHOP'] as $type)
                            <option value="{{ $type }}" @selected(old('type', $merchant->type) == $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">{{ old('description', $merchant->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address Line</label>
                <input type="text" name="address_line" value="{{ old('address_line', $merchant->address_line) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" name="city" value="{{ old('city', $merchant->city) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $merchant->phone) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        @foreach(['ACTIVE','INACTIVE','SUSPENDED'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $merchant->status) == $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Latitude <span class="text-gray-400">(lokasi GPS)</span></label>
                    <input type="number" step="any" name="latitude" value="{{ old('latitude', $merchant->latitude) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Longitude <span class="text-gray-400">(lokasi GPS)</span></label>
                    <input type="number" step="any" name="longitude" value="{{ old('longitude', $merchant->longitude) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Perubahan
            </button>
        </form>
    </div>
</div>
@endsection
