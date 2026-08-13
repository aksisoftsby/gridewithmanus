@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Detail Kota/Kabupaten</h1>
            <a href="{{ route('kota.wilayah.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <div class="text-xs font-semibold text-gray-500 uppercase">ID</div>
                <div class="text-gray-900">{{ $kota->id }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold text-gray-500 uppercase">Provinsi</div>
                <div class="text-gray-900">{{ $kota->provinsi_nama }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold text-gray-500 uppercase">Nama Kota/Kabupaten</div>
                <div class="text-lg font-bold text-gray-900">{{ $kota->nama }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold text-gray-500 uppercase">Tipe</div>
                <div>
                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $kota->tipe === 'Kota' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $kota->tipe }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
