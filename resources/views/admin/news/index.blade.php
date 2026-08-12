@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">News Management</h1>
            <p class="text-sm text-gray-500">Kelola berita & pengumuman yang tampil di halaman depan.</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.news.create') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-plus mr-1"></i> Tambah Berita
            </a>
            <a href="{{ route('admin.dashboard') }}" class="text-emerald-600 hover:underline text-sm font-semibold self-center">&larr; Dashboard</a>
        </div>
    </div>

    @include('admin.partials.search', ['route' => 'admin.news.index', 'placeholder' => 'Cari judul atau status berita...'])

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                    <th class="px-6 py-3">Judul</th>
                    <th class="px-6 py-3">Kategori</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Dipublikasikan</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($news as $item)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900">{{ $item->title }}</div>
                            <div class="text-xs text-gray-500 truncate max-w-md">{{ Str::limit(strip_tags($item->excerpt), 80) }}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $item->category_name ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $item->status == 'PUBLISHED' ? 'bg-emerald-100 text-emerald-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $item->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $item->published_at ? \Carbon\Carbon::parse($item->published_at)->format('d M Y H:i') : '-' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.news.edit', $item->id) }}" class="text-emerald-600 hover:text-emerald-800 font-medium text-xs"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit</a>
                                <form action="{{ route('admin.news.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus berita ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-6 text-center text-gray-500">Belum ada berita.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $news->links() }}
        </div>
    </div>
</div>
@endsection
