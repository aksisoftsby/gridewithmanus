@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Promo & Voucher Management</h1>
            <p class="text-sm text-gray-500">Create and manage discounts and promo codes.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-emerald-600 hover:underline text-sm font-semibold">&larr; Dashboard</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Promos Table -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Code</th>
                        <th class="px-6 py-3">Title</th>
                        <th class="px-6 py-3">Discount</th>
                        <th class="px-6 py-3">Min Purchase</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($promos as $promo)
                        <tr>
                            <td class="px-6 py-4 font-mono font-bold text-emerald-600">{{ $promo->code }}</td>
                            <td class="px-6 py-4 text-gray-800">{{ $promo->title }}</td>
                            <td class="px-6 py-4 font-semibold">
                                @if($promo->discount_type == 'PERCENTAGE')
                                    {{ $promo->discount_value }}%
                                @else
                                    Rp {{ number_format($promo->discount_value, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600">Rp {{ number_format($promo->min_purchase, 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                <form action="{{ route('admin.promos.destroy', $promo->id) }}" method="POST" onsubmit="return confirm('Delete promo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-6 text-center text-gray-500">No promos found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Create Promo Form -->
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100 h-fit">
            <h3 class="font-bold text-gray-800 text-lg mb-4">Create New Promo</h3>
            <form action="{{ route('admin.promos.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Promo Code</label>
                    <input type="text" name="code" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 uppercase" placeholder="e.g. DISKONBARU">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500" placeholder="e.g. Diskon Spesial Pengguna Baru">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Discount Type</label>
                    <select name="discount_type" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500">
                        <option value="PERCENTAGE">PERCENTAGE (%)</option>
                        <option value="FIXED">FIXED (Rp)</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Discount Value</label>
                    <input type="number" step="0.01" name="discount_value" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500" placeholder="25">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Minimum Purchase (IDR)</label>
                    <input type="number" step="100" name="min_purchase" required value="0" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500">
                </div>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-lg shadow transition">
                    Save Promo
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
