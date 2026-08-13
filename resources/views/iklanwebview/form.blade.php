@extends('layouts.webview', ['title' => 'Pasang Iklan Baris'])

@section('content')
<h1 class="text-xl font-bold text-gray-900 mb-1">Pasang Iklan Baris</h1>
<p class="text-xs text-gray-500 mb-4">Iklan Anda akan tampil di listing publik maksimal sesuai masa aktif yang dipilih.</p>

<form action="{{ route('iklanwebview.store') }}" method="POST" class="bg-white rounded-2xl shadow border border-gray-100 p-5 flex flex-col gap-4">
    @csrf
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Kategori <span class="text-red-500">*</span></label>
        <select name="category_id" required class="w-full px-4 py-3 rounded-xl border bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500">
            @foreach($categories as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Judul Iklan <span class="text-red-500">*</span></label>
        <input type="text" name="title" required maxlength="255" value="{{ old('title') }}" class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Contoh: Jual HP Samsung Galaxy A14">
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi <span class="text-red-500">*</span></label>
        <textarea name="description" required maxlength="5000" rows="4" class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Jelaskan produk/jasa Anda...">{{ old('description') }}</textarea>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Harga (Rp)</label>
            <input type="number" name="price" min="0" value="{{ old('price') }}" class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="0">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Kota</label>
            <input type="text" name="city" maxlength="100" value="{{ old('city') }}" class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Contoh: Kediri">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Kontak</label>
            <input type="text" name="contact_name" maxlength="255" value="{{ old('contact_name') }}" class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Nama Anda">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">No. WhatsApp/HP</label>
            <input type="text" name="contact_phone" maxlength="20" value="{{ old('contact_phone') }}" class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="08xxx">
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Foto Iklan (URL per baris, maks 10 foto)</label>
        <textarea name="photo_urls" rows="4" class="w-full px-4 py-3 rounded-xl border font-mono text-xs focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="https://...
https://...">{{ old('photo_urls') }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Masa Aktif Iklan <span class="text-red-500">*</span></label>
        <select name="expired_months" required class="w-full px-4 py-3 rounded-xl border bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500">
            @for ($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ old('expired_months', 1) == $m ? 'selected' : '' }}>
                    {{ $m }} {{ $m === 1 ? 'bulan' : 'bulan' }}
                </option>
            @endfor
        </select>
    </div>

    <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white font-bold py-3.5 rounded-xl transition shadow">
        <i class="fa-solid fa-paper-plane mr-2"></i>Pasang Iklan
    </button>
</form>
@endsection
