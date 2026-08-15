@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Merchant: {{ $merchant->name }}</h1>
            <p class="text-sm text-gray-500">Profil, earning, dan order merchant di area Anda</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('kota.merchants.edit', $merchant->id) }}" class="bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
            </a>
            <a href="{{ route('kota.merchants.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-pink-50 border border-pink-200 text-pink-900 px-4 py-3 rounded-lg mb-6 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Profil Merchant</h3>
                <form action="{{ route('kota.merchants.status', $merchant->id) }}" method="POST" class="flex gap-2">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="px-2 py-1 border rounded text-xs font-semibold">
                        <option value="ACTIVE" {{ $merchant->status === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                        <option value="INACTIVE" {{ $merchant->status === 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                        <option value="SUSPENDED" {{ $merchant->status === 'SUSPENDED' ? 'selected' : '' }}>SUSPENDED</option>
                        <option value="CLOSED" {{ $merchant->status === 'CLOSED' ? 'selected' : '' }}>CLOSED</option>
                    </select>
                    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-xs font-semibold">Set</button>
                </form>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">ID Merchant</span><span class="font-mono">{{ $merchant->id }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Nama</span><span class="font-semibold">{{ $merchant->name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Tipe</span>{!! statusBadge($merchant->type ?? '-') !!}</div>
                <div class="flex justify-between"><span class="text-gray-500">Pemilik</span><span>{{ $owner->full_name ?? '-' }} <span class="text-xs text-gray-400">({{ $owner->email ?? '' }})</span></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Alamat</span><span class="text-right max-w-[60%]">{{ $merchant->address_line ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Kota</span><span>{{ $merchant->city ?? '-' }} {{ $merchant->city_id ? '(id ' . $merchant->city_id . ')' : '' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Phone</span><span>{{ $merchant->phone ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status</span>{!! statusBadge($merchant->status) !!}</div>
                <div class="flex justify-between"><span class="text-gray-500">Lokasi (lat,lng)</span><span>{{ $merchant->latitude && $merchant->longitude ? round($merchant->latitude,5).',' . round($merchant->longitude,5) : '-' }}</span></div>
                <div class="flex gap-3 pt-2">
                    <a href="{{ route('kota.merchants.wallet', $merchant->id) }}" class="text-xs text-pink-700 hover:underline">Lihat wallet merchant &rarr;</a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-pink-100 bg-pink-50 overflow-hidden">
            <div class="px-6 py-4 border-b border-pink-100 bg-pink-100">
                <h3 class="font-bold text-pink-900 text-sm uppercase tracking-wide">GrSaldo Merchant</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-600">Saldo</span><span class="text-xl font-bold text-pink-700">Rp{{ number_format($wallet->balance ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Total Earning (SUCCESS)</span><span class="font-semibold">Rp{{ number_format($earningSummary->total_earning ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Transaksi Earning</span><span>{{ $earningSummary->total_transactions ?? 0 }}</span></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Order Terbaru (10)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                            <th class="px-6 py-3">Order</th>
                            <th class="px-6 py-3">Pelanggan</th>
                            <th class="px-6 py-3">Driver</th>
                            <th class="px-6 py-3">Tipe</th>
                            <th class="px-6 py-3">Total</th>
                            <th class="px-6 py-3">Pembayaran</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentOrders as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-mono text-xs text-gray-600">{{ $item->order_number }}</td>
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $item->recipient_name ?? '-' }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ $item->driver_id ? 'Driver #' . $item->driver_id : '-' }}</td>
                                <td class="px-6 py-3"><span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ ucfirst(strtolower($item->order_type ?? '-')) }}</span></td>
                                <td class="px-6 py-3 font-semibold">Rp{{ number_format($item->total_amount ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ $item->payment_method_snapshot ?? '-' }}</td>
                                <td class="px-6 py-3">{!! statusBadge($item->status) !!}</td>
                                <td class="px-6 py-3 text-xs text-gray-500">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-6 text-center text-gray-500">Belum ada order.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
