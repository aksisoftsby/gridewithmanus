<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Investasi Gride SuperApp - Solusi Ekosistem Digital Terintegrasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .gradient-text {
            background: linear-gradient(135deg, #10b981 0%, #065f46 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .bg-glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .proposal-card:hover {
            transform: translateY(-5px);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 overflow-x-hidden">

    <!-- Hero Section -->
    <header class="relative min-h-screen flex items-center justify-center overflow-hidden bg-slate-900">
        <div class="absolute inset-0 z-0 opacity-30">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-emerald-500 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-teal-600 rounded-full blur-[120px]"></div>
        </div>
        
        <div class="container mx-auto px-6 relative z-10 text-center">
            <div class="inline-flex items-center space-x-2 bg-emerald-500/10 border border-emerald-500/20 px-4 py-2 rounded-full mb-8">
                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                <span class="text-emerald-400 text-sm font-semibold tracking-wide uppercase">Investment Proposal 2026</span>
            </div>
            <h1 class="text-5xl md:text-7xl lg:text-8xl font-extrabold text-white mb-6 leading-tight">
                Masa Depan Ekonomi <br>
                <span class="gradient-text">Digital di Tangan Anda.</span>
            </h1>
            <p class="text-xl text-slate-400 max-w-3xl mx-auto mb-10 leading-relaxed">
                Gride SuperApp: Ekosistem All-in-One yang menghubungkan Pelanggan, Merchant, dan Driver dalam satu platform cerdas, efisien, dan menguntungkan.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="#fitur" class="bg-emerald-600 hover:bg-emerald-500 text-white px-10 py-4 rounded-2xl font-bold text-lg transition-all shadow-lg shadow-emerald-900/20">
                    Jelajahi Fitur Utama
                </a>
                <a href="#pricing" class="bg-white/10 hover:bg-white/20 text-white border border-white/20 px-10 py-4 rounded-2xl font-bold text-lg transition-all backdrop-blur-sm">
                    Lihat Penawaran Spesial
                </a>
            </div>
        </div>
        
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 animate-bounce text-white/30 text-2xl">
            <i class="fa-solid fa-chevron-down"></i>
        </div>
    </header>

    <!-- The Ecosystem Section -->
    <section id="fitur" class="py-24 container mx-auto px-6">
        <div class="text-center mb-20">
            <h2 class="text-sm font-bold text-emerald-600 uppercase tracking-[0.2em] mb-3">Ekosistem Terintegrasi</h2>
            <h3 class="text-4xl md:text-5xl font-bold text-slate-900">3 Aplikasi, 1 Solusi Sempurna.</h3>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Customer App -->
            <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 proposal-card">
                <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center text-emerald-600 text-2xl mb-8">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                </div>
                <h4 class="text-2xl font-bold mb-4 text-slate-900">Gride Customer</h4>
                <p class="text-slate-500 mb-6">Aplikasi untuk pengguna akhir dengan pengalaman belanja yang mulus dan fitur terlengkap.</p>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <i class="fa-solid fa-check-circle text-emerald-500 mt-1 mr-3"></i>
                        <span class="text-sm font-medium text-slate-700"><b>GrideFood & Mart:</b> Pesan makanan dan belanja kebutuhan harian dengan filter lokasi real-time.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-check-circle text-emerald-500 mt-1 mr-3"></i>
                        <span class="text-sm font-medium text-slate-700"><b>Maps Point Picker:</b> Pilih lokasi penjemputan & tujuan langsung dari peta dengan presisi tinggi.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-check-circle text-emerald-500 mt-1 mr-3"></i>
                        <span class="text-sm font-medium text-slate-700"><b>Auto-Slide News:</b> Update promo dan berita terbaru langsung di beranda aplikasi.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-check-circle text-emerald-500 mt-1 mr-3"></i>
                        <span class="text-sm font-medium text-slate-700"><b>Wallet & History:</b> Manajemen saldo dan riwayat transaksi transparan.</span>
                    </li>
                </ul>
            </div>

            <!-- Driver App -->
            <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 proposal-card">
                <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600 text-2xl mb-8">
                    <i class="fa-solid fa-motorcycle"></i>
                </div>
                <h4 class="text-2xl font-bold mb-4 text-slate-900">Gride Driver</h4>
                <p class="text-slate-500 mb-6">Didesain khusus untuk efisiensi kerja mitra pengemudi dan kurir di lapangan.</p>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <i class="fa-solid fa-check-circle text-blue-500 mt-1 mr-3"></i>
                        <span class="text-sm font-medium text-slate-700"><b>Real-time Tracking:</b> Update lokasi GPS otomatis untuk penugasan order yang akurat.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-check-circle text-blue-500 mt-1 mr-3"></i>
                        <span class="text-sm font-medium text-slate-700"><b>Wallet Driver:</b> Pantau pendapatan real-time dan saldo yang siap ditarik (withdraw).</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-check-circle text-blue-500 mt-1 mr-3"></i>
                        <span class="text-sm font-medium text-slate-700"><b>Earnings History:</b> Laporan detail pendapatan per order (driver_net) secara otomatis.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-check-circle text-blue-500 mt-1 mr-3"></i>
                        <span class="text-sm font-medium text-slate-700"><b>Universal Login:</b> Kemudahan login lintas aplikasi untuk mitra multifungsi.</span>
                    </li>
                </ul>
            </div>

            <!-- Merchant App -->
            <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 proposal-card">
                <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600 text-2xl mb-8">
                    <i class="fa-solid fa-store"></i>
                </div>
                <h4 class="text-2xl font-bold mb-4 text-slate-900">Gride Merchant</h4>
                <p class="text-slate-500 mb-6">Pusat kendali bisnis bagi mitra toko dan restoran untuk meningkatkan penjualan.</p>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <i class="fa-solid fa-check-circle text-orange-500 mt-1 mr-3"></i>
                        <span class="text-sm font-medium text-slate-700"><b>Product Management:</b> Tambah, edit, dan kelola stok produk/menu secara mandiri.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-check-circle text-orange-500 mt-1 mr-3"></i>
                        <span class="text-sm font-medium text-slate-700"><b>Order Management:</b> Terima dan proses pesanan masuk dengan notifikasi real-time.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-check-circle text-orange-500 mt-1 mr-3"></i>
                        <span class="text-sm font-medium text-slate-700"><b>Business Insight:</b> Laporan saldo merchant dan riwayat transaksi harian.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fa-solid fa-check-circle text-orange-500 mt-1 mr-3"></i>
                        <span class="text-sm font-medium text-slate-700"><b>Store Settings:</b> Update profil toko, jam operasional, dan lokasi merchant.</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <!-- App Distribution -->
    <section class="py-24 bg-white border-y border-slate-100">
        <div class="container mx-auto px-6">
            <div class="text-center mb-14">
                <h2 class="text-sm font-bold text-emerald-600 uppercase tracking-[0.2em] mb-3">Ready for Distribution</h2>
                <h3 class="text-4xl md:text-5xl font-bold text-slate-900">Hadir di Perangkat Pelanggan Anda.</h3>
                <p class="text-slate-500 max-w-2xl mx-auto mt-5 leading-relaxed">Gride disiapkan sebagai ekosistem multi-platform. Customer dapat menjangkau pengguna Android dan iOS, sementara aplikasi operasional Merchant dan Driver fokus pada distribusi Android yang efisien.</p>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
                <div class="rounded-3xl bg-slate-900 p-7 text-white shadow-xl shadow-slate-200/50">
                    <div class="flex items-center justify-between mb-7">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-2xl"><i class="fa-solid fa-mobile-screen-button"></i></div>
                        <span class="text-[10px] uppercase tracking-widest font-bold text-emerald-300 bg-emerald-500/10 px-3 py-1.5 rounded-full">End User</span>
                    </div>
                    <h4 class="text-2xl font-bold mb-2">Gride Customer</h4>
                    <p class="text-slate-400 text-sm mb-7">Satu aplikasi untuk pesan makanan, belanja, kirim barang, dan layanan antar.</p>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 rounded-2xl bg-white/10 border border-white/10 px-4 py-3"><i class="fa-brands fa-google-play text-xl text-emerald-400"></i><div><div class="text-[10px] text-slate-400 uppercase tracking-wider">Build</div><div class="font-semibold text-sm">Android APK / Google Play</div></div></div>
                        <div class="flex items-center gap-3 rounded-2xl bg-white/10 border border-white/10 px-4 py-3"><i class="fa-brands fa-apple text-2xl text-white"></i><div><div class="text-[10px] text-slate-400 uppercase tracking-wider">Build</div><div class="font-semibold text-sm">iOS / App Store Ready</div></div></div>
                    </div>
                </div>
                <div class="rounded-3xl bg-blue-600 p-7 text-white shadow-xl shadow-blue-200/50">
                    <div class="flex items-center justify-between mb-7">
                        <div class="w-14 h-14 rounded-2xl bg-white/15 flex items-center justify-center text-white text-2xl"><i class="fa-solid fa-motorcycle"></i></div>
                        <span class="text-[10px] uppercase tracking-widest font-bold text-blue-100 bg-white/10 px-3 py-1.5 rounded-full">Operations</span>
                    </div>
                    <h4 class="text-2xl font-bold mb-2">Gride Driver</h4>
                    <p class="text-blue-100 text-sm mb-7">Aplikasi mitra untuk menerima order, memperbarui lokasi, dan memantau pendapatan.</p>
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 border border-white/15 px-4 py-3"><i class="fa-brands fa-google-play text-xl text-white"></i><div><div class="text-[10px] text-blue-100 uppercase tracking-wider">Distribution</div><div class="font-semibold text-sm">Android APK / Google Play</div></div></div>
                </div>
                <div class="rounded-3xl bg-orange-500 p-7 text-white shadow-xl shadow-orange-200/50">
                    <div class="flex items-center justify-between mb-7">
                        <div class="w-14 h-14 rounded-2xl bg-white/15 flex items-center justify-center text-white text-2xl"><i class="fa-solid fa-store"></i></div>
                        <span class="text-[10px] uppercase tracking-widest font-bold text-orange-100 bg-white/10 px-3 py-1.5 rounded-full">Business</span>
                    </div>
                    <h4 class="text-2xl font-bold mb-2">Gride Merchant</h4>
                    <p class="text-orange-100 text-sm mb-7">Pusat kendali merchant untuk mengelola toko, produk, order, saldo, dan transaksi.</p>
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 border border-white/15 px-4 py-3"><i class="fa-brands fa-google-play text-xl text-white"></i><div><div class="text-[10px] text-orange-100 uppercase tracking-wider">Distribution</div><div class="font-semibold text-sm">Android APK / Google Play</div></div></div>
                </div>
            </div>
            <div class="mt-10 flex flex-wrap justify-center gap-3 text-xs font-semibold text-slate-500">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-50 border border-slate-200"><i class="fa-solid fa-code-branch text-emerald-600"></i> One shared backend API</span>
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-50 border border-slate-200"><i class="fa-solid fa-arrows-rotate text-emerald-600"></i> Cross-platform release workflow</span>
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-50 border border-slate-200"><i class="fa-solid fa-cloud-arrow-up text-emerald-600"></i> Production deployment support</span>
            </div>
        </div>
    </section>

    <!-- Technical Prowess -->
    <section class="py-24 bg-slate-900 text-white overflow-hidden relative">
        <div class="container mx-auto px-6 relative z-10">
            <div class="flex flex-col lg:flex-row items-center gap-16">
                <div class="lg:w-1/2">
                    <h2 class="text-sm font-bold text-emerald-400 uppercase tracking-[0.2em] mb-3 text-left">Teknologi Mutakhir</h2>
                    <h3 class="text-4xl md:text-5xl font-bold mb-8">Infrastruktur Skala Enterprise.</h3>
                    <p class="text-slate-400 text-lg mb-10 leading-relaxed">
                        Dibangun dengan stack teknologi modern untuk menjamin performa tinggi, keamanan data, dan kemudahan skalabilitas bisnis di masa depan.
                    </p>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center text-emerald-400">
                                <i class="fa-brands fa-laravel text-2xl"></i>
                            </div>
                            <div>
                                <h5 class="font-bold">Laravel 11</h5>
                                <p class="text-xs text-slate-500">Backend API Robust</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center text-blue-400">
                                <i class="fa-brands fa-flutter text-2xl"></i>
                            </div>
                            <div>
                                <h5 class="font-bold">Flutter 3.x</h5>
                                <p class="text-xs text-slate-500">Cross-platform Mobile</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center text-indigo-400">
                                <i class="fa-solid fa-database text-2xl"></i>
                            </div>
                            <div>
                                <h5 class="font-bold">PostgreSQL 15+</h5>
                                <p class="text-xs text-slate-500">High Performance DB</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center text-teal-400">
                                <i class="fa-solid fa-shield-halved text-2xl"></i>
                            </div>
                            <div>
                                <h5 class="font-bold">CI/CD & Cloud</h5>
                                <p class="text-xs text-slate-500">Auto Deployment</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lg:w-1/2 relative">
                    <div class="bg-emerald-500/20 absolute inset-0 blur-[100px] rounded-full"></div>
                    <div class="bg-glass border border-white/10 p-8 rounded-[2.5rem] relative z-20">
                        <div class="flex items-center justify-between mb-8">
                            <h5 class="text-slate-900 font-bold text-xl">Admin Control Center</h5>
                            <span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full uppercase">Super Admin</span>
                        </div>
                        <div class="space-y-4">
                            <div class="h-4 bg-slate-200 rounded-full w-3/4"></div>
                            <div class="h-4 bg-slate-100 rounded-full w-1/2"></div>
                            <div class="grid grid-cols-3 gap-4 mt-6">
                                <div class="h-20 bg-emerald-50 rounded-2xl flex flex-col items-center justify-center">
                                    <span class="text-emerald-600 font-bold">1.2k</span>
                                    <span class="text-[10px] text-emerald-400 uppercase">Orders</span>
                                </div>
                                <div class="h-20 bg-blue-50 rounded-2xl flex flex-col items-center justify-center">
                                    <span class="text-blue-600 font-bold">850</span>
                                    <span class="text-[10px] text-blue-400 uppercase">Drivers</span>
                                </div>
                                <div class="h-20 bg-orange-50 rounded-2xl flex flex-col items-center justify-center">
                                    <span class="text-orange-600 font-bold">420</span>
                                    <span class="text-[10px] text-orange-400 uppercase">Merchants</span>
                                </div>
                            </div>
                            <div class="mt-6 p-4 bg-slate-900 rounded-xl border border-white/10">
                                <code class="text-xs text-emerald-400 font-mono">
                                    > php artisan gride:deploy --success<br>
                                    > system status: OPTIMIZED
                                </code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-24 container mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="text-sm font-bold text-emerald-600 uppercase tracking-[0.2em] mb-3">Investment Opportunity</h2>
            <h3 class="text-4xl md:text-5xl font-bold text-slate-900">Penawaran Eksklusif Terbatas.</h3>
        </div>

        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-[3rem] shadow-2xl shadow-emerald-200/50 border-4 border-emerald-500 relative overflow-hidden">
                <!-- Promo Badge -->
                <div class="absolute top-12 -right-12 bg-yellow-400 text-slate-900 font-bold py-2 px-12 rotate-45 text-sm uppercase tracking-wider shadow-md">
                    Limited Time Offer
                </div>

                <div class="p-12 text-center border-b border-slate-100">
                    <h4 class="text-2xl font-bold text-slate-900 mb-4 uppercase tracking-widest">SuperApp Full License</h4>
                    <div class="flex items-center justify-center space-x-4 mb-4">
                        <span class="text-3xl text-slate-400 line-through font-bold">Rp 100.000.000</span>
                        <span class="bg-emerald-100 text-emerald-700 text-sm font-bold px-4 py-1 rounded-full">Save 50%</span>
                    </div>
                    <div class="text-7xl md:text-8xl font-black text-slate-900 mb-6">
                        <span class="text-4xl align-top mr-2">Rp</span>50<span class="text-4xl ml-2">Juta</span>
                    </div>
                    <p class="text-slate-500 font-medium">Investasi satu kali untuk kepemilikan penuh sistem Gride SuperApp.</p>
                </div>

                <div class="p-12 bg-emerald-50/30">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h5 class="font-bold text-slate-900 mb-6 flex items-center">
                                <i class="fa-solid fa-gift text-emerald-600 mr-2"></i> Paket Pembelian Termasuk:
                            </h5>
                            <ul class="space-y-4 text-left">
                                <li class="flex items-center text-slate-700">
                                    <i class="fa-solid fa-circle-check text-emerald-500 mr-3"></i>
                                    Full Source Code (Laravel & Flutter)
                                </li>
                                <li class="flex items-center text-slate-700">
                                    <i class="fa-solid fa-circle-check text-emerald-500 mr-3"></i>
                                    Setup VPS & Domain Production
                                </li>
                                <li class="flex items-center text-slate-700">
                                    <i class="fa-solid fa-circle-check text-emerald-500 mr-3"></i>
                                    Custom Branding & Logo
                                </li>
                                <li class="flex items-center text-slate-700 font-bold text-emerald-700">
                                    <i class="fa-solid fa-circle-check text-emerald-500 mr-3"></i>
                                    Free Maintenance 6 Bulan Penuh
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h5 class="font-bold text-slate-900 mb-6 flex items-center">
                                <i class="fa-solid fa-hand-holding-heart text-emerald-600 mr-2"></i> Komitmen Kami:
                            </h5>
                            <p class="text-sm text-slate-600 leading-relaxed mb-4">
                                Kami tidak hanya menjual aplikasi, kami membantu Anda sampai <b>Launching & Jalan Normal</b>. 
                                Seluruh revisi bug dan perbaikan teknis selama 6 bulan pertama ditanggung sepenuhnya oleh tim kami.
                            </p>
                            <div class="p-4 bg-white rounded-2xl border border-emerald-100 shadow-sm">
                                <p class="text-xs text-slate-400 uppercase font-bold mb-1">Garansi Launching</p>
                                <p class="text-sm font-bold text-slate-800 italic">"Kami dampingi sampai transaksi pertama Anda sukses!"</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Maintenance Details -->
    <section class="py-24 bg-slate-50">
        <div class="container mx-auto px-6">
            <div class="max-w-4xl mx-auto bg-white p-12 rounded-[2.5rem] shadow-xl border border-slate-100">
                <div class="flex flex-col md:flex-row items-center justify-between mb-12 gap-6">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 mb-2">Paket Maintenance Tahunan</h2>
                        <p class="text-slate-500">Menjamin keberlangsungan bisnis Anda tanpa kendala teknis.</p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-black text-emerald-600">Rp 10.000.000 <span class="text-sm font-normal text-slate-400">/ tahun</span></div>
                        <p class="text-xs text-slate-400 mt-1">*Berlaku setelah masa free 6 bulan habis</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                    <div class="flex space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div>
                            <h6 class="font-bold text-slate-900 mb-1">Cloud Backup Rutin</h6>
                            <p class="text-sm text-slate-500">Backup database dan aset file setiap 24 jam untuk mencegah kehilangan data.</p>
                        </div>
                    </div>
                    <div class="flex space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                            <i class="fa-solid fa-shield-virus"></i>
                        </div>
                        <div>
                            <h6 class="font-bold text-slate-900 mb-1">Security Patch Update</h6>
                            <p class="text-sm text-slate-500">Pembaruan rutin library dan framework untuk menjaga sistem dari celah keamanan.</p>
                        </div>
                    </div>
                    <div class="flex space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center text-orange-600">
                            <i class="fa-solid fa-gauge-high"></i>
                        </div>
                        <div>
                            <h6 class="font-bold text-slate-900 mb-1">Server Optimization</h6>
                            <p class="text-sm text-slate-500">Pemantauan load server dan optimasi performa agar aplikasi tetap ringan & cepat.</p>
                        </div>
                    </div>
                    <div class="flex space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600">
                            <i class="fa-solid fa-headset"></i>
                        </div>
                        <div>
                            <h6 class="font-bold text-slate-900 mb-1">Technical Support 24/7</h6>
                            <p class="text-sm text-slate-500">Bantuan teknis darurat jika terjadi downtime atau masalah kritis pada sistem.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <footer class="py-24 bg-emerald-600 text-white text-center">
        <div class="container mx-auto px-6">
            <h2 class="text-4xl md:text-5xl font-bold mb-8">Siap Membangun Kerajaan Digital Anda?</h2>
            <p class="text-xl text-emerald-100 mb-12 max-w-2xl mx-auto">
                Kesempatan investasi dengan harga spesial ini tidak akan datang dua kali. Jadilah yang pertama menguasai pasar lokal dengan Gride SuperApp.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                <a href="https://wa.me/6281234567890" target="_blank" class="bg-white text-emerald-600 px-12 py-5 rounded-2xl font-black text-xl hover:scale-105 transition shadow-2xl">
                    <i class="fa-brands fa-whatsapp mr-2"></i> Hubungi Kami Sekarang
                </a>
                <a href="/" class="text-white hover:text-emerald-200 font-bold underline underline-offset-8">
                    Kembali ke Beranda
                </a>
            </div>
            <div class="mt-20 pt-10 border-t border-emerald-500/30 text-emerald-200 text-sm">
                &copy; 2026 Gride SuperApp. All rights reserved. Developed by Aksisoft Team.
            </div>
        </div>
    </footer>

</body>
</html>
