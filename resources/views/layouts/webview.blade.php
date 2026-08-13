<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? 'Iklan Baris Gride' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { padding-bottom: 96px; }
        .brand-gradient { background: linear-gradient(135deg, #2e1065 0%, #4c1d95 55%, #b45309 100%); }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans antialiased">
    <!-- Brand bar tipis (tanpa header menu) -->
    <div class="brand-gradient text-white">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-bolt-lightning text-yellow-300"></i>
                <span class="font-bold tracking-wider">Gride Iklan Baris</span>
            </div>
            <span class="text-xs text-white/80">gride.web.id</span>
        </div>
    </div>

    <main class="max-w-3xl mx-auto px-4 py-4">
        @if(session('success'))
            <div class="bg-purple-100 border-l-4 border-purple-600 text-purple-800 p-3 rounded shadow mb-4 text-sm" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 rounded shadow mb-4 text-sm" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 rounded shadow mb-4 text-sm" role="alert">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>

    <!-- Bottom nav webview (tanpa footer) -->
    <div class="fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 shadow-lg z-50">
        <div class="max-w-3xl mx-auto grid grid-cols-3 text-center text-xs">
            <a href="{{ route('iklanwebview.index') }}" class="py-3 text-purple-700 hover:bg-purple-50 {{ request()->routeIs('iklanwebview.index') ? 'font-bold' : '' }}">
                <i class="fa-solid fa-list block text-lg mb-0.5"></i>Listing
            </a>
            <a href="{{ route('iklanwebview.my') }}" class="py-3 text-purple-700 hover:bg-purple-50 {{ request()->routeIs('iklanwebview.my') ? 'font-bold' : '' }}">
                <i class="fa-solid fa-user block text-lg mb-0.5"></i>Iklan Saya
            </a>
            <a href="{{ route('iklanwebview.create') }}" class="py-3 bg-purple-700 text-white hover:bg-purple-800 font-bold">
                <i class="fa-solid fa-plus-circle block text-lg mb-0.5"></i>Pasang Iklan
            </a>
        </div>
    </div>
</body>
</html>
