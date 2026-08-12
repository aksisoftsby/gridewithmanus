@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Order Detail: {{ $order->order_number }}</h1>
            <p class="text-sm text-gray-500">Placed on {{ $order->created_at }}</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="text-emerald-600 hover:underline text-sm font-semibold">&larr; Back to Orders</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <h3 class="font-bold text-gray-700 text-sm mb-3">Customer Info</h3>
            <p class="text-sm font-semibold text-gray-900">{{ $order->customer_name }}</p>
            <p class="text-sm text-gray-600">{{ $order->customer_phone }}</p>
            <p class="text-xs text-gray-500 mt-2">Address: {{ $order->delivery_address }}</p>
        </div>

        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <h3 class="font-bold text-gray-700 text-sm mb-3">Merchant Info</h3>
            <p class="text-sm font-semibold text-gray-900">{{ $order->merchant_name }}</p>
            <p class="text-xs text-gray-500 mt-2">Recipient: {{ $order->recipient_name }} ({{ $order->recipient_phone }})</p>
        </div>

        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <h3 class="font-bold text-gray-700 text-sm mb-3">Status & Driver</h3>
            <p class="text-sm mb-2">Driver: <span class="font-semibold">{{ $order->driver_name ?? 'Assigned Driver #1' }}</span></p>
            <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" class="mt-2">
                @csrf
                @method('PUT')
                <label class="block text-xs font-semibold text-gray-600 mb-1">Update Status:</label>
                <div class="flex space-x-2">
                    <select name="status" class="px-2 py-1 border rounded text-xs focus:ring-1 focus:ring-emerald-500">
                        <option value="PENDING" @selected($order->status == 'PENDING')>PENDING</option>
                        <option value="PROCESSING" @selected($order->status == 'PROCESSING')>PROCESSING</option>
                        <option value="COMPLETED" @selected($order->status == 'COMPLETED')>COMPLETED</option>
                        <option value="CANCELLED" @selected($order->status == 'CANCELLED')>CANCELLED</option>
                    </select>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded text-xs font-semibold">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Order Items -->
    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Order Items</h3>
        </div>
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                    <th class="px-6 py-3">Item Name</th>
                    <th class="px-6 py-3">Quantity</th>
                    <th class="px-6 py-3">Unit Price</th>
                    <th class="px-6 py-3">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @foreach($items as $item)
                    <tr>
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ $item->product_name }}</td>
                        <td class="px-6 py-4">{{ $item->quantity }}</td>
                        <td class="px-6 py-4">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 font-semibold text-emerald-600">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-6 bg-gray-50 border-t flex justify-end space-y-1 text-right">
            <div>
                <p class="text-sm text-gray-600">Subtotal: <span class="font-semibold">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span></p>
                <p class="text-sm text-gray-600">Delivery Fee: <span class="font-semibold">Rp {{ number_format($order->delivery_fee, 0, ',', '.') }}</span></p>
                <p class="text-sm text-gray-600">Discount: <span class="font-semibold text-red-600">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span></p>
                <p class="text-lg font-bold text-gray-900 mt-2">Total Amount: <span class="text-emerald-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span></p>
            </div>
        </div>
    </div>
</div>
@endsection
