@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Ride {{ $ride->order_number }}</h1>
            <p class="text-sm text-gray-500">Order antar penumpang di area coverage Anda</p>
        </div>
        <a href="{{ route('kota.rides.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>

    @if(session('success'))
        <div class="bg-pink-50 border border-pink-200 text-pink-900 px-4 py-3 rounded-lg mb-6 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Status & Pembayaran</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Order Number</span><span class="font-mono font-semibold">{{ $ride->order_number }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status</span>{!! statusBadge($ride->status) !!}</div>
                <div class="flex justify-between"><span class="text-gray-500">Service Type</span><span class="font-semibold">{{ ucfirst(strtolower(str_replace('_', ' ', $ride->service_type ?? '-'))) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Kendaraan</span><span>{{ ucfirst(strtolower($ride->vehicle_type ?? '-')) }} (kapasitas {{ $ride->vehicle_capacity ?? '-' }})</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Total</span><span class="font-bold">Rp{{ number_format($ride->total_amount ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>Rp{{ number_format($ride->subtotal ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Biaya Antar</span><span>Rp{{ number_format($ride->delivery_fee ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Diskon</span><span>Rp{{ number_format($ride->discount_amount ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Pembayaran</span><span>{{ $ride->payment_method_snapshot ?? ($ride->is_cod ? 'CASH (COD)' : '-') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status Pembayaran</span>{!! statusBadge($ride->payment_status ?? '-') !!}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Penumpang & Rute</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Penumpang</span><span class="font-semibold">{{ $ride->passenger_name ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Tipe</span><span>{{ $ride->passenger_type === 'OTHER' ? 'Orang lain' : 'Saya sendiri' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">No. Telepon</span><span>{{ $ride->passenger_phone ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Jumlah</span><span>{{ $ride->passenger_count ?? '-' }} orang</span></div>
                <div><span class="text-gray-500">Catatan:</span> {{ $ride->passenger_notes ?: '-' }}</div>
                <hr class="border-gray-100">
                <div class="flex justify-between"><span class="text-gray-500">Pickup</span><span class="text-right max-w-[60%]">{{ $ride->pickup_address ?? 'Lat ' . round($ride->pickup_lat ?? 0, 5) . ', Lng ' . round($ride->pickup_lng ?? 0, 5) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Tujuan</span><span class="text-right max-w-[60%]">{{ $ride->dropoff_address ?? 'Lat ' . round($ride->dropoff_lat ?? 0, 5) . ', Lng ' . round($ride->dropoff_lng ?? 0, 5) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Jarak</span><span>{{ $ride->ride_distance_km ?? $ride->distance_km ?? '-' }} km</span></div>
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

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Timeline</h3>
            </div>
            <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><span class="text-gray-500 text-xs">Dibuat</span><br><span class="font-semibold">{{ $ride->created_at ? \Carbon\Carbon::parse($ride->created_at)->format('d M Y H:i') : '-' }}</span></div>
                <div><span class="text-gray-500 text-xs">Dikonfirmasi</span><br><span class="font-semibold">{{ $ride->confirmed_at ? \Carbon\Carbon::parse($ride->confirmed_at)->format('d M H:i') : '-' }}</span></div>
                <div><span class="text-gray-500 text-xs">Dijemput</span><br><span class="font-semibold">{{ $ride->picked_up_at ? \Carbon\Carbon::parse($ride->picked_up_at)->format('d M H:i') : '-' }}</span></div>
                <div><span class="text-gray-500 text-xs">Dimulai</span><br><span class="font-semibold">{{ $ride->started_at ? \Carbon\Carbon::parse($ride->started_at)->format('d M H:i') : '-' }}</span></div>
                <div><span class="text-gray-500 text-xs">Selesai</span><br><span class="font-semibold">{{ $ride->completed_at ? \Carbon\Carbon::parse($ride->completed_at)->format('d M H:i') : '-' }}</span></div>
                <div><span class="text-gray-500 text-xs">Dibatalkan</span><br><span class="font-semibold">{{ $ride->cancelled_at ? \Carbon\Carbon::parse($ride->cancelled_at)->format('d M H:i') : '-' }}</span></div>
                <div class="col-span-2"><span class="text-gray-500 text-xs">Alasan Batal</span><br><span class="font-semibold">{{ $ride->cancel_reason ?: '-' }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
