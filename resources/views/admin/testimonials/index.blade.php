@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Testimonial Management</h1>
            <p class="text-sm text-gray-500">Kelola testimoni pelanggan yang tampil di halaman depan.</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.testimonials.create') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-plus mr-1"></i> Tambah Testimoni
            </a>
            <a href="{{ route('admin.dashboard') }}" class="text-emerald-600 hover:underline text-sm font-semibold self-center">&larr; Dashboard</a>
        </div>
    </div>

    @include('admin.partials.search', ['route' => 'admin.testimonials.index', 'placeholder' => 'Cari nama, isi, atau role...'])

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                    <th class="px-6 py-3">Nama</th>
                    <th class="px-6 py-3">Rating</th>
                    <th class="px-6 py-3">Isi</th>
                    <th class="px-6 py-3">Role</th>
                    <th class="px-6 py-3">Tanggal</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($testimonials as $t)
                    <tr>
                        <td class="px-6 py-4 font-semibold text-gray-900">{{ $t->name }}</td>
                        <td class="px-6 py-4 font-semibold text-yellow-600"><i class="fa-solid fa-star text-xs mr-1"></i>{{ $t->rating }}</td>
                        <td class="px-6 py-4 text-xs text-gray-600 max-w-xs">{{ Str::limit($t->content, 80) }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $t->role_title ?: '-' }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $t->testimonial_date ? \Carbon\Carbon::parse($t->testimonial_date)->format('d M Y') : '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $t->is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                {{ $t->is_published ? 'Published' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.testimonials.edit', $t->id) }}" class="text-emerald-600 hover:text-emerald-800 font-medium text-xs"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit</a>
                                <form action="{{ route('admin.testimonials.destroy', $t->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus testimoni ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-6 text-center text-gray-500">Belum ada testimoni.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $testimonials->links() }}
        </div>
    </div>
</div>
@endsection
