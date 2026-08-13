@extends('layouts.app')

@section('content')
<!-- Merchant Header Banner -->
<div class="relative bg-gray-900 text-white py-16 mb-8">
    <div class="absolute inset-0 overflow-hidden opacity-40">
        <img src="{{ filled($merchant->banner_url) ? $merchant->banner_url : asset('images/merchant-placeholder.svg') }}" alt="{{ $merchant->name }}" loading="lazy" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='{{ asset('images/merchant-placeholder.svg') }}';">
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center gap-6">
        <img src="{{ filled($merchant->logo_url) ? $merchant->logo_url : asset('images/merchant-placeholder.svg') }}" alt="{{ $merchant->name }}" loading="lazy" class="w-28 h-28 rounded-2xl object-cover shadow-xl border-4 border-white" onerror="this.onerror=null;this.src='{{ asset('images/merchant-placeholder.svg') }}';">
        <div>
            <span class="bg-emerald-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase">{{ $merchant->type }}</span>
            <h1 class="text-3xl font-extrabold mt-2">{{ $merchant->name }}</h1>
            <p class="text-sm text-gray-200 mt-1 max-w-xl">{{ $merchant->description }}</p>
            <div class="flex items-center space-x-4 mt-3 text-sm text-gray-300">
                <span><i class="fa-solid fa-star text-yellow-400 mr-1"></i> {{ $merchant->rating }} ({{ $merchant->total_orders }}+ orders)</span>
                <span><i class="fa-solid fa-location-dot mr-1 text-emerald-400"></i> {{ $merchant->address_line }}, {{ $merchant->city }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Menu Items / Products List -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-16">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <i class="fa-solid fa-utensils text-emerald-600 mr-2"></i> Menu & Produk Tersedia
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($menuItems as $item)
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-5 flex gap-4 items-center">
                <img src="{{ filled($item->image_url) ? $item->image_url : asset('images/merchant-placeholder.svg') }}" alt="{{ $item->name }}" loading="lazy" class="w-24 h-24 rounded-xl object-cover flex-shrink-0" onerror="this.onerror=null;this.src='{{ asset('images/merchant-placeholder.svg') }}';">
                <div class="flex-grow">
                    <h3 class="font-bold text-gray-900 text-base">{{ $item->name }}</h3>
                    <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $item->description }}</p>
                    <div class="mt-3 flex justify-between items-center">
                        <span class="font-bold text-emerald-600">Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                        <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-medium">Tersedia</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12 text-gray-500 bg-white rounded-2xl shadow">
                <p>Belum ada menu atau produk yang ditambahkan untuk merchant ini.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
