@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Order Management</h1>
            <p class="text-sm text-gray-500">Track and update delivery and order statuses.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-pink-700 hover:underline text-sm font-semibold">&larr; Dashboard</a>
    </div>

    @include('admin.partials.search', [
        'route' => 'admin.orders.index',
        'placeholder' => 'Cari nomor order, customer, atau tipe (FOOD/MART/SHOP/DELIVERY)...',
    ])

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap gap-2">
            @foreach([''=>'Semua','PENDING'=>'Pending','PROCESSING'=>'Processing','COMPLETED'=>'Completed','CANCELLED'=>'Cancelled'] as $val => $label)
                <a href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => $val ? $val : null])) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ (request()->query('status') ?? '') === $val ? 'bg-pink-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">{{ $label }}</a>
            @endforeach
        </div>
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                    <th class="px-6 py-3">Order Number</th>
                    <th class="px-6 py-3">Type</th>
                    <th class="px-6 py-3">Customer</th>
                    <th class="px-6 py-3">Merchant</th>
                    <th class="px-6 py-3">Total Amount</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($orders as $order)
                    <tr>
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ $order->order_number }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-sky-100 text-sky-800">
                                {{ $order->order_type ?? 'FOOD' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $order->customer_name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $order->merchant_name }}</td>
                        <td class="px-6 py-4 font-semibold text-pink-700">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full 
                                @if($order->status == 'COMPLETED') bg-pink-100 text-pink-900 
                                @elseif($order->status == 'PROCESSING') bg-blue-100 text-blue-800 
                                @elseif($order->status == 'CANCELLED') bg-red-100 text-red-800 
                                @else bg-blue-100 text-blue-800 @endif">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="text-pink-700 hover:underline font-medium">View Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-6 text-center text-gray-500">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
