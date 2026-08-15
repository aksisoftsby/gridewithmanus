@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Customer: {{ $customer->full_name }}</h1>
            <p class="text-sm text-gray-500">Profil, wallet, dan riwayat order customer di area Anda</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('kota.customers.edit', $customer->id) }}" class="bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
            </a>
            <a href="{{ route('kota.customers.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Profil</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">ID</span><span class="font-mono">{{ $customer->id }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Nama</span><span class="font-semibold">{{ $customer->full_name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Email</span><span>{{ $customer->email }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Phone</span><span>{{ $customer->phone ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status</span>{!! statusBadge($customer->status) !!}</div>
                <div class="flex justify-between"><span class="text-gray-500">Terdaftar</span><span>{{ $customer->created_at ? \Carbon\Carbon::parse($customer->created_at)->format('d M Y H:i') : '-' }}</span></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-pink-100 bg-pink-50 overflow-hidden">
            <div class="px-6 py-4 border-b border-pink-100 bg-pink-100">
                <h3 class="font-bold text-pink-900 text-sm uppercase tracking-wide">GrSaldo Wallet</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-600">Saldo</span><span class="text-xl font-bold text-pink-700">Rp{{ number_format($wallet->balance ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Total Masuk</span><span>Rp{{ number_format($walletSummary->total_in ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Total Keluar</span><span>Rp{{ number_format($walletSummary->total_out ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Total Transaksi</span><span>{{ $walletSummary->total_transactions ?? 0 }}</span></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Order Terbaru (10)</h3>
                <a href="{{ route('kota.orders.index', ['search' => $customer->email]) }}" class="text-xs text-orange-600 hover:underline">Lihat semua order area &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                            <th class="px-6 py-3">Order</th>
                            <th class="px-6 py-3">Tipe</th>
                            <th class="px-6 py-3">Total</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($orders as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-mono text-xs">{{ $item->order_number }}</td>
                                <td class="px-6 py-3"><span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ ucfirst(strtolower($item->order_type ?? '-')) }}</span></td>
                                <td class="px-6 py-3 font-semibold">Rp{{ number_format($item->total_amount ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-3">{!! statusBadge($item->status) !!}</td>
                                <td class="px-6 py-3 text-xs text-gray-500">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-6 text-center text-gray-500">Belum ada order.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
