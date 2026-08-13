@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Products & Menu Management</h1>
            <p class="text-sm text-gray-500">Manage food menus and mart/shop products across merchants.</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.dashboard') }}" class="text-purple-700 hover:underline text-sm font-semibold self-center">&larr; Dashboard</a>
            <a href="{{ route('admin.products.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-plus mr-1"></i> Add Product
            </a>
        </div>
    </div>

    @include('admin.partials.search', ['route' => 'admin.products.index', 'placeholder' => 'Cari produk atau merchant...'])

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                    <th class="px-6 py-3">Product Name</th>
                    <th class="px-6 py-3">Merchant</th>
                    <th class="px-6 py-3">Price</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($products as $product)
                    <tr>
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ $product->name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $product->merchant_name }}</td>
                        <td class="px-6 py-4 font-semibold text-purple-700">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-900">
                                {{ $product->is_available ? 'Available' : 'Unavailable' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.products.edit', $product->id) }}" class="text-purple-700 hover:text-purple-900 font-medium text-xs"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit</a>
                                <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-6 text-center text-gray-500">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection
