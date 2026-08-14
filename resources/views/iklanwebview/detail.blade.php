@extends('layouts.webview', ['title' => $item->title])

@section('content')
<a href="{{ url()->previous() === request()->fullUrl() ? route('iklanwebview.index') : url()->previous() }}" class="inline-flex items-center text-sm text-pink-700 font-medium mb-3">
    <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
</a>

<div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
    @if(count($photos))
        <div class="grid grid-cols-2 gap-1 bg-gray-100">
            @foreach($photos as $p)
                <img src="{{ $p }}" alt="Foto" class="w-full h-48 object-cover" onerror="this.src='/images/merchant-placeholder.svg';">
            @endforeach
        </div>
    @else
        <div class="h-52 bg-gray-100 flex items-center justify-center">
            <img src="/images/merchant-placeholder.svg" alt="Iklan" class="w-24 h-24 opacity-60">
        </div>
    @endif

    <div class="p-5">
        @if($item->category_name)
            <span class="inline-block bg-pink-700 text-white text-xs font-semibold px-3 py-1 rounded-full mb-2">{{ $item->category_name }}</span>
        @endif
        <h1 class="text-lg font-bold text-gray-900">{{ $item->title }}</h1>
        @if((float)$item->price > 0)
            <div class="text-xl font-extrabold text-pink-700 mt-1">Rp {{ number_format((float)$item->price, 0, ',', '.') }}</div>
        @endif
        <p class="text-xs text-gray-500 mt-1">
            <i class="fa-solid fa-location-dot mr-1"></i>{{ $item->city ?: 'Kediri' }}
            @if($item->expired_at)
                <span class="ml-2"><i class="fa-solid fa-clock mr-1"></i>Berlaku hingga {{ \Carbon\Carbon::parse($item->expired_at)->format('d M Y H:i') }}</span>
            @endif
        </p>
        <div class="mt-3 text-sm text-gray-700 whitespace-pre-line">{{ $item->description }}</div>

        @if($item->contact_name || $item->contact_phone)
            <div class="mt-4 border-t pt-4 space-y-1 text-sm">
                @if($item->contact_name)
                    <p><i class="fa-solid fa-user mr-2 text-gray-400"></i>{{ $item->contact_name }}</p>
                @endif
                @if($item->contact_phone)
                    <p><i class="fa-solid fa-phone mr-2 text-gray-400"></i><a href="tel:{{ $item->contact_phone }}" class="text-pink-700 font-semibold">{{ $item->contact_phone }}</a></p>
                @endif
            </div>
        @endif

        @if($me && $isOwner)
            <div class="mt-4 bg-orange-50 border-l-4 border-orange-500 p-3 rounded text-xs text-orange-700">
                <i class="fa-solid fa-user-tag mr-1"></i> Ini adalah iklan Anda ({{ $item->status }}).
            </div>
        @endif
    </div>
</div>
@endsection
