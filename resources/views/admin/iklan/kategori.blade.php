@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kategori Iklan Gratis</h1>
            <p class="text-sm text-gray-500">Kelola kategori yang digunakan pada menu Iklan Gratis.</p>
        </div>
        <a href="{{ route('admin.iklan.index') }}" class="text-emerald-600 hover:underline text-sm font-semibold">&larr; Iklan</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Kategori Table -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">Slug</th>
                        <th class="px-6 py-3">Aktif</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($categories as $cat)
                        <tr>
                            <td class="px-6 py-4 font-semibold text-gray-800">{{ $cat->name }}</td>
                            <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ $cat->slug }}</td>
                            <td class="px-6 py-4">
                                @if($cat->is_active)
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">Aktif</span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-600">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.iklan.kategori.update', $cat->id) }}" class="hidden"></a>
                                <a href="#" onclick="document.getElementById('edit-{{ $cat->id }}').classList.remove('hidden'); return false;" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Edit</a> |
                                <form action="{{ route('admin.iklan.kategori.destroy', $cat->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kategori ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Delete</button>
                                </form>
                                <!-- Edit Inline -->
                                <div id="edit-{{ $cat->id }}" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                                    <div class="bg-white rounded-xl shadow-lg p-6 w-96">
                                        <h4 class="font-bold mb-3">Edit Kategori</h4>
                                        <form action="{{ route('admin.iklan.kategori.update', $cat->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="name" value="{{ $cat->name }}" required class="w-full px-3 py-2 border rounded-lg mb-3">
                                            <label class="flex items-center space-x-2 mb-4">
                                                <input type="checkbox" name="is_active" value="1" {{ $cat->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-600">
                                                <span class="text-sm">Aktif</span>
                                            </label>
                                            <div class="flex space-x-2">
                                                <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm">Simpan</button>
                                                <button type="button" onclick="document.getElementById('edit-{{ $cat->id }}').classList.add('hidden')" class="text-gray-600 px-4 py-2">Batal</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-6 text-center text-gray-500">Belum ada kategori.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-3 bg-gray-50 border-t text-sm">{{ $categories->links() }}</div>
        </div>

        <!-- Tambah Kategori -->
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100 h-fit">
            <h3 class="font-bold text-gray-800 text-lg mb-4">Tambah Kategori Baru</h3>
            <form action="{{ route('admin.iklan.kategori.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Kategori</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500" placeholder="Misal: Elektronik">
                </div>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-lg shadow transition">
                    Simpan Kategori
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
