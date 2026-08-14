@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Promo / Voucher</h1>
            <p class="text-sm text-gray-500">Perbarui informasi promo ({{ $promo->code }})</p>
        </div>
        <a href="{{ route('admin.promos.index') }}" class="text-pink-700 hover:underline text-sm font-semibold">&larr; Back to Promos</a>
    </div>

    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
        <form action="{{ route('admin.promos.update', $promo->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Promo Code</label>
                    <input type="text" name="code" value="{{ old('code', $promo->code) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" value="{{ old('title', $promo->title) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type</label>
                    <select name="discount_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
                        <option value="PERCENTAGE" @selected($promo->discount_type == 'PERCENTAGE')>Percentage (%)</option>
                        <option value="FIXED" @selected($promo->discount_type == 'FIXED')>Fixed (Rp)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount Value</label>
                    <input type="number" step="0.01" name="discount_value" value="{{ old('discount_value', $promo->discount_value) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min Purchase (Rp)</label>
                    <input type="number" step="0.01" name="min_purchase" value="{{ old('min_purchase', $promo->min_purchase) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Active</label>
                    <div class="flex items-center space-x-2 mt-2">
                        <input type="checkbox" name="is_active" value="1" @checked($promo->is_active) class="rounded border-gray-300 text-pink-700 focus:ring-pink-600">
                        <span class="text-sm text-gray-700">Promo aktif</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date & Time</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', \Carbon\Carbon::parse($promo->starts_at)->format('Y-m-d\TH:i')) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date & Time</label>
                    <input type="datetime-local" name="ends_at" value="{{ old('ends_at', \Carbon\Carbon::parse($promo->ends_at)->format('Y-m-d\TH:i')) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 focus:outline-none">
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.promos.index') }}" class="px-4 py-2 border rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-pink-700 hover:bg-pink-800 text-white rounded-lg text-sm font-semibold shadow transition">Update Promo</button>
            </div>
        </form>
    </div>
</div>
@endsection
