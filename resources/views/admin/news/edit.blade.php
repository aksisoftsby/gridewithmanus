@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Berita: {{ $news->title }}</h1>
            <p class="text-sm text-gray-500">Perbarui berita.</p>
        </div>
        <a href="{{ route('admin.news.index') }}" class="text-purple-700 hover:underline text-sm font-semibold">&larr; Kembali</a>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
        <form action="{{ route('admin.news.update', $news->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
                <input type="text" name="title" value="{{ old('title', $news->title) }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-600" required>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select name="news_category_id" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-600">
                        <option value="">-- Tanpa Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('news_category_id', $news->news_category_id) == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-600">
                        <option value="PUBLISHED" @selected(old('status', $news->status) == 'PUBLISHED')>PUBLISHED</option>
                        <option value="DRAFT" @selected(old('status', $news->status) == 'DRAFT')>DRAFT</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ringkasan (Excerpt)</label>
                <textarea name="excerpt" rows="2" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-600">{{ old('excerpt', $news->excerpt) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Konten</label>
                <textarea name="content" rows="10" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-600" required>{{ old('content', $news->content) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Publikasi</label>
                <input type="datetime-local" name="published_at" value="{{ old('published_at', $news->published_at ? \Carbon\Carbon::parse($news->published_at)->format('Y-m-d\TH:i') : '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL Gambar (opsional)</label>
                <input type="url" name="featured_image" value="{{ old('featured_image', $news->featured_image) }}" placeholder="https://..." class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan
            </button>
        </form>
    </div>
</div>
@endsection
