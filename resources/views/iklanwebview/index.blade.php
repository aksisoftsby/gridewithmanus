@extends('layouts.webview', ['title' => 'Iklan Baris RideSip'])

@section('content')
<div class="mb-4 flex items-center justify-between">
    <div>
        <h1 class="text-xl font-bold text-gray-900">Iklan Baris</h1>
        <p class="text-xs text-gray-500">
            @if($me)
                Login sebagai <b>{{ $me->full_name ?? $me->name ?? $me->email }}</b> · Iklan Anda: {{ $myIklanCount }}
                <form action="{{ route('webview.logout') }}" method="POST" class="inline ml-2">
                    @csrf
                    <button type="submit" class="text-red-600 underline text-xs">Logout</button>
                </form>
            @else
                Belum login?
                <a href="{{ route('webview.login', ['intended' => request()->fullUrl()]) }}" class="text-pink-700 font-semibold underline">Login di sini</a>
            @endif
        </p>
    </div>
</div>

<!-- Filter Bar -->
<form action="{{ route('iklanwebview.index') }}" method="GET" class="bg-white p-3 rounded-2xl shadow-md flex flex-col gap-3 mb-5">
    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari judul, deskripsi, atau kota..." class="w-full px-4 py-3 rounded-xl text-gray-800 border focus:outline-none focus:ring-2 focus:ring-pink-500">
    <div class="flex gap-2">
        <select name="category_id" class="flex-grow px-4 py-3 rounded-xl text-gray-800 bg-gray-50 border focus:outline-none font-medium">
            <option value="">Semua Kategori</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" {{ ($cat ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-pink-700 hover:bg-pink-800 text-white px-6 py-3 rounded-xl font-semibold transition">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div>
</form>

<!-- Listing -->
<div class="flex flex-col gap-4">
    @forelse($iklan as $item)
        @php $photos = json_decode($item->photos ?? '[]', true) ?? []; @endphp
        <a href="{{ route('iklanwebview.detail', $item->id) }}" class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden hover:shadow-lg transition flex">
            <div class="w-36 h-28 bg-gray-100 flex items-center justify-center overflow-hidden relative shrink-0">
                @if(count($photos))
                    <img src="{{ $photos[0] }}" alt="{{ $item->title }}" class="w-full h-full object-cover" onerror="this.src='/images/merchant-placeholder.svg';">
                @else
                    <img src="/images/merchant-placeholder.svg" alt="Iklan" class="w-14 h-14 opacity-60">
                @endif
                @if($item->category_name)
                    <span class="absolute top-2 left-2 bg-pink-700 text-white text-[10px] font-semibold px-2 py-0.5 rounded-full leading-tight">{{ $item->category_name }}</span>
                @endif
            </div>
            <div class="p-4 flex-1">
                <h3 class="font-bold text-gray-900 line-clamp-2 text-sm">{{ $item->title }}</h3>
                <p class="text-[11px] text-gray-500 mt-1">
                    <i class="fa-solid fa-location-dot mr-1"></i>{{ $item->city ?: 'Kediri' }}
                    @if($item->expired_at)
                        <span class="ml-1"><i class="fa-solid fa-clock mr-1"></i>{{ \Carbon\Carbon::parse($item->expired_at)->format('d M Y') }}</span>
                    @endif
                </p>
                <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ $item->description }}</p>
                @if((float)$item->price > 0)
                    <div class="mt-2 font-bold text-pink-700 text-sm">Rp {{ number_format((float)$item->price, 0, ',', '.') }}</div>
                @endif
            </div>
        </a>
    @empty
        <div class="text-center py-14 bg-white rounded-2xl shadow">
            <i class="fa-solid fa-rectangle-list text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500 text-sm">Belum ada iklan baris.</p>
            @if($me)
                <a href="{{ route('iklanwebview.create') }}" class="inline-block mt-3 bg-pink-700 text-white px-5 py-2 rounded-full text-sm font-semibold">Pasang Iklan Pertama</a>
            @endif
        </div>
    @endforelse
</div>

<!-- Pagination -->
@if(method_exists($iklan, 'links') && $iklan->hasPages())
    <div class="mt-6">
        {{ $iklan->links() }}
    </div>
@endif
@endsection
