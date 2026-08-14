@extends('layouts.webview', ['title' => 'Iklan Saya'])

@section('content')
<h1 class="text-xl font-bold text-gray-900 mb-1">Iklan Saya</h1>
<p class="text-xs text-gray-500 mb-4">Login sebagai <b>{{ $me->full_name ?? $me->name ?? $me->email }}</b></p>

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
                    Status: <b>{{ $item->status }}</b>
                    @if($item->expired_at)
                        <span class="ml-1">· Hingga {{ \Carbon\Carbon::parse($item->expired_at)->format('d M Y') }}</span>
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
            <i class="fa-regular fa-folder-open text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500 text-sm mb-3">Anda belum memasang iklan.</p>
            <a href="{{ route('iklanwebview.create') }}" class="inline-block bg-pink-700 text-white px-5 py-2 rounded-full text-sm font-semibold">Pasang Iklan Sekarang</a>
        </div>
    @endforelse
</div>

@if(method_exists($iklan, 'links') && $iklan->hasPages())
    <div class="mt-6">{{ $iklan->links() }}</div>
@endif
@endsection
