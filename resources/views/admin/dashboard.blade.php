@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Admin Control Dashboard</h1>
            <p class="text-sm text-gray-500">Manage users, merchants, menu items, orders, drivers, and promotions based on schema.sql</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.merchants.index') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-store mr-1"></i> Manage Merchants
            </a>
            <a href="{{ route('admin.products.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-utensils mr-1"></i> Manage Products
            </a>
            <a href="{{ route('admin.orders.index') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-receipt mr-1"></i> Manage Orders
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-xs font-semibold uppercase">Total Users</div>
            <div class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['users'] }}</div>
            <a href="{{ route('admin.users.index') }}" class="text-xs text-emerald-600 hover:underline mt-2 inline-block">View details &rarr;</a>
        </div>
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-xs font-semibold uppercase">Merchants</div>
            <div class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['merchants'] }}</div>
            <a href="{{ route('admin.merchants.index') }}" class="text-xs text-emerald-600 hover:underline mt-2 inline-block">View details &rarr;</a>
        </div>
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-xs font-semibold uppercase">Total Orders</div>
            <div class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['orders'] }}</div>
            <a href="{{ route('admin.orders.index') }}" class="text-xs text-emerald-600 hover:underline mt-2 inline-block">View details &rarr;</a>
        </div>
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-xs font-semibold uppercase">Completed Revenue</div>
            <div class="text-xl font-bold text-emerald-600 mt-2">Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-xs font-semibold uppercase">Active Drivers</div>
            <div class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['drivers'] }}</div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <div class="text-gray-500 text-xs font-semibold uppercase">Promos Active</div>
            <div class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['promos'] }}</div>
            <a href="{{ route('admin.promos.index') }}" class="text-xs text-emerald-600 hover:underline mt-2 inline-block">View details &rarr;</a>
        </div>
    </div>

    <!-- Quick Navigation Bar -->
    <div class="bg-white p-4 rounded-xl shadow border border-gray-100 mb-8 flex flex-wrap gap-4 items-center justify-between">
        <span class="font-semibold text-gray-700">Quick Navigation:</span>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.users.index') }}" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded text-sm font-medium text-gray-700">Users</a>
            <a href="{{ route('admin.merchants.index') }}" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded text-sm font-medium text-gray-700">Merchants</a>
            <a href="{{ route('admin.products.index') }}" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded text-sm font-medium text-gray-700">Products / Menu</a>
            <a href="{{ route('admin.orders.index') }}" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded text-sm font-medium text-gray-700">Orders</a>
            <a href="{{ route('admin.promos.index') }}" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded text-sm font-medium text-gray-700">Promos</a>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-lg">Recent Orders</h3>
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-emerald-600 hover:underline">View All &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Order Number</th>
                        <th class="px-6 py-3">Customer</th>
                        <th class="px-6 py-3">Merchant</th>
                        <th class="px-6 py-3">Total Amount</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($recentOrders as $order)
                        <tr>
                            <td class="px-6 py-4 font-semibold text-gray-800">{{ $order->order_number }}</td>
                            <td class="px-6 py-4">{{ $order->customer_name }}</td>
                            <td class="px-6 py-4">{{ $order->merchant_name }}</td>
                            <td class="px-6 py-4 font-semibold text-emerald-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full 
                                    @if($order->status == 'COMPLETED') bg-emerald-100 text-emerald-800 
                                    @elseif($order->status == 'PROCESSING') bg-blue-100 text-blue-800 
                                    @elseif($order->status == 'CANCELLED') bg-red-100 text-red-800 
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="text-emerald-600 hover:text-emerald-800 font-medium">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-6 text-center text-gray-500">No recent orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
