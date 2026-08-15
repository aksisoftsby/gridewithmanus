@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pembayaran — Transaksi Area Anda</h1>
            <p class="text-sm text-gray-500">Status pembayaran semua order di area coverage Anda (view-only)</p>
        </div>
        <a href="{{ route('kota.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow border border-gray-100 mb-6">
        <form action="{{ route('kota.payments.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status Pembayaran</label>
                <select name="payment_status" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    <option value="">Semua</option>
                    @foreach(["UNPAID","PAID","REFUNDED"] as $ps)
                        <option value="{{ $ps }}" {{ ($status ?? '') === $ps ? 'selected' : '' }}>{{ $ps }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Filter</button>
            @if($status)
                <a href="{{ route('kota.payments.index') }}" class="text-sm text-orange-700 hover:underline py-2">Hapus filter</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Daftar Pembayaran ({{ $payments->total() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Order</th>
                        <th class="px-6 py-3">Tipe</th>
                        <th class="px-6 py-3">Pelanggan</th>
                        <th class="px-6 py-3">Total</th>
                        <th class="px-6 py-3">Metode</th>
                        <th class="px-6 py-3">Status Bayar</th>
                        <th class="px-6 py-3">Status Order</th>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-mono text-xs text-gray-600">{{ $item->order_number }}</td>
                            <td class="px-6 py-3"><span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ ucfirst(strtolower($item->order_type ?? '-')) }}</span></td>
                            <td class="px-6 py-3 font-semibold text-gray-800">{{ $item->customer_name ?? '-' }}</td>
                            <td class="px-6 py-3 font-semibold">Rp{{ number_format($item->total_amount ?? 0, 0, ',', '.') }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->payment_method_snapshot ?? '-' }}</td>
                            <td class="px-6 py-3">{!! statusBadge($item->payment_status ?? 'UNPAID') !!}</td>
                            <td class="px-6 py-3">{!! statusBadge($item->status) !!}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') : '-' }}</td>
                            <td class="px-6 py-3">
                                <a href="{{ route('kota.payments.show', $item->id) }}" class="text-orange-600 hover:text-orange-800 font-medium text-xs"><i class="fa-solid fa-eye mr-1"></i>Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-6 py-6 text-center text-gray-500">Tidak ada transaksi di area Anda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $payments->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
