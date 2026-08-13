<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Gride Superapp' }}</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon.png">
    <link rel="apple-touch-icon" href="/images/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .brand-gradient { background: linear-gradient(135deg, #2e1065 0%, #4c1d95 55%, #b45309 100%); }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased min-h-screen flex flex-col">
    <!-- Navbar -->
    <nav class="brand-gradient text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('home') }}" class="flex items-center space-x-3">
                        <img src="/images/logo.png" alt="Gride" class="h-10 w-10 rounded-full ring-2 ring-yellow-400/50 shadow">
                        <span class="text-xl font-extrabold tracking-wide">Gride <span class="font-light text-yellow-300">Superapp</span></span>
                    </a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="{{ route('home') }}" class="hover:text-yellow-200 font-medium">Beranda</a>
                    <a href="{{ route('iklan.index') }}" class="hover:text-yellow-200 font-medium">Iklan Gratis</a>
                    @auth
                        @php
                            $currentRoute = request()->route() ? (request()->route()->getName() ?? '') : '';
                        @endphp
                        @if(\App\Http\Access::canAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="bg-purple-800/70 hover:bg-purple-700 border border-purple-600 px-3 py-2 rounded-lg text-sm font-semibold transition">
                                <i class="fa-solid fa-gauge mr-1"></i> Admin Panel
                            </a>
                            @if(strtoupper((string)(Auth::user()->role_kota ?? '')) === 'MANAGER')
                                <a href="{{ route('kota.dashboard') }}" class="bg-amber-600 hover:bg-amber-700 px-3 py-2 rounded-lg text-sm font-semibold transition">
                                    <i class="fa-solid fa-map-location-dot mr-1"></i> Panel Kota
                                </a>
                            @endif
                            <a href="{{ route('admin.api.docs') }}" class="hover:text-yellow-200 text-sm font-medium {{ str_starts_with($currentRoute ?? '', 'admin.api') ? 'text-yellow-300' : '' }}">
                                <i class="fa-solid fa-book mr-1"></i> API Docs
                            </a>
                            <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="hover:text-red-200 text-sm font-medium">Logout</button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('admin.login') }}" class="bg-white text-purple-800 hover:bg-yellow-50 px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                            <i class="fa-solid fa-lock mr-1"></i> Admin Login
                        </a>
                    @endauth
                </div>
            </div>
        </div>
        @auth
            @if(\App\Http\Access::canAdmin())
            <!-- Admin Top Menu (persistent on all admin pages) -->
            <div class="bg-purple-950">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <nav class="flex flex-wrap gap-1 py-2 text-sm">
                        @php
                            $adminNav = \App\Http\Access::adminNav();
                            $currentRoute = request()->route() ? (request()->route()->getName() ?? '') : '';
                        @endphp
                        @foreach($adminNav as $item)
                            <a href="{{ route($item['route']) }}" class="px-3 py-1.5 rounded-lg font-medium transition {{ $currentRoute === $item['route'] || str_starts_with($currentRoute, rtrim(str_replace('.index','',$item['route']), '.') . '.') ? 'bg-yellow-400 text-purple-950' : 'text-purple-100 hover:bg-purple-800' }}">
                                <i class="fa-solid {{ $item['icon'] }} mr-1"></i> {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </div>
            @endif
            @auth
                @if(in_array(strtoupper((string)(Auth::user()->role_kota ?? '')), ['ADMIN', 'MANAGER'], true))
                <!-- Kota Panel Top Menu (persistent) -->
                <div class="bg-amber-800">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <nav class="flex flex-wrap gap-1 py-2 text-sm">
                            @php
                                $kotaNav = \App\Http\Controllers\KotaController::kotaNav();
                                $currentRoute = request()->route() ? (request()->route()->getName() ?? '') : '';
                            @endphp
                            @foreach($kotaNav as $item)
                                @if(isset($item['external']))
                                    <a href="{{ route($item['route']) }}" target="_blank" class="px-3 py-1.5 rounded-lg font-medium transition text-amber-100 hover:bg-amber-700">
                                        <i class="fa-solid {{ $item['icon'] }} mr-1"></i> {{ $item['label'] }}
                                    </a>
                                @else
                                    <a href="{{ route($item['route']) }}" class="px-3 py-1.5 rounded-lg font-medium transition {{ $currentRoute === $item['route'] || str_starts_with($currentRoute, rtrim(str_replace('.index','',$item['route']), '.') . '.') ? 'bg-yellow-400 text-amber-900' : 'text-amber-100 hover:bg-amber-700' }}">
                                        <i class="fa-solid {{ $item['icon'] }} mr-1"></i> {{ $item['label'] }}
                                    </a>
                                @endif
                            @endforeach
                        </nav>
                    </div>
                </div>
                @endif
            @endauth

            @if(strtoupper((string)(Auth::user()->role_kota ?? '')) === 'MANAGER')
                <!-- Kota Top Menu (persistent pada semua halaman untuk MANAGER) -->
                <div class="bg-amber-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <nav class="flex flex-wrap gap-1 py-2 text-sm">
                            @php
                                $kotaNav = \App\Http\Controllers\KotaController::kotaNav();
                                $currentRoute = request()->route() ? (request()->route()->getName() ?? '') : '';
                            @endphp
                            @foreach($kotaNav as $item)
                                @if(isset($item['external']))
                                    <a href="{{ route($item['route']) }}" target="_blank" class="px-3 py-1.5 rounded-lg font-medium transition text-amber-100 hover:bg-amber-700">
                                        <i class="fa-solid {{ $item['icon'] }} mr-1"></i> {{ $item['label'] }}
                                    </a>
                                @else
                                    <a href="{{ route($item['route']) }}" class="px-3 py-1.5 rounded-lg font-medium transition {{ $currentRoute === $item['route'] || str_starts_with($currentRoute, rtrim(str_replace('.index','',$item['route']), '.') . '.') ? 'bg-yellow-400 text-amber-900' : 'text-amber-100 hover:bg-amber-700' }}">
                                        <i class="fa-solid {{ $item['icon'] }} mr-1"></i> {{ $item['label'] }}
                                    </a>
                                @endif
                            @endforeach
                        </nav>
                    </div>
                </div>
            @endif
        @endauth
    </nav>

    <!-- Content -->
    <main class="flex-grow">
        @if(session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="bg-purple-100 border-l-4 border-purple-600 text-purple-800 p-4 rounded shadow" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow" role="alert">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    @auth
        @if(\App\Http\Access::canAdmin())
            @php
                $apkCustomer = \Illuminate\Support\Facades\DB::table('app_settings')->where('setting_key', 'apk_download_url_customer')->value('setting_value');
                $apkDriver = \Illuminate\Support\Facades\DB::table('app_settings')->where('setting_key', 'apk_download_url_driver')->value('setting_value');
                $apkMerchant = \Illuminate\Support\Facades\DB::table('app_settings')->where('setting_key', 'apk_download_url_merchant')->value('setting_value');
                $apkCustomer = is_string($apkCustomer) ? $apkCustomer : 'https://gride.web.id/apk/customer.apk';
                $apkDriver = is_string($apkDriver) ? $apkDriver : 'https://gride.web.id/apk/driver.apk';
                $apkMerchant = is_string($apkMerchant) ? $apkMerchant : 'https://gride.web.id/apk/merchant.apk';
            @endphp
            <!-- Admin Footer: APK Downloads (permanen, URL di-settings page) -->
            <div class="bg-purple-50 border-t border-purple-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <span class="text-sm font-semibold text-purple-900"><i class="fa-solid fa-mobile-screen mr-1"></i> Unduh APK (Build Terbaru):</span>
                        <a href="{{ $apkCustomer }}" target="_blank" class="inline-flex items-center space-x-1 bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold px-4 py-2 rounded-full shadow transition">
                            <i class="fa-solid fa-download"></i>
                            <span>Customer</span>
                        </a>
                        <a href="{{ $apkDriver }}" target="_blank" class="inline-flex items-center space-x-1 bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold px-4 py-2 rounded-full shadow transition">
                            <i class="fa-solid fa-download"></i>
                            <span>Driver</span>
                        </a>
                        <a href="{{ $apkMerchant }}" target="_blank" class="inline-flex items-center space-x-1 bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold px-4 py-2 rounded-full shadow transition">
                            <i class="fa-solid fa-download"></i>
                            <span>Merchant</span>
                        </a>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    <!-- Footer -->
    <footer class="brand-gradient text-gray-200 py-10 mt-12 border-t border-purple-800">
        <div class="max-w-7xl mx-auto px-4 flex flex-col items-center">
            <img src="/images/logo-footer-small.png" alt="GRide — Good Relationship Inovasi Digital Ekosistem" class="w-72 md:w-96 mb-5 drop-shadow-lg">
            <p class="text-sm text-purple-200">&copy; {{ date('Y') }} Gride Superapp — gride.web.id</p>
        </div>
    </footer>
</body>
</html>
