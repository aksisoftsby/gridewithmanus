@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
            <p class="text-sm text-gray-500">Pengaturan tarif, komisi, dan tautan unduhan aplikasi RideSip.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-pink-700 hover:underline text-sm font-semibold">&larr; Dashboard</a>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-pink-50 border border-pink-200 text-pink-900 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf

        {{-- RIDE --}}
        <div class="bg-white rounded-xl shadow border border-gray-100 p-6 mb-6">
            <h3 class="font-bold text-gray-800 text-lg mb-4"><i class="fa-solid fa-car text-pink-700 mr-2"></i>Tarif Ride / Antar-Jemput</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Biaya per KM</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-400 text-xs">Rp</span>
                        <input type="number" min="0" step="1" name="ride_cost_per_km" value="{{ $settings['ride_cost_per_km'] }}" class="w-full border border-gray-300 rounded-lg pl-8 pr-3 py-2 focus:ring-2 focus:ring-pink-600 focus:border-pink-600">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Tarif jarak: Jarak (KM) &times; Biaya per KM.</p>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Tarif Dasar (Base Fare)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-400 text-xs">Rp</span>
                        <input type="number" min="0" step="1" name="ride_base_fare" value="{{ $settings['ride_base_fare'] }}" class="w-full border border-gray-300 rounded-lg pl-8 pr-3 py-2 focus:ring-2 focus:ring-pink-600 focus:border-pink-600">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Ditambahkan ke biaya jarak. Default Rp 10.000.</p>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Potongan Komisi Admin Ride</label>
                    <select name="admin_ride_commission_enabled" id="adminRideEnabled" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-pink-600 focus:border-pink-600">
                        <option value="OFF" {{ $settings['admin_ride_commission_enabled'] === 'OFF' ? 'selected' : '' }}>OFF</option>
                        <option value="ON" {{ $settings['admin_ride_commission_enabled'] === 'ON' ? 'selected' : '' }}>ON</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Nominal Potongan Admin Ride</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-400 text-xs">Rp</span>
                        <input type="number" min="0" step="1" id="adminRideAmount" name="admin_ride_commission_amount" value="{{ $settings['admin_ride_commission_amount'] }}" class="w-full border border-gray-300 rounded-lg pl-8 pr-3 py-2 focus:ring-2 focus:ring-pink-600 focus:border-pink-600">
                    </div>
                </div>
            </div>
        </div>

        {{-- FOOD --}}
        <div class="bg-white rounded-xl shadow border border-gray-100 p-6 mb-6">
            <h3 class="font-bold text-gray-800 text-lg mb-4"><i class="fa-solid fa-utensils text-pink-700 mr-2"></i>Komisi Food / Restro</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Komisi Food (Restro) %</label>
                    <div class="relative">
                        <input type="number" min="0" max="100" step="0.01" name="food_commission_pct" value="{{ $settings['food_commission_pct'] }}" class="w-full border border-gray-300 rounded-lg px-3 pr-8 py-2 focus:ring-2 focus:ring-pink-600 focus:border-pink-600">
                        <span class="absolute right-3 top-2 text-gray-400 text-xs">%</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Dipotong dari subtotal makanan setiap order Food/Mart.</p>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Potongan Komisi Admin Food</label>
                    <select name="admin_food_commission_enabled" id="adminFoodEnabled" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-pink-600 focus:border-pink-600">
                        <option value="OFF" {{ $settings['admin_food_commission_enabled'] === 'OFF' ? 'selected' : '' }}>OFF</option>
                        <option value="ON" {{ $settings['admin_food_commission_enabled'] === 'ON' ? 'selected' : '' }}>ON</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block font-medium text-gray-700 mb-1">Nominal Potongan Admin Food</label>
                    <div class="relative max-w-sm">
                        <span class="absolute left-3 top-2 text-gray-400 text-xs">Rp</span>
                        <input type="number" min="0" step="1" id="adminFoodAmount" name="admin_food_commission_amount" value="{{ $settings['admin_food_commission_amount'] }}" class="w-full border border-gray-300 rounded-lg pl-8 pr-3 py-2 focus:ring-2 focus:ring-pink-600 focus:border-pink-600">
                    </div>
                </div>
            </div>
        </div>

        {{-- SHOP --}}
        <div class="bg-white rounded-xl shadow border border-gray-100 p-6 mb-6">
            <h3 class="font-bold text-gray-800 text-lg mb-4"><i class="fa-solid fa-store text-pink-700 mr-2"></i>Komisi Toko / Shop</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Potongan Komisi Admin Toko / Shop</label>
                    <select name="admin_shop_commission_enabled" id="adminShopEnabled" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-pink-600 focus:border-pink-600">
                        <option value="OFF" {{ $settings['admin_shop_commission_enabled'] === 'OFF' ? 'selected' : '' }}>OFF</option>
                        <option value="ON" {{ $settings['admin_shop_commission_enabled'] === 'ON' ? 'selected' : '' }}>ON</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Nominal Potongan Admin Toko</label>
                    <div class="relative max-w-sm">
                        <span class="absolute left-3 top-2 text-gray-400 text-xs">Rp</span>
                        <input type="number" min="0" step="1" id="adminShopAmount" name="admin_shop_commission_amount" value="{{ $settings['admin_shop_commission_amount'] }}" class="w-full border border-gray-300 rounded-lg pl-8 pr-3 py-2 focus:ring-2 focus:ring-pink-600 focus:border-pink-600">
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">Nilai komisi dan potongan dicatat (snapshot) pada setiap order pada saat dibuat. Perubahan setting di sini tidak mengubah transaksi yang sudah terjadi.</p>
        </div>

        {{-- APK DOWNLOAD --}}
        <div class="bg-white rounded-xl shadow border border-gray-100 p-6 mb-6">
            <h3 class="font-bold text-gray-800 text-lg mb-4"><i class="fa-solid fa-download text-pink-700 mr-2"></i>Tautan Unduh APK</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Customer</label>
                    <input type="url" name="apk_download_url_customer" value="{{ $settings['apk_download_url_customer'] }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-pink-600 focus:border-pink-600">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Driver</label>
                    <input type="url" name="apk_download_url_driver" value="{{ $settings['apk_download_url_driver'] }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-pink-600 focus:border-pink-600">
                </div>
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Merchant</label>
                    <input type="url" name="apk_download_url_merchant" value="{{ $settings['apk_download_url_merchant'] }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-pink-600 focus:border-pink-600">
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">APK di-host di <code class="bg-gray-100 px-1 rounded">https://ridesip.my.id/apk/{customer,driver,merchant}.apk</code>. Tautan di bawah berasal dari build GitHub Actions terbaru.</p>

            <div class="flex items-center justify-between mt-4">
                <p class="text-xs text-gray-500">Tombol unduh APK di footer admin diambil dari tautan ini. Periode trial berakhir: {{ $trialEnds }}.</p>
                <button type="button" onclick="document.getElementById('refreshForm').submit()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    <i class="fa-solid fa-arrows-rotate mr-1"></i> Refresh dari GitHub
                </button>
            </div>
            @if($trialActive && $links)
                <div class="mt-4 overflow-x-auto">
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
                                        <a href="{{ $link['url'] }}" target="_blank" class="text-pink-700 hover:underline text-xs font-medium"><i class="fa-solid fa-download mr-1"></i> Unduh ZIP (GitHub)</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-pink-700 hover:bg-pink-800 text-white px-6 py-3 rounded-lg font-semibold transition">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan Pengaturan
            </button>
        </div>
    </form>

    <form id="refreshForm" action="{{ route('admin.settings.refresh-links') }}" method="POST" style="display:none">@csrf</form>
</div>

<script>
    // Toggle field nominal berdasarkan status ON/OFF
    function wireToggle(selectId, inputId) {
        const sel = document.getElementById(selectId);
        const inp = document.getElementById(inputId);
        const apply = () => { inp.disabled = sel.value !== 'ON'; inp.classList.toggle('opacity-50', sel.value !== 'ON'); };
        sel.addEventListener('change', apply);
        apply();
    }
    wireToggle('adminRideEnabled', 'adminRideAmount');
    wireToggle('adminFoodEnabled', 'adminFoodAmount');
    wireToggle('adminShopEnabled', 'adminShopAmount');
</script>
@endsection
