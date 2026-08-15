@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Order {{ $order->order_number }}</h1>
            <p class="text-sm text-gray-500">Tipe: <span class="font-semibold text-blue-700">{{ ucfirst(strtolower($order->order_type ?? '-')) }}</span></p>
        </div>
        <a href="{{ route('kota.orders.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Status & Pembayaran</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Order Number</span><span class="font-mono font-semibold">{{ $order->order_number }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status</span>{!! statusBadge($order->status) !!}</div>
                <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>Rp{{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Biaya Antar</span><span>Rp{{ number_format($order->delivery_fee ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Diskon</span><span>Rp{{ number_format($order->discount_amount ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Total</span><span class="font-bold">Rp{{ number_format($order->total_amount ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Pembayaran</span><span>{{ $order->payment_method_snapshot ?? ($order->is_cod ? 'CASH (COD)' : '-') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status Pembayaran</span>{!! statusBadge($order->payment_status ?? '-') !!}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Alamat & Penerima</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div><span class="text-gray-500">Alamat Pengiriman:</span><br>{{ $order->delivery_address ?? '-' }}</div>
                <div class="flex justify-between"><span class="text-gray-500">Penerima</span><span class="font-semibold">{{ $order->recipient_name ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Telp Penerima</span><span>{{ $order->recipient_phone ?? '-' }}</span></div>
                <hr class="border-gray-100">
                <div class="flex justify-between"><span class="text-gray-500">Pickup</span><span class="text-right max-w-[60%]">{{ $order->pickup_address ?? ($order->pickup_lat ? 'Lat ' . round($order->pickup_lat, 5) . ', Lng ' . round($order->pickup_lng, 5) : '-') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Tujuan</span><span class="text-right max-w-[60%]">{{ $order->dropoff_address ?? ($order->dropoff_lat ? 'Lat ' . round($order->dropoff_lat, 5) . ', Lng ' . round($order->dropoff_lng, 5) : '-') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Catatan</span><span>{{ $order->note ?: '-' }}</span></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Customer</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Nama</span><span class="font-semibold">{{ $customer->full_name ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Email</span><span>{{ $customer->email ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Phone</span><span>{{ $customer->phone ?? '-' }}</span></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Merchant & Driver</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Merchant</span><span class="font-semibold">{{ $merchant->name ?? '-' }}</span></div>
                @if($driver)
                    <div class="flex justify-between"><span class="text-gray-500">Driver</span><span class="font-semibold">{{ $driver->full_name }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Phone Driver</span><span>{{ $driver->phone ?? '-' }}</span></div>
                @else
                    <p class="text-gray-500">Belum ada driver yang ditugaskan.</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Timeline</h3>
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><span class="text-gray-500 text-xs">Dibuat</span><br><span class="font-semibold">{{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i') : '-' }}</span></div>
                <div><span class="text-gray-500 text-xs">Dikonfirmasi</span><br><span class="font-semibold">{{ $order->confirmed_at ? \Carbon\Carbon::parse($order->confirmed_at)->format('d M H:i') : '-' }}</span></div>
                <div><span class="text-gray-500 text-xs">Dijemput</span><br><span class="font-semibold">{{ $order->picked_up_at ? \Carbon\Carbon::parse($order->picked_up_at)->format('d M H:i') : '-' }}</span></div>
                <div><span class="text-gray-500 text-xs">Selesai</span><br><span class="font-semibold">{{ $order->completed_at ? \Carbon\Carbon::parse($order->completed_at)->format('d M H:i') : '-' }}</span></div>
                <div><span class="text-gray-500 text-xs">Dibatalkan</span><br><span class="font-semibold">{{ $order->cancelled_at ? \Carbon\Carbon::parse($order->cancelled_at)->format('d M H:i') : '-' }}</span></div>
                <div class="col-span-2"><span class="text-gray-500 text-xs">Alasan Batal</span><br><span class="font-semibold">{{ $order->cancel_reason ?: '-' }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
