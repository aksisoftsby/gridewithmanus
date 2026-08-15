@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Pembayaran: {{ $order->order_number }}</h1>
            <p class="text-sm text-gray-500">Informasi pembayaran order (view-only)</p>
        </div>
        <a href="{{ route('kota.payments.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Info Pembayaran</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Order Number</span><span class="font-mono">{{ $order->order_number }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Tipe</span>{!! statusBadge($order->order_type ?? '-') !!}</div>
                <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span class="font-semibold">Rp{{ number_format($order->subtotal ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Biaya Kirim/Service</span><span class="font-semibold">Rp{{ number_format($order->delivery_fee ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Total</span><span class="text-xl font-bold text-pink-700">Rp{{ number_format($order->total_amount ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Metode</span>{!! statusBadge($order->payment_method_snapshot ?? '-') !!}</div>
                <div class="flex justify-between"><span class="text-gray-500">COD</span><span>{{ $order->is_cod ? 'Ya' : 'Tidak' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status Pembayaran</span>{!! statusBadge($order->payment_status ?? 'UNPAID') !!}</div>
                <div class="flex justify-between"><span class="text-gray-500">Status Order</span>{!! statusBadge($order->status) !!}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Pelanggan & Detail</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Pelanggan</span><span class="font-semibold">{{ $customer->full_name ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Email</span><span>{{ $customer->email ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Phone Pelanggan</span><span>{{ $customer->phone ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Penerima</span><span>{{ $order->recipient_name ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Phone Penerima</span><span>{{ $order->recipient_phone ?? '-' }}</span></div>
                <div class="flex justify-between items-start"><span class="text-gray-500">Alamat</span><span class="text-right max-w-[60%]">{{ $order->delivery_address ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Driver</span><span>{{ $order->driver_id ? 'Driver #' . $order->driver_id : '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Merchant</span><span>{{ $order->merchant_id ? 'Merchant #' . $order->merchant_id : '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Dibuat</span><span>{{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d M Y H:i:s') : '-' }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
