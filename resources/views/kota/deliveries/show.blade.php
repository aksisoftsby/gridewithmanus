@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Delivery {{ $delivery->order_number }}</h1>
            <p class="text-sm text-gray-500">Order kirim barang di area coverage Anda</p>
        </div>
        <a href="{{ route('kota.deliveries.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Status & Pembayaran</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Order Number</span><span class="font-mono font-semibold">{{ $delivery->order_number }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status</span>{!! statusBadge($delivery->status) !!}</div>
                <div class="flex justify-between"><span class="text-gray-500">Total</span><span class="font-bold">Rp{{ number_format($delivery->total_amount ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Pembayaran</span><span>{{ $delivery->payment_method_snapshot ?? ($delivery->is_cod ? 'CASH (COD)' : '-') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status Pembayaran</span>{!! statusBadge($delivery->payment_status ?? '-') !!}</div>
                <div class="flex justify-between"><span class="text-gray-500">Jarak</span><span>{{ $delivery->ride_distance_km ?? $delivery->distance_km ?? '-' }} km</span></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Pengiriman & Penerima</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Pengirim</span><span class="font-semibold">{{ $customer->full_name ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Penerima</span><span class="font-semibold">{{ $delivery->recipient_name ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Telp Penerima</span><span>{{ $delivery->recipient_phone ?? '-' }}</span></div>
                <div><span class="text-gray-500">Alamat Pengiriman:</span> {{ $delivery->delivery_address ?? '-' }}</div>
                <hr class="border-gray-100">
                <div class="flex justify-between"><span class="text-gray-500">Pickup</span><span class="text-right max-w-[60%]">{{ $delivery->pickup_address ?? 'Lat ' . round($delivery->pickup_lat ?? 0, 5) . ', Lng ' . round($delivery->pickup_lng ?? 0, 5) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Tujuan</span><span class="text-right max-w-[60%]">{{ $delivery->dropoff_address ?? 'Lat ' . round($delivery->dropoff_lat ?? 0, 5) . ', Lng ' . round($delivery->dropoff_lng ?? 0, 5) }}</span></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Driver</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                @if($driver)
                    <div class="flex justify-between"><span class="text-gray-500">Nama</span><span class="font-semibold">{{ $driver->full_name }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Email</span><span>{{ $driver->email ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Phone</span><span>{{ $driver->phone ?? '-' }}</span></div>
                @else
                    <p class="text-gray-500">Belum ada driver yang ditugaskan.</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Timeline</h3>
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><span class="text-gray-500 text-xs">Dibuat</span><br><span class="font-semibold">{{ $delivery->created_at ? \Carbon\Carbon::parse($delivery->created_at)->format('d M Y H:i') : '-' }}</span></div>
                <div><span class="text-gray-500 text-xs">Dikonfirmasi</span><br><span class="font-semibold">{{ $delivery->confirmed_at ? \Carbon\Carbon::parse($delivery->confirmed_at)->format('d M H:i') : '-' }}</span></div>
                <div><span class="text-gray-500 text-xs">Selesai</span><br><span class="font-semibold">{{ $delivery->completed_at ? \Carbon\Carbon::parse($delivery->completed_at)->format('d M H:i') : '-' }}</span></div>
                <div><span class="text-gray-500 text-xs">Dibatalkan</span><br><span class="font-semibold">{{ $delivery->cancelled_at ? \Carbon\Carbon::parse($delivery->cancelled_at)->format('d M H:i') : '-' }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
