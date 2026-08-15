@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Orders — Semua Order</h1>
            <p class="text-sm text-gray-500">Semua jenis order (Ride, Delivery, Food) di area coverage Anda</p>
        </div>
        <a href="{{ route('kota.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow border border-gray-100 mb-6">
        <form action="{{ route('kota.orders.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Order number / customer / driver" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none w-72">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tipe</label>
                <select name="type" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    <option value="">Semua Tipe</option>
                    @foreach(['RIDE','DELIVERY','FOOD'] as $t)
                        <option value="{{ $t }}" {{ ($type ?? '') === $t ? 'selected' : '' }}>{{ ucfirst(strtolower($t)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select name="status" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    <option value="">Semua Status</option>
                    @foreach(['SEARCHING_DRIVER','DRIVER_ACCEPTED','DRIVER_ARRIVING','DRIVER_ARRIVED','TRIP_STARTED','CONFIRMED','PICKED_UP','COMPLETED','CANCELED','CANCELLED','REJECTED'] as $s)
                        <option value="{{ $s }}" {{ ($status ?? '') === $s ? 'selected' : '' }}>{{ ucfirst(strtolower(str_replace('_', ' ', $s))) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari
            </button>
            @if($search || $type || $status)
                <a href="{{ route('kota.orders.index') }}" class="text-sm text-orange-700 hover:underline py-2">Hapus pencarian</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Daftar Orders ({{ $orders->total() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Order</th>
                        <th class="px-6 py-3">Tipe</th>
                        <th class="px-6 py-3">Customer</th>
                        <th class="px-6 py-3">Merchant</th>
                        <th class="px-6 py-3">Driver</th>
                        <th class="px-6 py-3">Total</th>
                        <th class="px-6 py-3">Pembayaran</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Dibuat</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-mono text-xs text-gray-600">{{ $item->order_number }}</td>
                            <td class="px-6 py-3"><span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ ucfirst(strtolower($item->order_type ?? '-')) }}</span></td>
                            <td class="px-6 py-3 font-semibold text-gray-800">{{ $item->customer_name ?? '-' }}</td>
                            <td class="px-6 py-3">{{ $item->merchant_name ?? '-' }}</td>
                            <td class="px-6 py-3">{{ $item->driver_name ?? '<span class="text-gray-400">-</span>' }}</td>
                            <td class="px-6 py-3 font-semibold">Rp{{ number_format($item->total_amount ?? 0, 0, ',', '.') }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->payment_method_snapshot ?? '-' }}<br><span class="text-xs">{!! statusBadge($item->payment_status ?? '-') !!}</span></td>
                            <td class="px-6 py-3">{!! statusBadge($item->status) !!}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') : '-' }}</td>
                            <td class="px-6 py-3">
                                <a href="{{ route('kota.orders.show', $item->id) }}" class="text-orange-600 hover:text-orange-800 font-medium text-xs"><i class="fa-solid fa-eye mr-1"></i>Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-6 py-6 text-center text-gray-500">Belum ada order di area Anda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $orders->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
