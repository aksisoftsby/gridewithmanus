@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Iklan Gratis Management</h1>
            <p class="text-sm text-gray-500">Kelola iklan baris gratis yang dipasang pengguna via aplikasi.</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.iklan.kategori') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-layer-group mr-1"></i> Kategori
            </a>
            <a href="{{ route('admin.iklan.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-plus mr-1"></i> Tambah Iklan
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow border border-gray-100 p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Pencarian</label>
                <input type="text" name="search" value="{{ $search }}" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-purple-600" placeholder="Judul / deskripsi...">
            </div>
            <div class="w-44">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Kategori</label>
                <select name="category_id" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-purple-600">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ $cat == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-purple-600">
                    <option value="">Semua</option>
                    <option value="ACTIVE" {{ $status === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                    <option value="INACTIVE" {{ $status === 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                    <option value="BLOCKED" {{ $status === 'BLOCKED' ? 'selected' : '' }}>Blocked</option>
                    <option value="EXPIRED" {{ $status === 'EXPIRED' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">Filter</button>
            <a href="{{ route('admin.iklan.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium self-center">Reset</a>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                    <th class="px-6 py-3">Judul</th>
                    <th class="px-6 py-3">Kategori</th>
                    <th class="px-6 py-3">Harga</th>
                    <th class="px-6 py-3">Kota</th>
                    <th class="px-6 py-3">Pemasang</th>
                    <th class="px-6 py-3">Expired</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($iklan as $item)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800">{{ $item->title }}</div>
                            @php $photos = json_decode($item->photos ?? '[]', true) ?? []; @endphp
                            @if(count($photos))
                                <div class="text-xs text-gray-500 mt-0.5"><i class="fa-solid fa-images mr-1"></i>{{ count($photos) }} foto</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $item->category_name ?? '-' }}</td>
                        <td class="px-6 py-4 font-semibold text-purple-700">Rp {{ number_format((float)$item->price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $item->city ?: '-' }}</td>
                        <td class="px-6 py-4 text-gray-600 text-xs">user #{{ $item->user_id }}</td>
                        <td class="px-6 py-4 text-xs text-gray-600">{{ $item->expired_at ? \Carbon\Carbon::parse($item->expired_at)->format('d M Y') : '-' }}</td>
                        <td class="px-6 py-4">
                            @if($item->expired_at && \Carbon\Carbon::parse($item->expired_at)->isPast())
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Expired</span>
                            @elseif($item->status === 'ACTIVE')
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-900">Active</span>
                            @elseif($item->status === 'BLOCKED')
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Blocked</span>
                            @else
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-600">{{ $item->status }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.iklan.edit', $item->id) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Edit</a> |
                            <form action="{{ route('admin.iklan.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus iklan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-6 text-center text-gray-500">Tidak ada iklan ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $iklan->links() }}</div>
</div>
@endsection
