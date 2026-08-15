@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Driver: {{ $user->full_name }}</h1>
            <p class="text-sm text-gray-500">Profil, kendaraan, earning, dan trip driver di area Anda</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('kota.drivers.edit', $driver->id) }}" class="bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
            </a>
            <a href="{{ route('kota.drivers.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Profil Driver</h3>
                <form action="{{ route('kota.drivers.status', $driver->id) }}" method="POST" class="flex gap-2">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="px-2 py-1 border rounded text-xs font-semibold {{ $driver->status === 'ONLINE' ? 'text-emerald-700' : 'text-gray-600' }}">
                        <option value="ONLINE" {{ $driver->status === 'ONLINE' ? 'selected' : '' }}>ONLINE</option>
                        <option value="OFFLINE" {{ $driver->status === 'OFFLINE' ? 'selected' : '' }}>OFFLINE</option>
                    </select>
                    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-xs font-semibold">Set</button>
                </form>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">ID Driver</span><span class="font-mono">{{ $driver->id }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">User ID</span><span class="font-mono">{{ $driver->user_id }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Nama</span><span class="font-semibold">{{ $user->full_name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Email</span><span>{{ $user->email }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Phone</span><span>{{ $user->phone ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Status</span>{!! statusBadge($driver->status) !!}</div>
                <div class="flex justify-between"><span class="text-gray-500">Verifikasi</span>
                    @if($driver->is_verified)<span class="text-emerald-600 font-semibold"><i class="fa-solid fa-circle-check mr-1"></i>Verified</span>
                    @else <span class="text-gray-500">Belum</span> @endif
                </div>
                <div class="flex justify-between"><span class="text-gray-500">Rating</span>
                    @if($driver->rating)<span><span class="text-yellow-500"><i class="fa-solid fa-star mr-1"></i></span>{{ number_format($driver->rating, 1) }}</span>
                    @else <span>-</span> @endif
                </div>
                <div class="flex justify-between"><span class="text-gray-500">Total Trip</span><span>{{ $driver->total_trips ?? 0 }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Lokasi Terakhir</span>
                    <span>{{ $driver->current_lat && $driver->current_lng ? round($driver->current_lat, 5) . ', ' . round($driver->current_lng, 5) : '-' }}
                        @if($driver->last_location_at)<span class="text-xs text-gray-400">({{ \Carbon\Carbon::parse($driver->last_location_at)->format('d M H:i') }})</span>@endif
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-pink-100 bg-pink-50 overflow-hidden">
            <div class="px-6 py-4 border-b border-pink-100 bg-pink-100">
                <h3 class="font-bold text-pink-900 text-sm uppercase tracking-wide">GrSaldo Wallet</h3>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-600">Saldo</span><span class="text-xl font-bold text-pink-700">Rp{{ number_format($wallet->balance ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Total Earning (SUCCESS)</span><span class="font-semibold">Rp{{ number_format($earningSummary->total_earning ?? 0, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Transaksi Earning</span><span>{{ $earningSummary->total_transactions ?? 0 }}</span></div>
                <a href="{{ route('kota.drivers.wallet', $driver->id) }}" class="text-xs text-pink-700 hover:underline block mt-2">Lihat semua transaksi wallet &rarr;</a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Trip Terbaru (10)</h3>
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
                        @forelse($recentTrips as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-mono text-xs">{{ $item->order_number }}</td>
                                <td class="px-6 py-3"><span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ ucfirst(strtolower($item->order_type ?? '-')) }}</span></td>
                                <td class="px-6 py-3 font-semibold">Rp{{ number_format($item->total_amount ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-3">{!! statusBadge($item->status) !!}</td>
                                <td class="px-6 py-3 text-xs text-gray-500">{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-6 text-center text-gray-500">Belum ada trip.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-3 border-t border-gray-100">
                <a href="{{ route('kota.drivers.trips', $driver->id) }}" class="text-xs text-orange-600 hover:underline">Lihat semua trip &rarr;</a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Kendaraan</h3>
                <a href="{{ route('kota.drivers.vehicles', $driver->id) }}" class="text-xs text-orange-600 hover:underline">Kelola &rarr;</a>
            </div>
            <div class="p-6 text-sm">
                @if($vehicle)
                    <div class="flex justify-between"><span class="text-gray-500">Tipe</span><span class="font-semibold">{{ ucfirst(strtolower($vehicle->vehicle_type ?? '-')) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Merk/Model</span><span>{{ $vehicle->brand ?? '-' }} {{ $vehicle->model ?? '' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Plat</span><span class="font-mono">{{ $vehicle->plate_number ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Warna</span><span>{{ $vehicle->color ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Kapasitas</span><span>{{ $vehicle->capacity ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Status</span>{!! statusBadge($vehicle->status_verifikasi ?? 'unknown') !!}</div>
                @else
                    <p class="text-gray-500">Belum ada kendaraan terdaftar.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
