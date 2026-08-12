@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
            <p class="text-sm text-gray-500">Pengaturan sistem, termasuk tautan unduhan APK dari GitHub Actions.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-emerald-600 hover:underline text-sm font-semibold">&larr; Dashboard</a>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow border border-gray-100 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-gray-800 text-lg">Tautan Unduh APK (GitHub Actions)</h3>
                <p class="text-xs text-gray-500">Tombol unduh APK di footer admin diambil dari tautan ini. Periode trial berakhir: {{ $trialEnds }}.</p>
            </div>
            <form action="{{ route('admin.settings.refresh-links') }}" method="POST">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    <i class="fa-solid fa-arrows-rotate mr-1"></i> Refresh dari GitHub
                </button>
            </form>
        </div>

        @if(!$trialActive)
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-4">
                Periode trial APK download telah berakhir. Tautan tidak akan ditampilkan di footer admin.
            </div>
        @endif

        @if($links)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase border-b">
                            <th class="px-4 py-3">Aplikasi</th>
                            <th class="px-6 py-3">Dibuat</th>
                            <th class="px-6 py-3">Ukuran</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @foreach($links as $link)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $link['app'] }}</td>
                                <td class="px-6 py-3 text-xs text-gray-500">{{ \Carbon\Carbon::parse($link['created_at'])->format('d M Y H:i') }}</td>
                                <td class="px-6 py-3 text-xs text-gray-500">{{ $link['size_mb'] }} MB</td>
                                <td class="px-4 py-3">
                                    @if($trialActive)
                                        <a href="{{ $link['url'] }}" target="_blank" class="text-emerald-600 hover:underline text-xs font-medium"><i class="fa-solid fa-download mr-1"></i> Unduh APK</a>
                                    @else
                                        <span class="text-gray-400 text-xs">Expired</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center text-gray-500 text-sm py-8">
                Belum ada tautan APK. Klik "Refresh dari GitHub" setelah workflow Build Flutter APKs selesai berjalan.
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
        <h3 class="font-bold text-gray-800 text-lg mb-3">Informasi Sistem</h3>
        <div class="text-sm text-gray-600 space-y-2">
            <div><span class="font-medium">Domain:</span> {{ config('app.url') }}</div>
            <div><span class="font-medium">Lingkungan:</span> {{ config('app.env') }}</div>
            <div><span class="font-medium">Database:</span> {{ config('database.default') }}</div>
            <div><span class="font-medium">Repository:</span> <a href="https://github.com/aksisoftsby/gridewithmanus" target="_blank" class="text-emerald-600 hover:underline">aksisoftsby/gridewithmanus</a></div>
        </div>
    </div>
</div>
@endsection
