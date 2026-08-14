@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900"><i class="fa-solid fa-tag mr-2 text-pink-700"></i>Tambah Promo Baru</h1>
        <a href="{{ route('admin.promos.index') }}" class="text-sm text-pink-700 hover:underline"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali</a>
    </div>

    <form action="{{ route('admin.promos.store') }}" method="POST" class="bg-white rounded-2xl shadow-md p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kode Promo</label>
            <input type="text" name="code" value="{{ old('code') }}" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:outline-none" placeholder="RIDESIPHEMAT50">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
            <input type="text" name="title" value="{{ old('title') }}" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:outline-none">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Diskon</label>
                <select name="discount_type" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:outline-none">
                    <option value="PERCENTAGE" @selected(old('discount_type') == 'PERCENTAGE')>Persentase (%)</option>
                    <option value="FIXED" @selected(old('discount_type') == 'FIXED')>Nominal (Rp)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nilai Diskon</label>
                <input type="number" name="discount_value" value="{{ old('discount_value') }}" min="0" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:outline-none" placeholder="50">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Belanja (Rp)</label>
            <input type="number" name="min_purchase" value="{{ old('min_purchase', 0) }}" min="0" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:outline-none">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Mulai</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Selesai</label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-pink-600 focus:outline-none">
            </div>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active') ? 'checked' : '' }} class="w-4 h-4 text-pink-700">
            <label for="is_active" class="text-sm text-gray-700">Promo aktif</label>
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.promos.index') }}" class="px-5 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
            <button type="submit" class="px-5 py-2 bg-pink-700 hover:bg-pink-800 text-white rounded-lg text-sm font-semibold"><i class="fa-solid fa-save mr-1"></i>Simpan Promo</button>
        </div>
    </form>
</div>
@endsection
