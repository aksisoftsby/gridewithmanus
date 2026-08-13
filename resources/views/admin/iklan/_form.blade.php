@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ isset($iklan) ? 'Edit Iklan Gratis' : 'Tambah Iklan Gratis' }}</h1>
            <p class="text-sm text-gray-500">Iklan tampil di website & aplikasi, dipasang pengguna via aplikasi.</p>
        </div>
        <a href="{{ route('admin.iklan.index') }}" class="text-emerald-600 hover:underline text-sm font-semibold">&larr; Kembali</a>
    </div>

    <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
        <form action="{{ isset($iklan) ? route('admin.iklan.update', $iklan->id) : route('admin.iklan.store') }}" method="POST">
            @csrf
            @if(isset($iklan)) @method('PUT') @endif

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Judul Iklan <span class="text-red-500">*</span></label>
                <input type="text" name="title" required value="{{ old('title', $iklan->title ?? '') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500" placeholder="Misal: Rumah dijual cepat di Kediri">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Kategori</label>
                    <select name="category_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ old('category_id', $iklan->category_id ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Harga (Rp)</label>
                    <input type="number" step="100" name="price" value="{{ old('price', $iklan->price ?? 0) }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="4" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500" placeholder="Deskripsikan iklan secara singkat...">{{ old('description', $iklan->description ?? '') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Kontak</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name', $iklan->contact_name ?? '') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">No. HP</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $iklan->contact_phone ?? '') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Kota</label>
                    <input type="text" name="city" value="{{ old('city', $iklan->city ?? '') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500" placeholder="Kediri">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Foto Iklan (URL, maksimal 10 foto — satu URL per baris)</label>
                <textarea name="photos" rows="5" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 font-mono text-xs" placeholder="https://example.com/foto1.jpg&#10;https://example.com/foto2.jpg">{{ old('photos', isset($iklan) ? implode("\n", json_decode($iklan->photos ?? '[]', true) ?? []) : '') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Pisahkan setiap URL foto dengan baris baru.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Durasi Tampil (Expired)</label>
                    <select name="expired_months" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ isset($iklan) ? '' : ($m === 12 ? 'selected' : '') }}>{{ $m }} {{ $m === 1 ? 'bulan' : 'bulan' }}</option>
                        @endfor
                    </select>
                    @if(isset($iklan))
                        <p class="text-xs text-gray-500 mt-1">Expired saat ini: {{ $iklan->expired_at ? \Carbon\Carbon::parse($iklan->expired_at)->format('d M Y H:i') : '-' }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500">
                        <option value="ACTIVE" {{ old('status', $iklan->status ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                        <option value="INACTIVE" {{ old('status', $iklan->status ?? '') === 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                        <option value="BLOCKED" {{ old('status', $iklan->status ?? '') === 'BLOCKED' ? 'selected' : '' }}>Blocked</option>
                        <option value="EXPIRED" {{ old('status', $iklan->status ?? '') === 'EXPIRED' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-lg shadow transition">
                {{ isset($iklan) ? 'Update Iklan' : 'Simpan Iklan' }}
            </button>
        </form>
    </div>
</div>
@endsection
