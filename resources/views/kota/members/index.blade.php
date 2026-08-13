@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Kelola Members</h1>
            <p class="text-sm text-gray-500">Merchant sesuai coverage kota &amp; driver</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('kota.members.index', ['type' => 'merchant']) }}" class="px-4 py-2 rounded-lg text-sm font-semibold shadow transition {{ ($type ?? 'merchant') === 'merchant' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                <i class="fa-solid fa-store mr-1"></i> Merchant
            </a>
            <a href="{{ route('kota.members.index', ['type' => 'driver']) }}" class="px-4 py-2 rounded-lg text-sm font-semibold shadow transition {{ ($type ?? '') === 'driver' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                <i class="fa-solid fa-motorcycle mr-1"></i> Driver
            </a>
        </div>
    </div>

    @if ($type === 'merchant')
        @php $coverageNama = (new \App\Http\Controllers\KotaController())->coverageKotaNamesDisplay(); @endphp
        <div class="bg-blue-50 border border-blue-100 text-blue-800 text-sm rounded-lg px-4 py-3 mb-6">
            <i class="fa-solid fa-circle-info mr-1"></i>
            Daftar merchant difilter berdasarkan kota-kota di <strong>coverage</strong> Anda
            @if (!empty($coverageNama)): <strong>{{ implode(', ', $coverageNama) }}</strong>@else: <span class="text-blue-600">belum ada coverage kota</span>@endif.
            Merchant dengan nama kota yang cocok dengan kota coverage akan tampil.
        </div>

        <!-- Search -->
        <form method="GET" class="mb-4 flex gap-2">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama merchant, kota, pemilik..." class="flex-1 rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            <input type="hidden" name="type" value="merchant">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari
            </button>
            @if (!empty($search))
                <a href="{{ route('kota.members.index', ['type' => 'merchant']) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">Reset</a>
            @endif
        </form>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                            <th class="px-5 py-3">ID</th>
                            <th class="px-5 py-3">Nama</th>
                            <th class="px-5 py-3">Tipe</th>
                            <th class="px-5 py-3">Pemilik</th>
                            <th class="px-5 py-3">Kota</th>
                            <th class="px-5 py-3">Alamat</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($merchants ?? [] as $m)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-500">{{ $m->id }}</td>
                            <td class="px-5 py-3 font-semibold text-gray-800">{{ $m->name }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                                    @if ($m->type === 'FOOD') bg-orange-100 text-orange-700
                                    @elseif ($m->type === 'MART') bg-green-100 text-green-700
                                    @else bg-gray-100 text-gray-700 @endif">
                                    {{ $m->type ?? '-' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ $m->owner_name ?? '-' }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $m->city ?? '-' }}</td>
                            <td class="px-5 py-3 text-gray-500 max-w-xs truncate">{{ $m->address_line ?? '-' }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                                    @if ($m->status === 'ACTIVE') bg-green-100 text-green-700
                                    @elseif ($m->status === 'SUSPENDED') bg-red-100 text-red-700
                                    @else bg-gray-100 text-gray-700 @endif">
                                    {{ $m->status ?? 'ACTIVE' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('kota.members.merchant.edit', $m->id) }}" class="text-purple-600 hover:underline text-xs font-semibold">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-gray-500">
                                Tidak ada merchant dalam coverage kota Anda.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if (isset($merchants) && $merchants->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $merchants->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="bg-amber-50 border border-amber-100 text-amber-800 text-sm rounded-lg px-4 py-3 mb-6">
            <i class="fa-solid fa-circle-info mr-1"></i>
            Tabel driver <strong>tidak menyimpan data kota</strong>, sehingga daftar ini menampilkan semua driver yang terdaftar di platform (tidak dapat difilter per kota).
        </div>

        <form method="GET" class="mb-4 flex gap-2">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama driver, email, status..." class="flex-1 rounded-lg border border-gray-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            <input type="hidden" name="type" value="driver">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari
            </button>
            @if (!empty($search))
                <a href="{{ route('kota.members.index', ['type' => 'driver']) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">Reset</a>
            @endif
        </form>

        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                            <th class="px-5 py-3">ID</th>
                            <th class="px-5 py-3">Nama</th>
                            <th class="px-5 py-3">Email</th>
                            <th class="px-5 py-3">Telepon</th>
                            <th class="px-5 py-3">Rating</th>
                            <th class="px-5 py-3">Terverifikasi</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Lokasi Terakhir</th>
                            <th class="px-5 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($drivers ?? [] as $d)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-500">{{ $d->id }}</td>
                            <td class="px-5 py-3 font-semibold text-gray-800">{{ $d->full_name ?? '-' }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $d->email ?? '-' }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $d->phone ?? '-' }}</td>
                            <td class="px-5 py-3 text-gray-700">
                                @if (!is_null($d->rating))
                                    {{ number_format((float) $d->rating, 1) }} <i class="fa-solid fa-star text-yellow-400 text-xs"></i>
                                @else - @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ !empty($d->is_verified) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ !empty($d->is_verified) ? 'Ya' : 'Belum' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <form method="POST" action="{{ route('kota.members.driver.status', $d->id) }}" class="inline-flex">
                                    @csrf @method('PATCH')
                                    <button type="submit" name="status" value="{{ ($d->status ?? 'OFFLINE') === 'ONLINE' ? 'OFFLINE' : 'ONLINE' }}"
                                        class="px-2 py-0.5 text-xs font-semibold rounded-full border-0 cursor-pointer
                                        {{ ($d->status ?? 'OFFLINE') === 'ONLINE' ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}">
                                        {{ strtoupper($d->status ?? 'OFFLINE') }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-5 py-3 text-gray-500 text-xs">
                                @if ($d->current_lat && $d->current_lng)
                                    {{ number_format((float) $d->current_lat, 4) }}, {{ number_format((float) $d->current_lng, 4) }}
                                @else - @endif
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('kota.members.driver.edit', $d->id) }}" class="text-purple-600 hover:underline text-xs font-semibold">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-5 py-8 text-center text-gray-500">Tidak ada driver terdaftar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if (isset($drivers) && $drivers->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $drivers->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
