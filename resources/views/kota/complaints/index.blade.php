@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Keluhan (Complaints)</h1>
            <p class="text-sm text-gray-500">Keluhan yang dilaporkan via app manager</p>
        </div>
        <a href="{{ route('kota.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="bg-pink-50 border border-pink-200 text-pink-900 px-4 py-3 rounded-lg mb-6 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-900 px-4 py-3 rounded-lg mb-6 text-sm">
            <ul class="list-disc list-inside">@foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
        </div>
    @endif

    <div class="bg-white p-6 rounded-xl shadow border border-gray-100 mb-6">
        <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide mb-4">Laporkan Keluhan Baru</h3>
        <form action="{{ route('kota.complaints.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Judul *</label>
                    <input type="text" name="subject" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Kategori</label>
                    <select name="category" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                        <option value="other">Lainnya</option>
                        <option value="delivery_late">Pengiriman Telat</option>
                        <option value="driver_behavior">Perilaku Driver</option>
                        <option value="merchant_issue">Masalah Merchant</option>
                        <option value="billing">Billing</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Target</label>
                    <select name="target_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                        <option value="">-- Pilih --</option>
                        <option value="customer">Customer</option>
                        <option value="driver">Driver</option>
                        <option value="merchant">Merchant</option>
                        <option value="order">Order</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Pesan *</label>
                <textarea name="message" rows="3" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none"></textarea>
            </div>
            <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-paper-plane mr-1"></i> Laporkan
            </button>
        </form>
    </div>

    <div class="bg-white p-4 rounded-xl shadow border border-gray-100 mb-6">
        <form action="{{ route('kota.complaints.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Judul / pelapor" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none w-64">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select name="status" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    <option value="">Semua</option>
                    @foreach(["OPEN","IN_PROGRESS","RESOLVED","CLOSED"] as $s)
                        <option value="{{ $s }}" {{ ($status ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Filter</button>
            @if($search || $status)
                <a href="{{ route('kota.complaints.index') }}" class="text-sm text-orange-700 hover:underline py-2">Hapus filter</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Daftar Keluhan ({{ $complaints->total() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Judul</th>
                        <th class="px-6 py-3">Kategori</th>
                        <th class="px-6 py-3">Target</th>
                        <th class="px-6 py-3">Pelapor</th>
                        <th class="px-6 py-3">Ditugaskan</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($complaints as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-mono text-xs text-gray-500">{{ $complaints->id }}</td>
                            <td class="px-6 py-3">
                                <div class="font-semibold text-gray-800">{{ $complaints->subject }}</div>
                                <div class="text-xs text-gray-500 truncate max-w-[300px]" title="{{ $complaints->message }}">{{ $complaints->message }}</div>
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ ucfirst(str_replace('_',' ', $complaints->category ?? '')) }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $complaints->target_type ?? '-' }}</td>
                            <td class="px-6 py-3">{{ $complaints->reporter_name ?? '-' }}</td>
                            <td class="px-6 py-3">{{ $complaints->assigned_name ?? '-' }}</td>
                            <td class="px-6 py-3">{!! statusBadge($complaints->status) !!}</td>
                            <td class="px-6 py-3 text-xs text-gray-500">{{ $complaints->created_at ? \Carbon\Carbon::parse($complaints->created_at)->format('d M Y H:i') : '-' }}</td>
                            <td class="px-6 py-3">
                                <form action="{{ route('kota.complaints.status', $complaints->id) }}" method="POST" class="flex gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="px-1.5 py-1 border rounded text-xs font-semibold">
                                        @foreach(["OPEN","IN_PROGRESS","RESOLVED","CLOSED"] as $s)
                                            <option value="{{ $s }}" {{ $complaints->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-2 py-1 rounded text-xs font-semibold">Set</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-6 py-6 text-center text-gray-500">Belum ada keluhan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $complaints->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
