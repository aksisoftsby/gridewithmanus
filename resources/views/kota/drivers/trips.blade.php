@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Riwayat Trip Driver</h1>
            <p class="text-sm text-gray-500">Semua order yang ditangani driver</p>
        </div>
        <a href="{{ route('kota.drivers.show', $driver->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Daftar Trip ({{ $trips->total() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Order</th>
                        <th class="px-6 py-3">Tipe</th>
                        <th class="px-6 py-3">Penerima</th>
                        <th class="px-6 py-3">Alamat</th>
                        <th class="px-6 py-3">Total</th>
                        <th class="px-6 py-3">Pembayaran</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($trips as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-mono text-xs text-gray-600">{{ $item->order_number }}</td>
                            <td class="px-6 py-3"><span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ ucfirst(strtolower($item->order_type ?? '-')) }}</span></td>
                            <td class="px-6 py-3 font-semibold text-gray-800">{{ $item->recipient_name ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-600 max-w-[220px] truncate" title="{{ $item->delivery_address ?? '' }}">{{ $item->delivery_address ?? '-' }}</td>
                            <td class="px-6 py-3 font-semibold">Rp{{ number_format($item->total_amount ?? 0, 0, ',', '.') }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->payment_method_snapshot ?? '-' }}</td>
                            <td class="px-6 py-3">{!! statusBadge($item->status) !!}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-6 text-center text-gray-500">Belum ada trip.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $trips->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
