<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Gride Superapp' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased min-h-screen flex flex-col">
    <!-- Navbar -->
    <nav class="bg-emerald-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('home') }}" class="text-xl font-bold tracking-wider flex items-center space-x-2">
                        <i class="fa-solid fa-bolt-lightning text-yellow-300"></i>
                        <span>Gride Superapp</span>
                    </a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="{{ route('home') }}" class="hover:text-yellow-200 font-medium">Beranda</a>
                    <a href="{{ route('api.docs') }}" class="hover:text-yellow-200 font-medium">API Docs</a>
                    @auth
                        @if(Auth::user()->role === 'ADMIN')
                            <a href="{{ route('admin.dashboard') }}" class="bg-emerald-700 hover:bg-emerald-800 px-3 py-2 rounded-lg text-sm font-semibold transition">
                                <i class="fa-solid fa-gauge mr-1"></i> Admin Panel
                            </a>
                            <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="hover:text-red-200 text-sm font-medium">Logout</button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('admin.login') }}" class="bg-white text-emerald-700 hover:bg-emerald-50 px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                            <i class="fa-solid fa-lock mr-1"></i> Admin Login
                        </a>
                    @endauth
                </div>
            </div>
        </div>
        @auth
            @if(Auth::user()->role === 'ADMIN')
            <!-- Admin Top Menu (persistent on all admin pages) -->
            <div class="bg-emerald-800">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <nav class="flex flex-wrap gap-1 py-2 text-sm">
                        @php
                            $adminNav = \App\Http\Controllers\AdminController::adminNav();
                            $currentRoute = request()->route() ? (request()->route()->getName() ?? '') : '';
                        @endphp
                        @foreach($adminNav as $item)
                            <a href="{{ route($item['route']) }}" class="px-3 py-1.5 rounded-lg font-medium transition {{ $currentRoute === $item['route'] || str_starts_with($currentRoute, rtrim(str_replace('.index','',$item['route']), '.') . '.') ? 'bg-yellow-400 text-emerald-900' : 'text-emerald-100 hover:bg-emerald-700' }}">
                                <i class="fa-solid {{ $item['icon'] }} mr-1"></i> {{ $item['label'] }}
                            </a>
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
                <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded shadow" role="alert">
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

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-6 mt-12 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm">
            <p>&copy; 2026 SuperApp Ecosystem (Laravel 11). Built with schema.sql & app-info.md specifications.</p>
        </div>
    </footer>
</body>
</html>
