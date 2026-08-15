@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Semua Transaksi Wallet</h1>
            <p class="text-sm text-gray-500">Seluruh transaksi GrSaldo user di area coverage Anda (view-only)</p>
        </div>
        <a href="{{ route('kota.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow border border-gray-100 mb-6">
        <form action="{{ route('kota.wallet.transactions.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Deskripsi / ref no / nama" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none w-72">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tipe</label>
                <input type="text" name="type" value="{{ $type ?? '' }}" placeholder="mis. TOPUP" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Arah</label>
                <select name="direction" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    <option value="">Semua</option>
                    @foreach(["CREDIT","DEBIT"] as $d)
                        <option value="{{ $d }}" {{ ($direction ?? '') === $d ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Filter
            </button>
            @if($search || $type || $direction)
                <a href="{{ route('kota.wallet.transactions.index') }}" class="text-sm text-orange-700 hover:underline py-2">Hapus filter</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Transaksi ({{ $transactions->total() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">User</th>
                        <th class="px-6 py-3">Tipe</th>
                        <th class="px-6 py-3">Arah</th>
                        <th class="px-6 py-3">Amount</th>
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
                            <td class="px-6 py-3 font-semibold text-gray-800">{{ $item->full_name ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->type ?? '-' }}</td>
                            <td class="px-6 py-3">{!! statusBadge($item->direction ?? '-') !!}</td>
                            <td class="px-6 py-3 font-semibold {{ ($item->direction ?? '') === 'DEBIT' ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ ($item->direction ?? '') === 'DEBIT' ? '-' : '+' }}Rp{{ number_format($item->amount ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3">{!! statusBadge($item->status ?? '-') !!}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->method ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-600 max-w-[220px] truncate" title="{{ $item->description ?? '' }}">{{ $item->description ?? '-' }}</td>
                            <td class="px-6 py-3 font-mono text-xs text-gray-500">{{ $item->reference_no ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-6 py-6 text-center text-gray-500">Tidak ada transaksi.</td></tr>
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
