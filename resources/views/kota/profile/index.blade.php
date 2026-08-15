@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Profil Saya (Manager)</h1>
            <p class="text-sm text-gray-500">Informasi akun manager dan area coverage</p>
        </div>
        <a href="{{ route('kota.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="bg-pink-50 border border-pink-200 text-pink-900 px-4 py-3 rounded-lg mb-6 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-900 px-4 py-3 rounded-lg mb-6 text-sm">
            <ul class="list-disc list-inside">@foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
        </div>
    @endif

    <div class="bg-white p-6 rounded-xl shadow border border-gray-100 space-y-4">
        <form action="{{ route('kota.profile.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Role</label>
                    <input type="text" value="MANAGER" disabled class="w-full px-4 py-2 border rounded-lg bg-gray-50 text-gray-500">
                </div>
            </div>
            <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2 rounded-lg text-sm font-semibold shadow transition mt-4">
                <i class="fa-solid fa-save mr-1"></i> Simpan Profil
            </button>
        </form>
    </div>

    <div class="bg-pink-50 border border-pink-100 rounded-xl p-6 mt-6">
        <h3 class="font-bold text-pink-900 text-sm uppercase tracking-wide mb-3">Area Coverage Anda</h3>
        @forelse($coverage as $item)
            <span class="inline-flex items-center gap-1 bg-white border border-pink-200 text-pink-800 px-3 py-1.5 rounded-full text-xs font-semibold mr-2 mb-2">
                <i class="fa-solid fa-location-dot text-pink-500"></i> {{ $item->kota_nama }}, {{ $item->provinsi_nama }}
            </span>
        @empty
            <p class="text-sm text-gray-500">Belum ada kota coverage yang ditugaskan ke akun Anda. Hubungi admin super.</p>
        @endforelse
    </div>
</div>
@endsection
