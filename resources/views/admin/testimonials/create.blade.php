@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tambah Testimoni</h1>
            <p class="text-sm text-gray-500">Buat testimoni baru untuk halaman depan.</p>
        </div>
        <a href="{{ route('admin.testimonials.index') }}" class="text-pink-700 hover:underline text-sm font-semibold">&larr; Kembali</a>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
        <form action="{{ route('admin.testimonials.store') }}" method="POST" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rating (1-5)</label>
                    <select name="rating" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
                        @for($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" @selected(old('rating') == $i)>{{ $i }} Bintang</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role / Jabatan (opsional)</label>
                    <input type="text" name="role_title" value="{{ old('role_title') }}" placeholder="e.g. Pengguna Setia" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi (opsional)</label>
                    <input type="text" name="location" value="{{ old('location') }}" placeholder="e.g. Surabaya" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Isi Testimoni</label>
                <textarea name="content" rows="5" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600" required>{{ old('content') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Testimoni</label>
                <input type="date" name="testimonial_date" value="{{ old('testimonial_date', now()->format('Y-m-d')) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL Foto (opsional)</label>
                <input type="url" name="photo_url" value="{{ old('photo_url') }}" placeholder="https://..." class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-pink-600">
            </div>
            <div>
                <label class="flex items-center space-x-2">
                    <input type="checkbox" name="is_published" value="1" checked class="rounded border-gray-300 text-pink-700 focus:ring-pink-600">
                    <span class="text-sm font-semibold text-gray-700">Tampilkan di halaman depan</span>
                </label>
            </div>
            <button type="submit" class="bg-pink-700 hover:bg-pink-800 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan
            </button>
        </form>
    </div>
</div>
@endsection
