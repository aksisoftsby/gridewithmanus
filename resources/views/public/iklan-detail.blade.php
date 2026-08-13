@extends('layouts.app')

@section('content')
@php $photos = json_decode($item->photos ?? '[]', true) ?? []; @endphp
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <a href="{{ route('iklan.index') }}" class="text-purple-700 hover:underline text-sm font-semibold">&larr; Kembali ke Iklan Gratis</a>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Foto / Slider -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
                @if(count($photos))
                    <img id="mainPhoto" src="{{ $photos[0] }}" alt="{{ $item->title }}" class="w-full h-96 object-cover" onerror="this.src='/images/merchant-placeholder.svg';">
                    @if(count($photos) > 1)
                        <div class="flex gap-2 p-3 overflow-x-auto bg-gray-50">
                            @foreach($photos as $i => $photo)
                                <img src="{{ $photo }}" alt="Foto {{ $i + 1 }}" onclick="document.getElementById('mainPhoto').src='{{ $photo }}'" class="w-20 h-20 object-cover rounded-lg border-2 cursor-pointer hover:border-purple-600 transition" onerror="this.src='/images/merchant-placeholder.svg';">
                            @endforeach
                        </div>
                    @endif
                @else
                    <img src="/images/merchant-placeholder.svg" alt="Iklan" class="w-full h-96 object-cover opacity-70">
                @endif
            </div>
        </div>

        <!-- Info -->
        <div class="bg-white rounded-2xl shadow border border-gray-100 p-6 h-fit">
            @if($item->category_name)
                <span class="bg-purple-100 text-purple-900 text-xs font-bold px-3 py-1 rounded-full uppercase">{{ $item->category_name }}</span>
            @endif
            <h1 class="text-2xl font-extrabold text-gray-900 mt-3">{{ $item->title }}</h1>
            @if((float)$item->price > 0)
                <div class="text-2xl font-bold text-purple-700 mt-2">Rp {{ number_format((float)$item->price, 0, ',', '.') }}</div>
            @endif
            <p class="text-sm text-gray-500 mt-2">
                <i class="fa-solid fa-location-dot mr-1"></i>{{ $item->city ?: 'Kediri' }}
                @if($item->expired_at)
                    <br><i class="fa-solid fa-clock mr-1 mt-2"></i>Berlaku hingga {{ \Carbon\Carbon::parse($item->expired_at)->format('d M Y H:i') }}
                @endif
            </p>
            <div class="mt-5 space-y-2 text-sm border-t pt-4">
                @if($item->contact_name)
                    <div><span class="font-semibold text-gray-700">Kontak:</span> {{ $item->contact_name }}</div>
                @endif
                @if($item->contact_phone)
                    <div><span class="font-semibold text-gray-700">No. HP:</span>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $item->contact_phone) }}" target="_blank" class="text-purple-700 hover:underline font-semibold">{{ $item->contact_phone }}</a>
                    </div>
                @endif
            </div>
            <a href="https://gride.web.id/apk/customer.apk" class="mt-6 block text-center bg-purple-700 hover:bg-purple-800 text-white font-semibold py-3 rounded-xl shadow transition text-sm">
                <i class="fa-solid fa-download mr-1"></i> Pasang Iklan Gratis via Aplikasi
            </a>
        </div>
    </div>

    <!-- Deskripsi -->
    <div class="mt-8 bg-white rounded-2xl shadow border border-gray-100 p-6">
        <h2 class="font-bold text-gray-900 text-lg mb-3">Deskripsi Iklan</h2>
        <p class="text-gray-700 whitespace-pre-line leading-relaxed">{{ $item->description ?: 'Tidak ada deskripsi.' }}</p>
    </div>

    <!-- Iklan Serupa -->
    @if($related->count())
        <div class="mt-10">
            <h2 class="text-xl font-bold text-gray-900 mb-4"><i class="fa-solid fa-bullhorn text-purple-700 mr-2"></i>Iklan Serupa</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach($related as $rel)
                    @php $rphotos = json_decode($rel->photos ?? '[]', true) ?? []; @endphp
                    <a href="{{ route('iklan.detail', $rel->id) }}" class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden hover:shadow-lg transition">
                        <div class="h-32 bg-gray-100 overflow-hidden">
                            @if(count($rphotos))
                                <img src="{{ $rphotos[0] }}" alt="{{ $rel->title }}" class="w-full h-full object-cover" onerror="this.src='/images/merchant-placeholder.svg';">
                            @else
                                <img src="/images/merchant-placeholder.svg" alt="Iklan" class="w-16 h-16 mx-auto mt-6 opacity-60">
                            @endif
                        </div>
                        <div class="p-3">
                            <h3 class="font-semibold text-sm text-gray-900 line-clamp-2">{{ $rel->title }}</h3>
                            @if((float)$rel->price > 0)
                                <div class="text-xs font-bold text-purple-700 mt-1">Rp {{ number_format((float)$rel->price, 0, ',', '.') }}</div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
