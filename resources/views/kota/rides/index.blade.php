@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rides — Antar Penumpang</h1>
            <p class="text-sm text-gray-500">Monitoring order antar penumpang di area coverage Anda</p>
        </div>
        <a href="{{ route('kota.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="bg-pink-50 border border-pink-200 text-pink-900 px-4 py-3 rounded-lg mb-6 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white p-4 rounded-xl shadow border border-gray-100 mb-6">
        <form action="{{ route('kota.rides.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Order number / customer / driver" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none w-72">
            </div>
            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari
            </button>
            @if($search)
                <a href="{{ route('kota.rides.index') }}" class="text-sm text-orange-700 hover:underline py-2">Hapus pencarian</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Daftar Rides ({{ $rides->total() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Order</th>
                        <th class="px-6 py-3">Customer</th>
                        <th class="px-6 py-3">Driver</th>
                        <th class="px-6 py-3">Kendaraan</th>
                        <th class="px-6 py-3">Penumpang</th>
                        <th class="px-6 py-3">Total</th>
                        <th class="px-6 py-3">Pembayaran</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Dibuat</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rides as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-mono text-xs text-gray-600">{{ $item->order_number }}</td>
                            <td class="px-6 py-3 font-semibold text-gray-800">{{ $item->customer_name ?? '-' }}<br><span class="text-xs text-gray-500 font-normal">{{ $item->passenger_name ?? '' }}</span></td>
                            <td class="px-6 py-3">{{ $item->driver_name ?? '<span class="text-gray-400">Belum ada</span>' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ ucfirst(strtolower(str_replace('_', ' ', $item->service_type ?? $item->vehicle_type ?? '-'))) }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->passenger_count ?? '-' }} pax</td>
                            <td class="px-6 py-3 font-semibold">Rp{{ number_format($item->total_amount ?? 0, 0, ',', '.') }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $item->payment_method_snapshot ?? ($item->is_cod ? 'CASH' : '-') }}</td>
                            <td class="px-6 py-3">{!! \Illuminate\Support\Str::of($item->status)->upper()->pipe(function($s){ return $s->isNotEmpty() ? statusBadge((string) $s) : '-'; })->__toString() !!}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') : '-' }}</td>
                            <td class="px-6 py-3">
                                <a href="{{ route('kota.rides.show', $item->id) }}" class="text-orange-600 hover:text-orange-800 font-medium text-xs"><i class="fa-solid fa-eye mr-1"></i>Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-6 py-6 text-center text-gray-500">Belum ada ride di area Anda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $rides->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
