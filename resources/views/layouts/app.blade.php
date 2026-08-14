<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'RideSip Superapp' }}</title>
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
                        <img src="/images/logo.png" alt="RideSip" class="h-10 w-10 rounded-xl shadow-lg">
                        <span class="text-xl font-extrabold tracking-wide">Ride<span class="font-light text-pink-300">Sip</span></span>
                    </a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="{{ route('home') }}" class="hover:text-pink-200 font-medium">Beranda</a>
                    <a href="{{ route('iklan.index') }}" class="hover:text-pink-200 font-medium">Iklan Gratis</a>
                    @auth
                        @php
                            $currentRoute = request()->route() ? (request()->route()->getName() ?? '') : '';
                        @endphp
                        @if(\App\Http\Access::canAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="bg-pink-800/70 hover:bg-pink-700 border border-pink-600 px-3 py-2 rounded-lg text-sm font-semibold transition">
                                <i class="fa-solid fa-gauge mr-1"></i> Admin Panel
                            </a>
                            @if(strtoupper((string)(Auth::user()->role_kota ?? '')) === 'MANAGER')
                                <a href="{{ route('kota.dashboard') }}" class="bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded-lg text-sm font-semibold transition">
                                    <i class="fa-solid fa-map-location-dot mr-1"></i> Panel Kota
                                </a>
                            @endif
                            <a href="{{ route('admin.api.docs') }}" class="hover:text-pink-200 text-sm font-medium {{ str_starts_with($currentRoute ?? '', 'admin.api') ? 'text-pink-200' : '' }}">
                                <i class="fa-solid fa-book mr-1"></i> API Docs
                            </a>
                            <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="hover:text-red-200 text-sm font-medium">Logout</button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('admin.login') }}" class="bg-white text-pink-700 hover:bg-pink-50 px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                            <i class="fa-solid fa-lock mr-1"></i> Admin Login
                        </a>
                    @endauth
                </div>
            </div>
        </div>
        @auth
            @if(\App\Http\Access::canAdmin())
            <!-- Admin Top Menu (persistent on all admin pages) -->
            <div class="bg-pink-950">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <nav class="flex flex-wrap gap-1 py-2 text-sm">
                        @php
                            $adminNav = \App\Http\Access::adminNav();
                            $currentRoute = request()->route() ? (request()->route()->getName() ?? '') : '';
                        @endphp
                        @foreach($adminNav as $item)
                            <a href="{{ route($item['route']) }}" class="px-3 py-1.5 rounded-lg font-medium transition {{ $currentRoute === $item['route'] || str_starts_with($currentRoute, rtrim(str_replace('.index','',$item['route']), '.') . '.') ? 'bg-pink-500 text-white' : 'text-pink-100 hover:bg-pink-900/60' }}">
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
                <div class="bg-blue-900">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <nav class="flex flex-wrap gap-1 py-2 text-sm">
                            @php
                                $kotaNav = \App\Http\Controllers\KotaController::kotaNav();
                                $currentRoute = request()->route() ? (request()->route()->getName() ?? '') : '';
                            @endphp
                            @foreach($kotaNav as $item)
                                @if(isset($item['external']))
                                    <a href="{{ route($item['route']) }}" target="_blank" class="px-3 py-1.5 rounded-lg font-medium transition text-blue-100 hover:bg-blue-800">
                                        <i class="fa-solid {{ $item['icon'] }} mr-1"></i> {{ $item['label'] }}
                                    </a>
                                @else
                                    <a href="{{ route($item['route']) }}" class="px-3 py-1.5 rounded-lg font-medium transition {{ $currentRoute === $item['route'] || str_starts_with($currentRoute, rtrim(str_replace('.index','',$item['route']), '.') . '.') ? 'bg-pink-500 text-white' : 'text-pink-100 hover:bg-pink-900/60' }}">
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
                <div class="bg-[#140823]">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <nav class="flex flex-wrap gap-1 py-2 text-sm">
                            @php
                                $kotaNav = \App\Http\Controllers\KotaController::kotaNav();
                                $currentRoute = request()->route() ? (request()->route()->getName() ?? '') : '';
                            @endphp
                            @foreach($kotaNav as $item)
                                @if(isset($item['external']))
                                    <a href="{{ route($item['route']) }}" target="_blank" class="px-3 py-1.5 rounded-lg font-medium transition text-pink-100 hover:bg-pink-900/60">
                                        <i class="fa-solid {{ $item['icon'] }} mr-1"></i> {{ $item['label'] }}
                                    </a>
                                @else
                                    <a href="{{ route($item['route']) }}" class="px-3 py-1.5 rounded-lg font-medium transition {{ $currentRoute === $item['route'] || str_starts_with($currentRoute, rtrim(str_replace('.index','',$item['route']), '.') . '.') ? 'bg-pink-500 text-white' : 'text-pink-100 hover:bg-pink-900/60' }}">
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
                <div class="bg-pink-100 border-l-4 border-pink-600 text-pink-800 p-4 rounded shadow" role="alert">
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
                $apkCustomer = is_string($apkCustomer) ? $apkCustomer : 'https://ridesip.my.id/apk/customer.apk';
                $apkDriver = is_string($apkDriver) ? $apkDriver : 'https://ridesip.my.id/apk/driver.apk';
                $apkMerchant = is_string($apkMerchant) ? $apkMerchant : 'https://ridesip.my.id/apk/merchant.apk';
            @endphp
            <!-- Admin Footer: APK Downloads (permanen, URL di-settings page) -->
            <div class="bg-pink-50 border-t border-pink-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <span class="text-sm font-semibold text-pink-900"><i class="fa-solid fa-mobile-screen mr-1"></i> Unduh APK (Build Terbaru):</span>
                        <a href="{{ $apkCustomer }}" target="_blank" class="inline-flex items-center space-x-1 bg-pink-700 hover:bg-pink-800 text-white text-xs font-semibold px-4 py-2 rounded-full shadow transition">
                            <i class="fa-solid fa-download"></i>
                            <span>Customer</span>
                        </a>
                        <a href="{{ $apkDriver }}" target="_blank" class="inline-flex items-center space-x-1 bg-blue-700 hover:bg-blue-800 text-white text-xs font-semibold px-4 py-2 rounded-full shadow transition">
                            <i class="fa-solid fa-download"></i>
                            <span>Driver</span>
                        </a>
                        <a href="{{ $apkMerchant }}" target="_blank" class="inline-flex items-center space-x-1 bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold px-4 py-2 rounded-full shadow transition">
                            <i class="fa-solid fa-download"></i>
                            <span>Merchant</span>
                        </a>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    <!-- Footer -->
    <footer class="brand-gradient text-gray-200 py-10 mt-12 border-t border-pink-900/50">
        <div class="max-w-7xl mx-auto px-4 flex flex-col items-center">
            <img src="/images/logo-footer-v2.png" alt="RideSip — Superapp Ride &amp; Delivery" class="w-72 md:w-96 mb-5 drop-shadow-lg">
            <p class="text-sm text-pink-200">&copy; {{ date('Y') }} RideSip Superapp — ridesip.my.id</p>
        </div>
    </footer>
</body>
</html>
