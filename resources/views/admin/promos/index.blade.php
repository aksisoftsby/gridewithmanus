@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Promo & Voucher Management</h1>
            <p class="text-sm text-gray-500">Create and manage discounts and promo codes.</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.dashboard') }}" class="text-pink-700 hover:underline text-sm font-semibold self-center">&larr; Dashboard</a>
            <a href="{{ route('admin.promos.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-plus mr-1"></i> Add Promo
            </a>
        </div>
    </div>

    @include('admin.partials.search', ['route' => 'admin.promos.index', 'placeholder' => 'Cari kode atau judul promo...'])

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
                        <th class="px-6 py-3">Period (Start - End)</th>
                        <th class="px-6 py-3">Active</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($promos as $promo)
                        <tr>
                            <td class="px-6 py-4 font-mono font-bold text-pink-700">{{ $promo->code }}</td>
                            <td class="px-6 py-4 text-gray-800">{{ $promo->title }}</td>
                            <td class="px-6 py-4 font-semibold">
                                @if($promo->discount_type == 'PERCENTAGE')
                                    {{ $promo->discount_value }}%
                                @else
                                    Rp {{ number_format($promo->discount_value, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600">Rp {{ number_format($promo->min_purchase, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-xs text-gray-600">
                                <div>From: {{ $promo->starts_at ? \Carbon\Carbon::parse($promo->starts_at)->format('d M Y H:i') : '-' }}</div>
                                <div>To: {{ $promo->ends_at ? \Carbon\Carbon::parse($promo->ends_at)->format('d M Y H:i') : '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($promo->is_active)
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-pink-100 text-pink-900">Active</span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-600">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.promos.edit', $promo->id) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Edit</a> |
                                <form action="{{ route('admin.promos.destroy', $promo->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete promo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-6 text-center text-gray-500">No promos found.</td>
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
                    <input type="text" name="code" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600 uppercase" placeholder="e.g. DISKONBARU">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600" placeholder="e.g. Diskon Spesial Pengguna Baru">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Discount Type</label>
                    <select name="discount_type" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600">
                        <option value="PERCENTAGE">PERCENTAGE (%)</option>
                        <option value="FIXED">FIXED (Rp)</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Discount Value</label>
                    <input type="number" step="0.01" name="discount_value" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600" placeholder="25">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Minimum Purchase (IDR)</label>
                    <input type="number" step="100" name="min_purchase" required value="0" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Start Date & Time</label>
                    <input type="datetime-local" name="starts_at" required value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">End Date & Time</label>
                    <input type="datetime-local" name="ends_at" required value="{{ now()->addDays(30)->format('Y-m-d\TH:i') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-pink-600">
                </div>
                <div class="mb-6">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-pink-700 focus:ring-pink-600">
                        <span class="text-sm font-semibold text-gray-700">Promo aktif</span>
                    </label>
                </div>
                <button type="submit" class="w-full bg-pink-700 hover:bg-pink-800 text-white font-semibold py-2.5 rounded-lg shadow transition">
                    Save Promo
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
