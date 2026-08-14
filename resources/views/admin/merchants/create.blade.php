@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto mt-10 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Add New Merchant</h2>
        <a href="{{ route('admin.merchants.index') }}" class="text-sm text-pink-700 hover:underline">&larr; Back</a>
    </div>

    <form action="{{ route('admin.merchants.store') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Merchant Name</label>
            <input type="text" name="name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none" placeholder="e.g. Restoran Padang Sederhana">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Owner User</label>
            <select name="owner_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
                @foreach($owners as $owner)
                    <option value="{{ $owner->id }}">{{ $owner->full_name }} ({{ $owner->email }})</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Merchant Type</label>
            <select name="type" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
                <option value="FOOD">FOOD (Restoran / Makanan)</option>
                <option value="MART">MART (Sembako / Minimarket)</option>
                <option value="SHOP">SHOP (Retail / Toko)</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">City</label>
            <input type="text" name="city" required value="Jakarta" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Address Line</label>
            <textarea name="address_line" required rows="2" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none" placeholder="Jl. Sudirman No. 1..."></textarea>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Phone</label>
            <input type="text" name="phone" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none" placeholder="0215551234">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none" placeholder="Brief description of the merchant..."></textarea>
        </div>

        <button type="submit" class="w-full bg-pink-700 hover:bg-pink-800 text-white font-semibold py-2.5 rounded-lg shadow transition">
            Save Merchant
        </button>
    </form>
</div>
@endsection
