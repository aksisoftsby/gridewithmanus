@extends('layouts.app')

@section('content')
<!-- Hero Section -->
<div class="bg-gradient-to-r from-emerald-600 to-teal-700 text-white py-16 mb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-extrabold tracking-tight mb-4">Gride Superapp</h1>
        <p class="text-lg text-emerald-100 max-w-2xl mx-auto mb-8">Pesan makanan favorit, belanja kebutuhan harian, dan layanan kurir instan dalam satu aplikasi.</p>
        
        <!-- Search & Filter Bar -->
        <form action="{{ route('home') }}" method="GET" class="max-w-3xl mx-auto bg-white p-2 rounded-2xl shadow-xl flex flex-col sm:flex-row gap-2">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari restoran, makanan, atau supermarket..." class="flex-grow px-4 py-3 rounded-xl text-gray-800 focus:outline-none">
            <select name="type" class="px-4 py-3 rounded-xl text-gray-800 bg-gray-50 focus:outline-none font-medium">
                <option value="">Semua Layanan</option>
                <option value="FOOD" @selected($type == 'FOOD')>GrideFood (Restoran)</option>
                <option value="MART" @selected($type == 'MART')>GrideMart (Sembako)</option>
                <option value="SHOP" @selected($type == 'SHOP')>GrideShop (Retail)</option>
            </select>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 rounded-xl font-semibold transition">
                <i class="fa-solid fa-magnifying-glass mr-2"></i>Cari
            </button>
        </form>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-12">
    <!-- Active Promos Carousel/Banner -->
    <div class="mb-10">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <i class="fa-solid fa-tags text-emerald-600 mr-2"></i> Promo & Voucher Spesial
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($promos as $promo)
                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white p-6 rounded-2xl shadow-md flex justify-between items-center">
                    <div>
                        <span class="bg-yellow-400 text-gray-900 text-xs font-bold px-2.5 py-1 rounded-full uppercase">{{ $promo->code }}</span>
                        <h3 class="text-lg font-bold mt-2">{{ $promo->title }}</h3>
                        <p class="text-xs text-emerald-100 mt-1">Min. belanja Rp {{ number_format($promo->min_purchase, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-3xl font-extrabold bg-white/20 p-4 rounded-xl">
                        @if($promo->discount_type == 'PERCENTAGE')
                            {{ intval($promo->discount_value) }}%
                        @else
                            RpK
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Merchants Listing -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fa-solid fa-store text-emerald-600 mr-2"></i> Mitra Merchant & Restoran Terdekat
        </h2>
        <span class="text-sm text-gray-500">Showing {{ $merchants->count() }} active partners</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @forelse($merchants as $merchant)
            <a href="{{ route('merchant.detail', $merchant->slug) }}" class="bg-white rounded-2xl shadow-md hover:shadow-xl transition overflow-hidden border border-gray-100 flex flex-col group">
                <div class="relative h-48 bg-gray-200 overflow-hidden">
                    <img src="{{ $merchant->banner_url }}" alt="{{ $merchant->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    <span class="absolute top-3 left-3 bg-emerald-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow">
                        {{ $merchant->type }}
                    </span>
                    <span class="absolute bottom-3 right-3 bg-white text-gray-800 text-xs font-bold px-2.5 py-1 rounded-full shadow flex items-center">
                        <i class="fa-solid fa-star text-yellow-500 mr-1"></i> {{ $merchant->rating }}
                    </span>
                </div>
                <div class="p-6 flex flex-col flex-grow">
                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-emerald-600 transition">{{ $merchant->name }}</h3>
                    <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $merchant->description }}</p>
                    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center text-xs text-gray-500">
                        <span><i class="fa-solid fa-location-dot mr-1 text-emerald-600"></i>{{ $merchant->city }}</span>
                        <span class="text-emerald-600 font-semibold">Lihat Menu &rarr;</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-3 py-12 text-center text-gray-500 bg-white rounded-2xl shadow">
                <i class="fa-solid fa-face-sad-tear text-4xl mb-3 text-gray-400"></i>
                <p class="text-lg font-semibold">Tidak ada merchant yang ditemukan.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $merchants->appends(request()->query())->links() }}
    </div>
</div>
@endsection
