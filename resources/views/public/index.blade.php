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
                    <img src="{{ filled($merchant->banner_url) ? $merchant->banner_url : asset('images/merchant-placeholder.svg') }}" alt="{{ $merchant->name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" onerror="this.onerror=null;this.src='{{ asset('images/merchant-placeholder.svg') }}';">
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
                    <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500 space-y-1">
                        <div class="flex justify-between items-center">
                            <span><i class="fa-solid fa-location-dot mr-1 text-emerald-600"></i>{{ $merchant->address_line ?? '' }}{{ $merchant->city ? ', ' . $merchant->city : '' }}</span>
                            <span class="text-emerald-600 font-semibold">Lihat Menu &rarr;</span>
                        </div>
                        @if($merchant->latitude && $merchant->longitude)
                            <a href="https://www.google.com/maps/dir/?api=1&destination={{ $merchant->latitude }},{{ $merchant->longitude }}" target="_blank" class="inline-flex items-center text-emerald-700 hover:underline font-medium">
                                <i class="fa-solid fa-map-location-dot mr-1"></i> Lihat Lokasi di Peta
                            </a>
                        @endif
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

<!-- News Section -->
@if(isset($news) && $news->count() > 0)
<div class="bg-white py-12 border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fa-solid fa-newspaper text-emerald-600 mr-2"></i> Berita & Informasi Terbaru
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($news as $item)
                <a href="{{ route('news.detail', $item->slug) }}" class="block">
                <article class="bg-gray-50 rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition">
                    @if($item->featured_image)
                        <img src="{{ $item->featured_image }}" alt="{{ $item->title }}" class="w-full h-44 object-cover">
                    @endif
                    <div class="p-5">
                        <div class="flex items-center gap-2 mb-2">
                            @if($item->category_name)
                                <span class="text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">{{ $item->category_name }}</span>
                            @endif
                            <span class="text-[10px] text-gray-400">{{ $item->published_at ? \Carbon\Carbon::parse($item->published_at)->format('d M Y') : '' }}</span>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">{{ $item->title }}</h3>
                        <p class="text-sm text-gray-600 line-clamp-3">{{ $item->excerpt ?: strip_tags($item->content) }}</p>
                    </div>
                </article>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Testimonials Section -->
@if(isset($testimonials) && $testimonials->count() > 0)
<div class="bg-gradient-to-br from-emerald-50 to-teal-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fa-solid fa-quote-left text-emerald-600 mr-2"></i> Kata Pelanggan Kami
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($testimonials as $t)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-3 mb-3">
                        @if($t->photo_url)
                            <img src="{{ $t->photo_url }}" alt="{{ $t->name }}" class="w-11 h-11 rounded-full object-cover">
                        @else
                            <div class="w-11 h-11 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold">{{ strtoupper(substr($t->name, 0, 1)) }}</div>
                        @endif
                        <div>
                            <div class="font-semibold text-gray-900 text-sm">{{ $t->name }}</div>
                            <div class="text-xs text-gray-500">{{ $t->role_title ?: '' }}{{ $t->location ? ' • ' . $t->location : '' }}</div>
                        </div>
                    </div>
                    <div class="text-yellow-500 text-xs mb-2" aria-label="Rating {{ (int) $t->rating }} dari 5">
                        @for($star = 1; $star <= 5; $star++)
                            @if($star <= (int) $t->rating)
                                <i class="fa-solid fa-star" aria-hidden="true"></i>
                            @else
                                <i class="fa-regular fa-star text-yellow-300" aria-hidden="true"></i>
                            @endif
                        @endfor
                    </div>
                    <p class="text-sm text-gray-700 italic">"{{ $t->content }}"</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection
