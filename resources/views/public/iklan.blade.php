@extends('layouts.app')

@section('content')
<div class="brand-gradient text-white py-12 mb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl font-extrabold tracking-tight mb-2">Iklan Gratis</h1>
        <p class="text-purple-100 max-w-2xl mx-auto">Jual beli & jasa dalam satu ekosistem. Pasang iklan gratis langsung dari aplikasi Gride Customer.</p>
        <div class="mt-6 flex justify-center gap-3">
            <a href="https://gride.web.id/apk/customer.apk" class="bg-yellow-400 text-gray-900 hover:bg-yellow-300 px-5 py-2.5 rounded-full font-semibold shadow transition text-sm">
                <i class="fa-solid fa-download mr-1"></i> Pasang Iklan via Aplikasi
            </a>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-12">
    <!-- Filter Bar -->
    <form action="{{ route('iklan.index') }}" method="GET" class="bg-white p-3 rounded-2xl shadow-md flex flex-col sm:flex-row gap-3 mb-8">
        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari judul, deskripsi, atau kota..." class="flex-grow px-4 py-3 rounded-xl text-gray-800 border focus:outline-none focus:ring-2 focus:ring-purple-600">
        <select name="category_id" class="px-4 py-3 rounded-xl text-gray-800 bg-gray-50 border focus:outline-none font-medium">
            <option value="">Semua Kategori</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" {{ ($cat ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white px-8 py-3 rounded-xl font-semibold transition">
            <i class="fa-solid fa-magnifying-glass mr-2"></i>Cari
        </button>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($iklan as $item)
            @php $photos = json_decode($item->photos ?? '[]', true) ?? []; @endphp
            <a href="{{ route('iklan.detail', $item->id) }}" class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden hover:shadow-lg transition group">
                <div class="h-44 bg-gray-100 flex items-center justify-center overflow-hidden relative">
                    @if(count($photos))
                        <img src="{{ $photos[0] }}" alt="{{ $item->title }}" class="w-full h-full object-cover" onerror="this.src='/images/merchant-placeholder.svg';">
                    @else
                        <img src="/images/merchant-placeholder.svg" alt="Iklan" class="w-24 h-24 opacity-60">
                    @endif
                    @if($item->category_name)
                        <span class="absolute top-3 left-3 bg-purple-700 text-white text-xs font-semibold px-3 py-1 rounded-full">{{ $item->category_name }}</span>
                    @endif
                </div>
                <div class="p-5">
                    <h3 class="font-bold text-gray-900 group-hover:text-purple-700 transition line-clamp-2">{{ $item->title }}</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fa-solid fa-location-dot mr-1"></i>{{ $item->city ?: 'Kediri' }}
                        @if($item->expired_at)
                            <span class="ml-2"><i class="fa-solid fa-clock mr-1"></i>Hingga {{ \Carbon\Carbon::parse($item->expired_at)->format('d M Y') }}</span>
                        @endif
                    </p>
                    <p class="text-sm text-gray-600 mt-2 line-clamp-2">{{ $item->description }}</p>
                    @if((float)$item->price > 0)
                        <div class="mt-3 font-bold text-purple-700">Rp {{ number_format((float)$item->price, 0, ',', '.') }}</div>
                    @endif
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-16 bg-white rounded-2xl shadow">
                <i class="fa-solid fa-bullhorn text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 font-medium">Belum ada iklan yang aktif di kategori ini.</p>
            </div>
        @endforelse
    </div>

    @if(isset($iklan) && $iklan instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-8">{{ $iklan->links() }}</div>
    @endif
</div>
@endsection
