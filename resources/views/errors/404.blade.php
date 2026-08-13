<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan | Gride Superapp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .brand-gradient { background: linear-gradient(135deg, #374151 0%, #4c1d95 55%, #b45309 100%); }
        .float-anim { animation: floaty 4s ease-in-out infinite; }
        @keyframes floaty {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-14px); }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans antialiased flex flex-col min-h-screen">

    <!-- Header Gride -->
    <header class="brand-gradient text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="{{ url('/') }}" class="text-xl font-bold tracking-wider flex items-center space-x-2">
                    <i class="fa-solid fa-bolt-lightning text-yellow-300"></i>
                    <span>Gride Superapp</span>
                </a>
                <div class="flex items-center space-x-6">
                    <a href="{{ url('/') }}" class="hover:text-yellow-200 font-medium text-sm">Beranda</a>
                    <a href="{{ route('iklan.index') }}" class="hover:text-yellow-200 font-medium text-sm">Iklan Gratis</a>
                    <a href="{{ url('/proposal') }}" class="hover:text-yellow-200 font-medium text-sm">Proposal</a>
                    <a href="{{ route('admin.login') }}" class="bg-white text-emerald-700 hover:bg-emerald-50 px-4 py-2 rounded-lg text-sm font-semibold shadow transition">
                        <i class="fa-solid fa-lock mr-1"></i> Admin Login
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- 404 Content -->
    <main class="flex-grow flex items-center justify-center py-12">
        <div class="max-w-2xl mx-auto px-4 text-center">
            <div class="float-anim text-9xl font-black mb-6 bg-clip-text text-transparent bg-gradient-to-r from-purple-700 to-amber-600 leading-none">
                404
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900 mb-3">Halaman Tidak Ditemukan</h1>
            <p class="text-gray-600 mb-8 max-w-md mx-auto">
                Sepertinya Anda tersesat di jalan. Halaman yang Anda cari tidak ada, telah dipindahkan, atau sedang dalam perjalanan.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ url('/') }}" class="bg-gradient-to-r from-purple-700 to-amber-600 hover:from-purple-800 hover:to-amber-700 text-white px-8 py-3.5 rounded-full font-semibold shadow-lg transition inline-flex items-center">
                    <i class="fa-solid fa-house mr-2"></i> Kembali ke Beranda
                </a>
                <a href="{{ route('iklan.index') }}" class="bg-white hover:bg-gray-50 text-purple-700 border border-purple-200 px-8 py-3.5 rounded-full font-semibold shadow transition inline-flex items-center">
                    <i class="fa-solid fa-bullhorn mr-2"></i> Lihat Iklan Gratis
                </a>
                <button onclick="history.back()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-8 py-3.5 rounded-full font-semibold transition inline-flex items-center">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Halaman Sebelumnya
                </button>
            </div>

            <div class="mt-12 grid grid-cols-3 gap-4 max-w-md mx-auto text-sm">
                <a href="{{ url('/') }}" class="bg-white rounded-2xl shadow p-4 hover:shadow-lg transition">
                    <i class="fa-solid fa-motorcycle text-purple-600 text-2xl mb-2 block"></i>
                    <span class="font-semibold text-gray-700">Layanan Gride</span>
                </a>
                <a href="{{ route('iklan.index') }}" class="bg-white rounded-2xl shadow p-4 hover:shadow-lg transition">
                    <i class="fa-solid fa-store text-amber-600 text-2xl mb-2 block"></i>
                    <span class="font-semibold text-gray-700">Merchant</span>
                </a>
                <a href="{{ url('/proposal') }}" class="bg-white rounded-2xl shadow p-4 hover:shadow-lg transition">
                    <i class="fa-solid fa-file-lines text-emerald-600 text-2xl mb-2 block"></i>
                    <span class="font-semibold text-gray-700">Proposal</span>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer Gride -->
    <footer class="bg-gray-900 text-gray-300 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-white font-bold text-lg flex items-center mb-3">
                        <i class="fa-solid fa-bolt-lightning text-yellow-300 mr-2"></i> Gride Superapp
                    </h3>
                    <p class="text-sm text-gray-400">Ekosistem superapp Kediri: kirim barang, antar jemput, merchant lokal, GrSaldo, dan PPOB dalam satu aplikasi.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3">Tautan</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ url('/') }}" class="hover:text-yellow-300">Beranda</a></li>
                        <li><a href="{{ route('iklan.index') }}" class="hover:text-yellow-300">Iklan Gratis</a></li>
                        <li><a href="{{ url('/proposal') }}" class="hover:text-yellow-300">Proposal</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-3">Aplikasi</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ url('/apk/customer.apk') }}" class="hover:text-yellow-300"><i class="fa-solid fa-download mr-1"></i> Gride (Customer)</a></li>
                        <li><a href="{{ url('/apk/driver.apk') }}" class="hover:text-yellow-300"><i class="fa-solid fa-download mr-1"></i> Gride Driver</a></li>
                        <li><a href="{{ url('/apk/merchant.apk') }}" class="hover:text-yellow-300"><i class="fa-solid fa-download mr-1"></i> Gride Merchant</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-6 text-center text-xs text-gray-500">
                &copy; {{ date('Y') }} Gride Superapp — gride.web.id
            </div>
        </div>
    </footer>
</body>
</html>
