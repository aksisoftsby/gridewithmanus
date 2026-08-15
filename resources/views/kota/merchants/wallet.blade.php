@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Wallet Merchant: {{ $merchant->name }}</h1>
            <p class="text-sm text-gray-500">Transaksi wallet merchant (view-only)</p>
        </div>
        <div class="flex gap-3">
            <span class="bg-pink-50 border border-pink-200 text-pink-800 px-3 py-1.5 rounded-full text-xs font-semibold">Saldo: Rp{{ number_format($wallet->balance ?? 0, 0, ',', '.') }}</span>
            <a href="{{ route('kota.merchants.show', $merchant->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Transaksi Wallet ({{ $transactions->total() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Tipe</th>
                        <th class="px-6 py-3">Arah</th>
                        <th class="px-6 py-3">Amount</th>
                        <th class="px-6 py-3">Saldo Sebelum</th>
                        <th class="px-6 py-3">Saldo Sesudah</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Metode</th>
                        <th class="px-6 py-3">Deskripsi</th>
                        <th class="px-6 py-3">Ref</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-xs text-gray-500">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') : '-' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->type ?? '-' }}</td>
                            <td class="px-6 py-3">{!! statusBadge($item->direction ?? '-') !!}</td>
                            <td class="px-6 py-3 font-semibold {{ ($item->direction ?? '') === 'DEBIT' ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ ($item->direction ?? '') === 'DEBIT' ? '-' : '+' }}Rp{{ number_format($item->amount ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-gray-500">Rp{{ number_format($item->balance_before ?? 0, 0, ',', '.') }}</td>
                            <td class="px-6 py-3 text-gray-500">Rp{{ number_format($item->balance_after ?? 0, 0, ',', '.') }}</td>
                            <td class="px-6 py-3">{!! statusBadge($item->status ?? '-') !!}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->method ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-600 max-w-[200px] truncate" title="{{ $item->description ?? '' }}">{{ $item->description ?? '-' }}</td>
                            <td class="px-6 py-3 font-mono text-xs text-gray-500">{{ $item->reference_no ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-6 py-6 text-center text-gray-500">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $transactions->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
