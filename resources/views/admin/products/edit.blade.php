@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Product / Menu Item</h1>
            <p class="text-sm text-gray-500">Update product information ({{ $product->name }})</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="text-pink-700 hover:underline text-sm font-semibold">&larr; Back to Products</a>
    </div>

    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
        <form action="{{ route('admin.products.update', $product->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Merchant / Toko</label>
                    <select name="merchant_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
                        @foreach($merchants as $merchant)
                            <option value="{{ $merchant->id }}" @selected($merchant->id == $product->merchant_id)>{{ $merchant->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (Rp)</label>
                    <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">{{ old('description', $product->description) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="is_available" value="1" @checked($product->is_available) class="rounded border-gray-300 text-pink-700 focus:ring-pink-600">
                        <span class="text-sm font-medium text-gray-700">Available (Ditampilkan di katalog)</span>
                    </label>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.products.index') }}" class="px-4 py-2 border rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-pink-700 hover:bg-pink-800 text-white rounded-lg text-sm font-semibold shadow transition">Update Product</button>
            </div>
        </form>
    </div>
</div>
@endsection
