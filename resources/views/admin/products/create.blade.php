@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto mt-10 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Add Product / Menu Item</h2>
        <a href="{{ route('admin.products.index') }}" class="text-sm text-pink-700 hover:underline">&larr; Back</a>
    </div>

    <form action="{{ route('admin.products.store') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Merchant</label>
            <select name="merchant_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
                @foreach($merchants as $merchant)
                    <option value="{{ $merchant->id }}">{{ $merchant->name }} ({{ $merchant->type }})</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Product Name</label>
            <input type="text" name="name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none" placeholder="e.g. Nasi Goreng Spesial">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Price (IDR)</label>
            <input type="number" step="100" name="price" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none" placeholder="35000">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none" placeholder="Product ingredients or description..."></textarea>
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg shadow transition">
            Save Product
        </button>
    </form>
</div>
@endsection
