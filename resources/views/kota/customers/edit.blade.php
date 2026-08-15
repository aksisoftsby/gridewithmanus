@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Customer: {{ $customer->full_name }}</h1>
            <p class="text-sm text-gray-500">Update profil customer di area coverage Anda</p>
        </div>
        <a href="{{ route('kota.customers.show', $customer->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Detail
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-900 px-4 py-3 rounded-lg mb-6 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('kota.customers.update', $customer->id) }}" method="POST" class="bg-white p-6 rounded-xl shadow border border-gray-100 space-y-4">
        @csrf
        @method('PATCH')
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
            <input type="text" name="full_name" value="{{ old('full_name', $customer->full_name) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Status Akun</label>
            <select name="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                @foreach(['ACTIVE','INACTIVE','SUSPENDED','BANNED'] as $s)
                    <option value="{{ $s }}" {{ ($customer->status ?? 'ACTIVE') === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2 rounded-lg text-sm font-semibold shadow transition">
                <i class="fa-solid fa-save mr-1"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection
