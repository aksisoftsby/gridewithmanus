@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 mb-8">
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-2xl font-bold">
                <i class="fa-solid fa-code"></i>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900">SuperApp REST API Documentation</h1>
                <p class="text-sm text-gray-500">Official REST endpoints corresponding to schema.sql and app-info.md specifications.</p>
            </div>
        </div>
        <p class="text-gray-700 leading-relaxed">
            API ini dirancang untuk integrasi Mobile App (Customer, Driver, Merchant) serta sistem pihak ketiga. Seluruh endpoint mengembalikan respons dalam format JSON standar.
        </p>
    </div>

    <!-- Endpoint List -->
    <div class="space-y-6">
        <!-- Endpoint 1 -->
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="bg-emerald-600 text-white px-6 py-3 flex justify-between items-center">
                <span class="font-mono font-bold text-sm bg-emerald-700 px-3 py-1 rounded">GET /api/merchants</span>
                <span class="text-xs text-emerald-100">Daftar Merchant Mitra</span>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">Mengambil daftar seluruh merchant aktif dengan opsi filter berdasarkan tipe layanan.</p>
                <h4 class="text-xs font-bold text-gray-700 uppercase mb-2">Query Parameters:</h4>
                <ul class="list-disc list-inside text-sm text-gray-600 mb-4">
                    <li><code>type</code> (optional): FOOD, MART, SHOP</li>
                </ul>
                <h4 class="text-xs font-bold text-gray-700 uppercase mb-2">Contoh Response (JSON):</h4>
                <pre class="bg-gray-900 text-emerald-400 p-4 rounded-xl text-xs overflow-x-auto font-mono">
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Restoran Padang Sederhana",
      "type": "FOOD",
      "city": "Jakarta",
      "rating": 4.9
    }
  ]
}
                </pre>
            </div>
        </div>

        <!-- Endpoint 2 -->
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="bg-emerald-600 text-white px-6 py-3 flex justify-between items-center">
                <span class="font-mono font-bold text-sm bg-emerald-700 px-3 py-1 rounded">GET /api/merchants/{id}/menu</span>
                <span class="text-xs text-emerald-100">Detail Menu & Produk Merchant</span>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">Mengambil informasi detail merchant beserta daftar menu makanan atau produk yang dijual.</p>
                <h4 class="text-xs font-bold text-gray-700 uppercase mb-2">Contoh Response (JSON):</h4>
                <pre class="bg-gray-900 text-emerald-400 p-4 rounded-xl text-xs overflow-x-auto font-mono">
{
  "status": "success",
  "merchant": { "id": 1, "name": "Restoran Padang Sederhana" },
  "menu": [
    { "id": 1, "name": "Paket Nasi Rendang Special", "price": 35000 }
  ]
}
                </pre>
            </div>
        </div>

        <!-- Endpoint 3 -->
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="bg-emerald-600 text-white px-6 py-3 flex justify-between items-center">
                <span class="font-mono font-bold text-sm bg-emerald-700 px-3 py-1 rounded">GET /api/products</span>
                <span class="text-xs text-emerald-100">Daftar Seluruh Produk & Menu</span>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">Mengambil katalog produk global dari seluruh merchant yang terdaftar.</p>
            </div>
        </div>

        <!-- Endpoint 4 -->
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="bg-emerald-600 text-white px-6 py-3 flex justify-between items-center">
                <span class="font-mono font-bold text-sm bg-emerald-700 px-3 py-1 rounded">GET /api/orders</span>
                <span class="text-xs text-emerald-100">Daftar Transaksi Pesanan</span>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">Mengambil riwayat pesanan terbaru dalam sistem SuperApp.</p>
            </div>
        </div>

        <!-- Endpoint 5 -->
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="bg-emerald-600 text-white px-6 py-3 flex justify-between items-center">
                <span class="font-mono font-bold text-sm bg-emerald-700 px-3 py-1 rounded">GET /api/promos</span>
                <span class="text-xs text-emerald-100">Daftar Promo & Voucher Aktif</span>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">Mengambil daftar kupon diskon dan promo yang sedang aktif.</p>
            </div>
        </div>
    </div>
</div>
@endsection
