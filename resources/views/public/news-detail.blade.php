@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-6">
        <a href="{{ route('home') }}" class="inline-flex items-center text-emerald-700 hover:underline font-medium text-sm">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Beranda
        </a>
    </div>

    @if($item->featured_image)
        <div class="rounded-2xl overflow-hidden mb-6 shadow-md">
            <img src="{{ $item->featured_image }}" alt="{{ $item->title }}" class="w-full h-80 object-cover">
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 p-8 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
            @if($item->category_name)
                <span class="text-[11px] font-bold uppercase bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full">{{ $item->category_name }}</span>
            @endif
            <span class="text-xs text-gray-400">
                {{ $item->published_at ? \Carbon\Carbon::parse($item->published_at)->format('d F Y') : '' }}
            </span>
        </div>

        <h1 class="text-3xl font-extrabold text-gray-900 mb-4">{{ $item->title }}</h1>

        @if($item->excerpt)
            <p class="text-base text-emerald-700 font-medium mb-6">{{ $item->excerpt }}</p>
        @endif

        <div class="prose prose-emerald max-w-none text-gray-700 leading-relaxed whitespace-pre-line">
            {!! nl2br(e($item->content)) !!}
        </div>
    </div>
</div>
@endsection
