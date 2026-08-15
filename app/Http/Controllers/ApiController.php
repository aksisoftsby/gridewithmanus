<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{

    /**
     * Proteksi endpoint API berbasis akun user:
     * hanya user dengan role MEMBER yang boleh mengakses.
     * Admin (platform) dan Manager (panel kota) DITOLAK (403).
     */
    protected function requireMember(Request $request)
    {
        $user = DB::table('users')->where('id', (int) $request->input('user_id', 0))->first();
        if (!$user) {
            $user = DB::table('users')->where('id', (int) $request->query('user_id', 0))->first();
        }
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'user_id tidak valid.'], 400);
        }
        $role = strtoupper((string) ($user->role ?? ''));
        if ($role !== 'MEMBER') {
            return response()->json(['status' => 'error', 'message' => 'Akun ini tidak diizinkan mengakses API (role: '.$role.').'], 403);
        }
        return null;
    }

    public function merchants(Request $request)
    {
        $type = $request->query('type');
        $search = $request->query('search');
        $lat = (float) $request->query('lat');
        $lng = (float) $request->query('lng');

        $query = DB::table('merchants')->where('status', 'ACTIVE');

        if ($type) {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('city', 'like', '%' . $search . '%');
            });
        }

        // Optional: sort by distance when customer sends current GPS location.
        // Distance computed in-app with PHP (Haversine) — lightweight and DB-agnostic.
        $merchants = $query->get();

        if ($lat && $lng) {
            foreach ($merchants as $m) {
                $m->distance_km = $this->haversineKm($lat, $lng, (float) $m->latitude, (float) $m->longitude);
            }
            $merchants = $merchants->sortBy('distance_km')->values();
        }

        return response()->json([
            'status' => 'success',
            'data' => $merchants
        ]);
    }

    public function merchantMenu($id)
    {
        $merchant = DB::table('merchants')->where('id', $id)->first();
        if (!$merchant) {
            return response()->json(['status' => 'error', 'message' => 'Merchant not found'], 404);
        }
        $menu = DB::table('menu_items')->where('merchant_id', $id)->get();
        return response()->json([
            'status' => 'success',
            'merchant' => $merchant,
            'menu' => $menu
        ]);
    }

    public function products(Request $request)
    {
        $search = $request->query('search');
        $query = DB::table('menu_items')
            ->join('merchants', 'menu_items.merchant_id', '=', 'merchants.id')
            ->select('menu_items.*', 'merchants.name as merchant_name');

        if ($search) {
            $query->where('menu_items.name', 'like', '%' . $search . '%');
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }

    public function orders(Request $request)
    {
        $driverId = $request->query('driver_id');
        $userId = $request->query('user_id');
        $merchantId = $request->query('merchant_id');
        $status = $request->query('status');
        $query = DB::table('orders');
        if ($driverId) {
            $query->where('driver_id', $driverId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->limit(100)->get();

        // Lampirkan invoice breakdown pada setiap order agar semua
        // aplikasi (customer/driver/merchant) bisa menampilkan komponen biaya.
        foreach ($orders as $order) {
            $order->invoice = $this->buildInvoiceBreakdown($order);
        }

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    public function promos(Request $request)
    {
        $search = $request->query('search');
        $query = DB::table('promos')->where('is_active', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }

    public function news(Request $request)
    {
        $limit = min((int) $request->query('limit', 10), 50);
        $query = DB::table('news')
            ->leftJoin('news_categories', 'news.news_category_id', '=', 'news_categories.id')
            ->select('news.*', 'news_categories.name as category_name')
            ->where('status', 'PUBLISHED')
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'desc');

        if (!DB::getSchemaBuilder()->hasTable('news')) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->limit($limit)->get()
        ]);
    }

    public function testimonials(Request $request)
    {
        $limit = min((int) $request->query('limit', 10), 50);
        $query = DB::table('testimonials')->where('is_published', true)->orderBy('rating', 'desc');
        return response()->json([
            'status' => 'success',
            'data' => $query->limit($limit)->get()
        ]);
    }

    /**
     * Driver location check-in. The driver app sends its GPS location so
     * admins can track drivers and customers can see where drivers are.
     * POST /api/drivers/{id}/location { latitude, longitude, speed?, heading? }
     */
    public function driverLocationUpdate(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $driver = DB::table('drivers')->where('id', $id)->first();
        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver not found'], 404);
        }

        $validated = $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|min:0|max:360',
            'ride_id' => 'nullable|integer|min:1',
        ]);

        $validated['latitude'] = $validated['latitude'] ?? $validated['lat'];
        $validated['longitude'] = $validated['longitude'] ?? $validated['lng'];

        if ($validated['latitude'] === null || $validated['longitude'] === null) {
            return response()->json(['status' => 'error', 'message' => 'Latitude and longitude are required'], 422);
        }

        // Update driver's current live location
        DB::table('drivers')->where('id', $id)->update([
            'current_lat' => $validated['latitude'],
            'current_lng' => $validated['longitude'],
            'last_location_at' => now(),
        ]);

        // Append to location history (driver_locations)
        if (DB::getSchemaBuilder()->hasTable('driver_locations')) {
            DB::table('driver_locations')->insert([
                'driver_id' => $id,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'speed' => $validated['speed'] ?? null,
                'heading' => $validated['heading'] ?? null,
                'recorded_at' => now(),
            ]);
        }
        // Riwayat lokasi ride (GrAntar): bila driver sedang membawa ride aktif (tersimpan di driver_locations dengan context)
        $rideId = (string) ($validated['ride_id'] ?? '');
        if ($rideId !== '' && DB::getSchemaBuilder()->hasTable('driver_locations')) {
            $activeRide = DB::table('orders')->where('id', $rideId)->where('driver_id', $id)
                ->whereIn('status', ['DRIVER_ACCEPTED', 'DRIVER_ARRIVING', 'DRIVER_ARRIVED', 'TRIP_STARTED'])->first();
            if ($activeRide) {
                DB::table('driver_locations')->insert([
                    'driver_id' => $id,
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'recorded_at' => now(),
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'driver_id' => $id,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'recorded_at' => now()->toDateTimeString(),
            ]
        ]);
    }

    /**
     * Read a tariff/commission setting value from app_settings.
     * Returns string|null.
     */
    private function getSetting(string $key): ?string
    {
        if (!DB::getSchemaBuilder()->hasTable('app_settings')) {
            return null;
        }
        return DB::table('app_settings')->where('setting_key', $key)->value('setting_value');
    }

    private function getSettingFloat(string $key, float $fallback = 0.0): float
    {
        $v = $this->getSetting($key);
        if ($v === null || $v === '') {
            return $fallback;
        }
        $num = (float) preg_replace('/[^0-9.]/', '', $v);
        return is_finite($num) ? $num : $fallback;
    }

    private function getSettingBool(string $key): bool
    {
        return strtolower((string) $this->getSetting($key)) === 'on';
    }

    /**
     * Bangun invoice breakdown dari snapshot kolom pada order.
     */
    private function buildInvoiceBreakdown($order): array
    {
        $type = $order->order_type ?? '';
        $invoice = [
            'order_type' => $type,
            'subtotal' => (float) ($order->subtotal ?? 0),
            'subtotal_label' => match ($type) {
                'FOOD', 'MART' => 'Total Makanan',
                'SHOP' => 'Total Belanja',
                default => 'Subtotal',
            },
        ];

        $distanceKm = (float) ($order->ride_distance_km ?? 0);
        $costPerKm = (float) ($order->cost_per_km_snapshot ?? 0);
        $tripCost = $distanceKm > 0 && $costPerKm > 0 ? round($distanceKm * $costPerKm, 0) : 0;
        $deliveryFee = (float) ($order->delivery_fee ?? 0);

        if (in_array($type, ['RIDE', 'DELIVERY'])) {
            $invoice['trip_distance_km'] = $distanceKm;
            $invoice['base_fare'] = round(max($deliveryFee - $tripCost, 0), 0);
            $invoice['trip_cost'] = $tripCost;
            $invoice['trip_cost_label'] = $distanceKm > 0 ? 'Biaya Perjalanan (' . $distanceKm . ' km × Rp ' . number_format($costPerKm, 0, ',', '.') . ')' : 'Tarif Dasar';
        } else {
            $invoice['delivery_fee'] = $deliveryFee;
        }

        $merchantCommission = (float) ($order->merchant_commission_snapshot ?? 0);
        $merchantPct = (float) ($order->merchant_commission_pct_snapshot ?? 0);
        $adminCommission = (float) ($order->admin_commission_snapshot ?? 0);
        $adminLabel = match ($type) {
            'RIDE', 'DELIVERY' => 'Potongan Komisi Admin Ride',
            'FOOD', 'MART' => 'Potongan Komisi Admin Food',
            'SHOP' => 'Potongan Komisi Admin Toko',
            default => 'Potongan Komisi Admin',
        };

        $invoice['merchant_commission'] = $merchantCommission;
        if ($merchantCommission > 0) {
            $invoice['merchant_commission_label'] = 'Komisi Food/Restro (' . rtrim(rtrim((string) $merchantPct, '0'), '.') . '%)';
        }
        $invoice['admin_commission'] = $adminCommission;
        $invoice['admin_commission_label'] = $adminLabel;

        $total = $deliveryFee + ($order->subtotal ?? 0) + $adminCommission - (float) ($order->discount_amount ?? 0);
        $invoice['total'] = $total;

        if ($merchantCommission > 0) {
            $invoice['merchant_net'] = max(($order->subtotal ?? 0) - $merchantCommission, 0);
            $invoice['merchant_net_label'] = 'Pendapatan Bersih Resto';
        }
        if (in_array($type, ['RIDE', 'DELIVERY']) && $deliveryFee > 0 && $adminCommission >= 0) {
            $invoice['driver_net'] = $deliveryFee - $adminCommission;
            $invoice['driver_net_label'] = 'Pendapatan Driver';
        }

        return $invoice;
    }

    /**
     * GET /api/wallets?user_id=X — saldo dompet pelanggan (GrSaldo) untuk app_customer.
     * Autocreate wallets table & row bila belum ada (production menggunakan BIGINT).
     */
    public function wallets(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $uid = $request->query('user_id');
        if (!$uid) {
            return response()->json(['status' => 'error', 'message' => 'user_id required'], 400);
        }

        // Production menggunakan BIGINT, contoh skema UUID. Selesaikan dengan aman.
        $user = null;
        if (is_numeric($uid)) {
            $user = DB::table('users')->where('id', (int) $uid)->first();
        }
        if (!$user) {
            $user = DB::table('users')->where('id', $uid)->first();
        }
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        if (!DB::getSchemaBuilder()->hasTable('wallets')) {
            DB::statement('CREATE TABLE IF NOT EXISTS wallets (
                id BIGSERIAL PRIMARY KEY,
                user_id BIGINT NOT NULL,
                balance NUMERIC(15,2) DEFAULT 0,
                points INTEGER DEFAULT 0,
                status VARCHAR(20) DEFAULT \'ACTIVE\',
                created_at TIMESTAMPTZ DEFAULT NOW(),
                updated_at TIMESTAMPTZ DEFAULT NOW()
            )');
        }

        $wallet = DB::table('wallets')->where('user_id', $user->id)->first();
        if (!$wallet) {
            DB::table('wallets')->insert([
                'user_id' => $user->id,
                'balance' => 0,
                'points' => 0,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $wallet = DB::table('wallets')->where('user_id', $user->id)->first();
        }

        return response()->json(['status' => 'success', 'data' => [$wallet]]);
    }

    /**
     * GET /api/settings — public, read-only tarif & komisi untuk aplikasi Flutter.
     */
    public function settings(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'ride_cost_per_km' => $this->getSetting('ride_cost_per_km') ?? '5000',
                'ride_base_fare' => $this->getSetting('ride_base_fare') ?? '10000',
                'food_commission_pct' => $this->getSetting('food_commission_pct') ?? '20',
                'admin_ride_commission_enabled' => $this->getSetting('admin_ride_commission_enabled') ?? 'OFF',
                'admin_ride_commission_amount' => $this->getSetting('admin_ride_commission_amount') ?? '0',
                'admin_food_commission_enabled' => $this->getSetting('admin_food_commission_enabled') ?? 'OFF',
                'admin_food_commission_amount' => $this->getSetting('admin_food_commission_amount') ?? '0',
                'admin_shop_commission_enabled' => $this->getSetting('admin_shop_commission_enabled') ?? 'OFF',
                'admin_shop_commission_amount' => $this->getSetting('admin_shop_commission_amount') ?? '0',
                'apk_urls' => [
                    'customer' => 'https://ridesip.my.id/apk/customer.apk',
                    'driver' => 'https://ridesip.my.id/apk/driver.apk',
                    'merchant' => 'https://ridesip.my.id/apk/merchant.apk',
                ],
            ]
        ]);
    }

    /**
     * Create a new order. Supports RIDE/DELIVERY (antar-jemput GPS) and
     * FOOD/MART/SHOP orders. All tariff & commission values are computed
     * from settings AT ORDER TIME and snapshot-ed on the order row, so later
     * settings changes never affect old transactions.
     *
     * POST /api/orders { order_type, user_id, pickup_address, pickup_lat, pickup_lng,
     *  dropoff_address, dropoff_lat, dropoff_lng, subtotal?, delivery_fee?, note? }
     */
    public function ordersStore(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $validated = $request->validate([
            'order_type' => 'required|string|in:FOOD,MART,SHOP,DELIVERY,RIDE',
            'user_id' => 'required|integer|exists:users,id',
            'merchant_id' => 'nullable|integer|exists:merchants,id',
            'pickup_address' => 'nullable|string',
            'pickup_lat' => 'nullable|numeric|between:-90,90',
            'pickup_lng' => 'nullable|numeric|between:-180,180',
            'dropoff_address' => 'nullable|string',
            'dropoff_lat' => 'nullable|numeric|between:-90,90',
            'dropoff_lng' => 'nullable|numeric|between:-180,180',
            'delivery_address' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'nullable|integer',
            'items.*.name' => 'nullable|string',
            'items.*.qty' => 'nullable|integer|min:1',
            'items.*.price' => 'nullable|numeric|min:0',
        ]);

        $orderType = $validated['order_type'];
        // Hitung subtotal dari items bila dikirim oleh aplikasi Flutter
        $items = $validated['items'] ?? null;
        $subtotal = $request->input('subtotal') ?? 0;
        if ($items && is_array($items) && count($items) > 0) {
            $subtotal = 0;
            foreach ($items as $it) {
                $subtotal += ((float) ($it['price'] ?? 0)) * ((int) ($it['qty'] ?? 1));
            }
            $subtotal = round($subtotal, 0);
        }
        $deliveryFee = $validated['delivery_fee'] ?? 0;

        // Snapshot tarif & komisi pada saat order dibuat
        $costPerKm = $this->getSettingFloat('ride_cost_per_km', 5000);
        $baseFare = $this->getSettingFloat('ride_base_fare', 10000);
        $foodCommissionPct = $this->getSettingFloat('food_commission_pct', 20);

        $distanceKm = null;
        $adminCommission = 0;
        $merchantCommission = 0;
        $merchantCommissionPct = 0;

        // Ride / antar-jemput: biaya = base fare + biaya per KM x jarak
        if (in_array($orderType, ['RIDE', 'DELIVERY']) && isset($validated['pickup_lat'], $validated['dropoff_lat'])) {
            $distanceKm = round($this->haversineKm(
                (float) $validated['pickup_lat'], (float) $validated['pickup_lng'],
                (float) $validated['dropoff_lat'], (float) $validated['dropoff_lng']
            ), 2);
            $deliveryFee = round($baseFare + ($distanceKm * $costPerKm), -2);
            if ($this->getSettingBool('admin_ride_commission_enabled')) {
                $adminCommission = $this->getSettingFloat('admin_ride_commission_amount', 0);
            }
        } elseif (in_array($orderType, ['FOOD', 'MART'])) {
            // Komisi Food/Restro % (dari subtotal makanan)
            $merchantCommission = round($subtotal * ($foodCommissionPct / 100), 0);
            $merchantCommissionPct = $foodCommissionPct;
            if ($this->getSettingBool('admin_food_commission_enabled')) {
                $adminCommission = $this->getSettingFloat('admin_food_commission_amount', 0);
            }
        } elseif ($orderType === 'SHOP') {
            if ($this->getSettingBool('admin_shop_commission_enabled')) {
                $adminCommission = $this->getSettingFloat('admin_shop_commission_amount', 0);
            }
        }

        $count = DB::table('orders')->count();
        $orderNumber = 'ORD-' . date('Y') . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        $id = DB::table('orders')->insertGetId([
            'order_number' => $orderNumber,
            'order_type' => $orderType,
            'user_id' => $validated['user_id'],
            'merchant_id' => $validated['merchant_id'] ?? null,
            'status' => 'PENDING',
            'pickup_address' => $validated['pickup_address'] ?? null,
            'pickup_lat' => $validated['pickup_lat'] ?? null,
            'pickup_lng' => $validated['pickup_lng'] ?? null,
            'dropoff_address' => $validated['dropoff_address'] ?? null,
            'dropoff_lat' => $validated['dropoff_lat'] ?? null,
            'dropoff_lng' => $validated['dropoff_lng'] ?? null,
            'delivery_address' => $validated['delivery_address'] ?? ($validated['dropoff_address'] ?? ''),
            'recipient_name' => $request->input('recipient_name', ''),
            'recipient_phone' => $request->input('recipient_phone', ''),
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'discount_amount' => 0,
            'total_amount' => $subtotal + $deliveryFee + $adminCommission,
            'ride_distance_km' => $distanceKm,
            'cost_per_km_snapshot' => in_array($orderType, ['RIDE', 'DELIVERY']) ? $costPerKm : null,
            'admin_commission_snapshot' => $adminCommission,
            'merchant_commission_snapshot' => $merchantCommission,
            'merchant_commission_pct_snapshot' => $merchantCommissionPct,
            'note' => $validated['note'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Simpan item pesanan bila ada (FOOD/MART/SHOP)
        if ($items && is_array($items) && count($items) > 0 && DB::getSchemaBuilder()->hasTable('order_items')) {
            foreach ($items as $it) {
                $qty = max(1, (int) ($it['qty'] ?? 1));
                $unitPrice = (float) ($it['price'] ?? 0);
                DB::table('order_items')->insert([
                    'order_id' => $id,
                    'menu_item_id' => $it['product_id'] ?? $it['menu_item_id'] ?? 0,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $qty * $unitPrice,
                    'notes' => $it['name'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Auto-assign driver untuk pesanan antar-jemput/kirim
        $assignedDriverId = null;
        if (in_array($orderType, ['RIDE', 'DELIVERY']) && DB::getSchemaBuilder()->hasTable('drivers')) {
            $assigned = DB::table('drivers')
                ->where('status', 'ONLINE')
                ->orderBy('id')
                ->first();
            if ($assigned) {
                $assignedDriverId = $assigned->id;
                DB::table('orders')->where('id', $id)->update([
                    'driver_id' => $assignedDriverId,
                    'status' => 'ASSIGNED',
                    'updated_at' => now(),
                ]);
            }
        }

        $order = DB::table('orders')->where('id', $id)->first();
        $order->invoice = $this->buildInvoiceBreakdown($order);
        return response()->json([
            'status' => 'success',
            'data' => $order
        ], 201);
    }

    /**
     * Register a new driver account. Creates user (role DRIVER), drivers profile,
     * driver wallet (balance 0) and optional vehicle row.
     * POST /api/register-driver { full_name, email, phone?, password, vehicle_type?, plate_number? }
     */
    public function registerDriver(Request $request)
    {

        $validated = $request->validate([
            'full_name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'vehicle_type' => 'nullable|string|in:MOTOR,MOBIL,BAJAJ,TRUK,PICKUP_TERBUKA,PICKUP_BOX',
            'plate_number' => 'nullable|string|max:20',
        ]);

        $email = strtolower(trim($validated['email']));
        if (DB::table('users')->where('email', $email)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email sudah terdaftar. Silakan login.',
            ], 409);
        }

        $phone = trim($validated['phone'] ?? '');
        if ($phone === '') {
            $phone = preg_replace('/[^0-9]/', '', $email);
            if (strlen($phone) > 20) $phone = substr($phone, 0, 20);
        }
        if (DB::table('users')->where('phone', $phone)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor HP sudah terdaftar.',
            ], 409);
        }

        $userId = DB::table('users')->insertGetId([
            'full_name' => $validated['full_name'],
            'name' => explode(' ', trim($validated['full_name']))[0],
            'email' => $email,
            'phone' => $phone,
            'password' => \Hash::make($validated['password']),
            'role' => 'MEMBER',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $driverId = DB::table('drivers')->insertGetId([
            'user_id' => $userId,
            'status' => 'OFFLINE',
            'is_verified' => true,
            'rating' => 5.00,
            'total_trips' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (DB::getSchemaBuilder()->hasTable('driver_wallets')) {
            DB::table('driver_wallets')->insert([
                'driver_id' => $driverId,
                'balance' => 0,
                'pending_balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $vehicleType = $validated['vehicle_type'] ?? 'MOTOR';
        if (!empty($validated['plate_number'])) {
            if (DB::getSchemaBuilder()->hasTable('driver_vehicles')) {
                DB::table('driver_vehicles')->insert([
                    'driver_id' => $driverId,
                    'vehicle_type' => $vehicleType,
                    'plate_number' => strtoupper(trim($validated['plate_number'])),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Akun driver berhasil dibuat. Silakan login.',
            'data' => [
                'id' => $userId,
                'full_name' => $validated['full_name'],
                'email' => $email,
                'phone' => $phone,
                'role' => 'MEMBER',
                'driver_id' => $driverId,
            ],
        ], 201);
    }

    /**
     * Driver profile + wallet balance.
     * GET /api/driver/me?user_id=
     */
    public function driverMe(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $userId = (int) $request->query('user_id', 0);
        if ($userId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'user_id diperlukan.'], 400);
        }
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan.'], 404);
        }
        $driver = DB::table('drivers')->where('user_id', $userId)->first();
        $wallet = ['balance' => 0, 'pending_balance' => 0];
        if ($driver && DB::getSchemaBuilder()->hasTable('driver_wallets')) {
            $w = DB::table('driver_wallets')->where('driver_id', $driver->id)->first();
            if ($w) {
                $wallet = ['balance' => (float) $w->balance, 'pending_balance' => (float) $w->pending_balance];
            }
        }
        $this->ensureKendaraanTables();
        $vehicle = null;
        $vehicles = [];
        if ($driver && DB::getSchemaBuilder()->hasTable('driver_vehicles')) {
            $vehicle = DB::table('driver_vehicles')
                ->where('driver_id', $driver->id)
                ->where('is_active', true)
                ->first();
            $vehicles = DB::table('driver_vehicles')
                ->where('driver_id', $driver->id)
                ->whereNull('deleted_at')
                ->orderBy('is_default', 'desc')
                ->orderBy('id', 'asc')
                ->get()
                ->map(fn($v) => $this->kendaraanPayload($v));
        }
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'rating' => $driver ? (float) $driver->rating : null,
                'total_trips' => $driver ? (int) $driver->total_trips : 0,
                'driver_status' => $driver ? $driver->status : null,
                'wallet' => $wallet,
                'vehicle' => $vehicle ? [
                    'vehicle_type' => $vehicle->vehicle_type,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'plate_number' => $vehicle->plate_number,
                ] : null,
                'vehicles' => $vehicles,
            ],
        ]);
    }

    /**
     * Riwayat pemasukan driver + total saldo.
     * GET /api/driver/earnings?user_id=
     * driver_net = delivery_fee - komisi admin (untuk RIDE/DELIVERY);
     * status COMPLETED = sudah cair, selainnya = pending.
     */
    public function driverEarnings(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $userId = (int) $request->query('user_id', 0);
        if ($userId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'user_id diperlukan.'], 400);
        }
        $driver = DB::table('drivers')->where('user_id', $userId)->first();
        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver tidak ditemukan.'], 404);
        }

        // Sumber kebenaran: wallet_transactions (is_earning = 1)
        $this->ensureWalletTables();
        $cw = $this->resolveWallet($userId);
        $earned = 0.0;
        $history = [];
        if ($cw) {
            $earnings = DB::table('wallet_transactions')
                ->where('wallet_id', $cw['wallet']->id)
                ->where('is_earning', true)
                ->where('direction', 'CREDIT')
                ->whereIn('type', ['RIDE_EARNING', 'DELIVERY_EARNING'])
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();
            foreach ($earnings as $e) {
                $earned += (float) $e->amount;
                $history[] = [
                    'order_number' => $e->description ?? ('#' . $e->reference_id),
                    'order_type' => 'RIDE',
                    'status' => 'COMPLETED',
                    'pickup_address' => '',
                    'dropoff_address' => '',
                    'driver_net' => round((float) $e->amount, 0),
                    'created_at' => $e->created_at,
                ];
            }
        }

        // Pemasukan pending: order driver yang belum COMPLETED
        $orders = DB::table('orders')
            ->where('driver_id', $driver->id)
            ->where('status', '!=', 'COMPLETED')
            ->get();
        $pending = 0.0;
        foreach ($orders as $o) {
            $net = max((float) (($o->delivery_fee ?? 0) - ($o->admin_commission_snapshot ?? 0)), 0);
            $pending += $net;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'driver_id' => $driver->id,
                'total_earned' => round($earned, 0),
                'total_pending' => round($pending, 0),
                'history' => $history,
            ],
        ]);
    }

    /**
     * Register a new merchant account (one universal users login).
     * POST /api/register-merchant { full_name, email, phone?, password, merchant_name, merchant_type?, merchant_city? }
     */
    public function registerMerchant(Request $request)
    {

        $validated = $request->validate([
            'full_name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'merchant_name' => 'required|string|min:2|max:255',
            'merchant_type' => 'nullable|string|max:20',
            'merchant_city' => 'nullable|string|max:100',
        ]);

        $email = strtolower(trim($validated['email']));
        if (DB::table('users')->where('email', $email)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email sudah terdaftar. Silakan login.',
            ], 409);
        }

        $userId = DB::table('users')->insertGetId([
            'full_name' => $validated['full_name'],
            'name' => explode(' ', trim($validated['full_name']))[0],
            'email' => $email,
            'phone' => $validated['phone'] ?? null,
            'password' => \Hash::make($validated['password']),
            'role' => 'MEMBER',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $type = strtoupper(trim((string) ($validated['merchant_type'] ?? 'FOOD')));
        if (!in_array($type, ['FOOD', 'MART', 'SHOP'], true)) {
            $type = 'FOOD';
        }
        $slug = \Str::slug($validated['merchant_name'], '-');
        if (DB::table('merchants')->where('slug', $slug)->exists()) {
            $slug .= '-' . substr((string) $userId, -4);
        }
        $merchantId = DB::table('merchants')->insertGetId([
            'owner_id' => $userId,
            'type' => $type,
            'name' => trim($validated['merchant_name']),
            'slug' => $slug,
            'description' => '',
            'logo_url' => '',
            'banner_url' => '',
            'phone' => trim((string) ($validated['phone'] ?? '')),
            'address_line' => '',
            'city' => $validated['merchant_city'] ?? '',
            'latitude' => 0,
            'longitude' => 0,
            'status' => 'ACTIVE',
            'is_open' => true,
            'rating' => 0,
            'total_orders' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Akun merchant berhasil dibuat. Silakan login.',
            'data' => [
                'id' => $userId,
                'full_name' => $validated['full_name'],
                'email' => $email,
                'phone' => $validated['phone'] ?? null,
                'role' => 'MEMBER',
                'merchant_id' => $merchantId,
            ],
        ], 201);
    }

    /**
     * Profil merchant pemilik toko + saldo sederhana.
     * GET /api/merchant/me?user_id=
     */
    public function merchantMe(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $userId = (int) $request->query('user_id', 0);
        if ($userId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'user_id diperlukan.'], 400);
        }
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan.'], 404);
        }
        $merchant = DB::table('merchants')->where('owner_id', $userId)->first();
        if (!$merchant) {
            return response()->json(['status' => 'error', 'message' => 'Toko tidak ditemukan untuk akun ini.'], 404);
        }
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'merchant' => [
                    'id' => $merchant->id,
                    'type' => $merchant->type,
                    'name' => $merchant->name,
                    'slug' => $merchant->slug,
                    'description' => $merchant->description,
                    'logo_url' => $merchant->logo_url,
                    'banner_url' => $merchant->banner_url,
                    'phone' => $merchant->phone,
                    'address_line' => $merchant->address_line,
                    'city' => $merchant->city,
                    'status' => $merchant->status,
                    'is_open' => (bool) $merchant->is_open,
                ],
            ],
        ]);
    }

    /**
     * Update info toko milik merchant.
     * POST /api/merchant/update { user_id, name?, description?, phone?, address_line?, city?, logo_url?, banner_url? }
     */
    public function merchantUpdate(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|min:1',
            'name' => 'nullable|string|min:2|max:255',
            'description' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:20',
            'address_line' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'logo_url' => 'nullable|url|max:1000',
            'banner_url' => 'nullable|url|max:1000',
        ]);

        $merchant = DB::table('merchants')->where('owner_id', $validated['user_id'])->first();
        if (!$merchant) {
            return response()->json(['status' => 'error', 'message' => 'Toko tidak ditemukan untuk akun ini.'], 404);
        }
        $updates = ['updated_at' => now()];
        foreach (['name', 'description', 'phone', 'address_line', 'city', 'logo_url', 'banner_url'] as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] !== null) {
                $updates[$field] = trim((string) $validated[$field]);
            }
        }
        DB::table('merchants')->where('id', $merchant->id)->update($updates);
        return response()->json([
            'status' => 'success',
            'message' => 'Info toko berhasil diperbarui.',
        ]);
    }

    /**
     * Tambah produk baru ke toko merchant.
     * POST /api/products { user_id, merchant_id?, name, description?, price, image_url?, is_available? }
     */
    public function storeProduct(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|min:1',
            'merchant_id' => 'nullable|integer|min:1',
            'name' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|url|max:1000',
            'is_available' => 'nullable|boolean',
        ]);

        $merchant = DB::table('merchants')->where('owner_id', $validated['user_id'])
            ->when(!empty($validated['merchant_id']), function ($q) use ($validated) {
                return $q->where('id', $validated['merchant_id']);
            })
            ->first();
        if (!$merchant) {
            return response()->json(['status' => 'error', 'message' => 'Toko tidak ditemukan untuk akun ini.'], 404);
        }
        $slug = \Str::slug($validated['name'], '-') . '-' . substr(uniqid(), -4);
        $id = DB::table('menu_items')->insertGetId([
            'merchant_id' => $merchant->id,
            'name' => trim($validated['name']),
            'slug' => $slug,
            'description' => trim((string) ($validated['description'] ?? '')),
            'price' => (float) $validated['price'],
            'image_url' => $validated['image_url'] ?? null,
            'is_available' => (bool) ($validated['is_available'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil ditambahkan.',
            'data' => ['id' => $id],
        ], 201);
    }

    /**
     * Edit produk milik toko merchant.
     * PUT /api/products/{id} { user_id, name?, description?, price?, image_url?, is_available? }
     */
    public function updateProduct(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|min:1',
            'name' => 'nullable|string|min:2|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|url|max:1000',
            'is_available' => 'nullable|boolean',
        ]);

        $product = DB::table('menu_items')->where('id', $id)->first();
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Produk tidak ditemukan.'], 404);
        }
        $merchant = DB::table('merchants')->where('id', $product->merchant_id)
            ->where('owner_id', $validated['user_id'])
            ->first();
        if (!$merchant) {
            return response()->json(['status' => 'error', 'message' => 'Produk bukan milik toko Anda.'], 403);
        }
        $updates = ['updated_at' => now()];
        foreach (['name', 'description', 'price', 'image_url'] as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] !== null) {
                $updates[$field] = is_string($validated[$field]) ? trim($validated[$field]) : $validated[$field];
            }
        }
        if (array_key_exists('is_available', $validated)) {
            $updates['is_available'] = (bool) $validated['is_available'];
        }
        DB::table('menu_items')->where('id', $id)->update($updates);
        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil diperbarui.',
        ]);
    }

    /**
     * Nonaktifkan/hapus produk milik toko merchant.
     * DELETE /api/products/{id}?user_id=
     */
    public function toggleProduct(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $userId = (int) $request->query('user_id', 0);
        if ($userId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'user_id diperlukan.'], 400);
        }
        $product = DB::table('menu_items')->where('id', $id)->first();
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Produk tidak ditemukan.'], 404);
        }
        $merchant = DB::table('merchants')->where('id', $product->merchant_id)
            ->where('owner_id', $userId)
            ->first();
        if (!$merchant) {
            return response()->json(['status' => 'error', 'message' => 'Produk bukan milik toko Anda.'], 403);
        }
        DB::table('menu_items')->where('id', $id)->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil dihapus.',
        ]);
    }

    /**
     * Saldo & riwayat transaksi merchant pemilik toko.
     * GET /api/merchant/earnings?user_id=
     * merchant_net = subtotal - komisi toko (COMPLETED = sudah cair).
     */
    public function merchantEarnings(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $userId = (int) $request->query('user_id', 0);
        if ($userId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'user_id diperlukan.'], 400);
        }
        $merchant = DB::table('merchants')->where('owner_id', $userId)->first();
        if (!$merchant) {
            return response()->json(['status' => 'error', 'message' => 'Toko tidak ditemukan untuk akun ini.'], 404);
        }
        // Sumber kebenaran: wallet_transactions (is_earning = 1, ORDER_EARNING)
        $this->ensureWalletTables();
        $cw = $this->resolveWallet($userId);
        $earned = 0.0;
        $history = [];
        if ($cw) {
            $earnings = DB::table('wallet_transactions')
                ->where('wallet_id', $cw['wallet']->id)
                ->where('is_earning', true)
                ->where('direction', 'CREDIT')
                ->whereIn('type', ['ORDER_EARNING'])
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();
            foreach ($earnings as $e) {
                $earned += (float) $e->amount;
                $history[] = [
                    'order_number' => $e->description ?? ('#' . $e->reference_id),
                    'order_type' => 'FOOD',
                    'status' => 'COMPLETED',
                    'total_amount' => round((float) $e->amount, 0),
                    'merchant_net' => round((float) $e->amount, 0),
                    'created_at' => $e->created_at,
                ];
            }
        }

        // Pendapatan pending: order merchant yang belum COMPLETED
        $orders = DB::table('orders')
            ->where('merchant_id', $merchant->id)
            ->where('status', '!=', 'COMPLETED')
            ->get();
        $pending = 0.0;
        foreach ($orders as $o) {
            $net = max((float) ($o->subtotal ?? 0) - (float) ($o->merchant_commission_snapshot ?? 0), 0);
            $pending += $net;
            $history[] = [
                'order_number' => $o->order_number,
                'order_type' => $o->order_type,
                'status' => $o->status,
                'total_amount' => round((float) $o->total_amount, 0),
                'merchant_net' => round($net, 0),
                'created_at' => $o->created_at,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'merchant_id' => $merchant->id,
                'merchant_name' => $merchant->name,
                'total_earned' => round($earned, 0),
                'total_pending' => round($pending, 0),
                'history' => $history,
            ],
        ]);
    }

    /**
     * Register a new customer account.
     * POST /api/register { full_name, email, phone?, password }
     */
    public function register(Request $request)
    {

        $validated = $request->validate([
            'full_name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        if (DB::table('users')->where('email', strtolower(trim($validated['email'])))->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email sudah terdaftar. Silakan login.',
            ], 409);
        }

        $userId = DB::table('users')->insertGetId([
            'full_name' => $validated['full_name'],
            'name' => explode(' ', trim($validated['full_name']))[0],
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
            'password' => \Hash::make($validated['password']),
            'role' => 'MEMBER',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = DB::table('users')->where('id', $userId)->first();
        return response()->json([
            'status' => 'success',
            'message' => 'Akun berhasil dibuat. Silakan login.',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
        ], 201);
    }

    /**
     * Login customer account.
     * POST /api/login { email, password }
     */
    public function login(Request $request)
    {

        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = DB::table('users')
            ->where('email', strtolower(trim($validated['email'])))
            ->first();

        if (!$user || !\Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah.',
            ], 401);
        }

        if ($user->status !== 'ACTIVE') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun tidak aktif.',
            ], 403);
        }

        $role = strtoupper((string) ($user->role ?? ''));
        if ($role === 'ADMIN' || $role === 'MANAGER') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun ini tidak diizinkan mengakses API (role: '.$role.').',
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Haversine distance in kilometres between two GPS coordinates.
     */
    private function haversineKm(float $lat1, ?float $lng1, float $lat2, ?float $lng2): float
    {
        if ($lng1 === null || $lng2 === null) {
            return 9999;
        }
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    // =========================================================================
    // IKLAN GRATIS — public listing & CRUD via app_customer (identitas user
    // dikirim sebagai user_id, konsisten dengan pola endpoints lain).
    // =========================================================================

    /**
     * GET /api/iklan-gratis/categories — daftar kategori iklan gratis.
     * Autocreate tabel bila belum ada (production BIGINT).
     */
    public function iklanGratisCategories()
    {
        $this->ensureIklanGratisTables();
        $rows = DB::table('iklan_gratis_categories')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    /**
     * GET /api/iklan-gratis — daftar iklan aktif + belum expired.
     * Query: category_id, city, q (pencarian judul/deskripsi), page, per_page,
     * user_id (iklan milik sendiri tetap tampil meski expired).
     */
    public function iklanGratisIndex(Request $request)
    {
        $this->ensureIklanGratisTables();
        $query = DB::table('iklan_gratis')->where('status', 'ACTIVE');
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }
        if ($request->filled('city')) {
            $query->where('city', 'ILIKE', '%' . $request->query('city') . '%');
        }
        if ($request->filled('q')) {
            $q = '%' . $request->query('q') . '%';
            $query->where(function ($qq) use ($q) {
                $qq->where('title', 'ILIKE', $q)->orWhere('description', 'ILIKE', $q);
            });
        }
        if ($request->filled('user_id')) {
            $query->where(function ($qq) use ($request) {
                $qq->where('expired_at', '>=', now())
                    ->orWhere('user_id', $request->query('user_id'));
            });
        } else {
            $query->where('expired_at', '>=', now());
        }
        $query->orderBy('created_at', 'desc');
        $perPage = min(max((int) $request->query('per_page', 10), 5), 50);
        $page = max((int) $request->query('page', 1), 1);
        $total = $query->count();
        $rows = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();
        $rows = $rows->map(function ($r) {
            $r->photos = json_decode($r->photos ?? '[]', true) ?? [];
            return $r;
        });
        return response()->json([
            'status' => 'success',
            'data' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
                'items' => $rows,
            ],
        ]);
    }

    /**
     * GET /api/iklan-gratis/{id} — detail iklan.
     */
    public function iklanGratisShow($id)
    {
        $this->ensureIklanGratisTables();
        $ad = DB::table('iklan_gratis')->where('id', $id)->first();
        if (!$ad) {
            return response()->json(['status' => 'error', 'message' => 'Iklan tidak ditemukan'], 404);
        }
        $ad->photos = json_decode($ad->photos ?? '[]', true) ?? [];
        return response()->json(['status' => 'success', 'data' => $ad]);
    }

    /**
     * POST /api/iklan-gratis — tambah iklan baru (wajib user_id; maks 1 tahun).
     */
    public function iklanGratisStore(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $this->ensureIklanGratisTables();
        $userId = $request->input('user_id');
        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'Silakan login terlebih dahulu'], 401);
        }
        $user = is_numeric($userId)
            ? DB::table('users')->where('id', (int) $userId)->first()
            : null;
        if (!$user) {
            $user = DB::table('users')->where('id', $userId)->first();
        }
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
        }
        $title = trim((string) ($request->input('title') ?? ''));
        if ($title === '') {
            return response()->json(['status' => 'error', 'message' => 'Judul iklan wajib diisi'], 422);
        }
        $photos = $request->input('photos');
        if (is_string($photos)) {
            $photos = json_decode($photos, true) ?? [];
        }
        $photos = array_values(array_filter(array_slice((array) $photos, 0, 10), fn($p) => is_string($p) && trim($p) !== ''));
        $months = max(1, min(12, (int) ($request->input('expired_months', 12))));
        $expiredAt = now()->addMonths($months);
        $id = DB::table('iklan_gratis')->insertGetId([
            'user_id' => $user->id,
            'category_id' => $request->filled('category_id') ? (int) $request->input('category_id') : null,
            'title' => $title,
            'description' => trim((string) ($request->input('description') ?? '')),
            'price' => (float) ($request->input('price', 0) ?? 0),
            'photos' => json_encode($photos),
            'contact_name' => trim((string) ($request->input('contact_name') ?? '')),
            'contact_phone' => trim((string) ($request->input('contact_phone') ?? '')),
            'city' => trim((string) ($request->input('city') ?? '')),
            'status' => 'ACTIVE',
            'expired_at' => $expiredAt,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $ad = DB::table('iklan_gratis')->where('id', $id)->first();
        $ad->photos = $photos;
        return response()->json(['status' => 'success', 'message' => 'Iklan berhasil ditambahkan', 'data' => $ad], 201);
    }

    /**
     * PUT /api/iklan-gratis/{id} — edit iklan (hanya pemilik).
     */
    public function iklanGratisUpdate(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $this->ensureIklanGratisTables();
        $ad = DB::table('iklan_gratis')->where('id', $id)->first();
        if (!$ad) {
            return response()->json(['status' => 'error', 'message' => 'Iklan tidak ditemukan'], 404);
        }
        if ((string) $ad->user_id !== (string) $request->input('user_id')) {
            return response()->json(['status' => 'error', 'message' => 'Hanya pemilik iklan yang dapat mengedit'], 403);
        }
        $title = trim((string) ($request->input('title', $ad->title)));
        if ($title === '') {
            return response()->json(['status' => 'error', 'message' => 'Judul iklan wajib diisi'], 422);
        }
        $photos = $request->input('photos');
        if ($photos !== null) {
            if (is_string($photos)) {
                $photos = json_decode($photos, true) ?? [];
            }
            $photos = array_values(array_filter(array_slice((array) $photos, 0, 10), fn($p) => is_string($p) && trim($p) !== ''));
        } else {
            $photos = json_decode($ad->photos ?? '[]', true) ?? [];
        }
        $months = $request->input('expired_months');
        $expiredAt = $months !== null ? now()->addMonths(max(1, min(12, (int) $months))) : $ad->expired_at;
        DB::table('iklan_gratis')->where('id', $id)->update([
            'category_id' => $request->filled('category_id') ? (int) $request->input('category_id') : $ad->category_id,
            'title' => $title,
            'description' => trim((string) ($request->input('description', $ad->description))),
            'price' => (float) ($request->input('price', $ad->price) ?? 0),
            'photos' => json_encode($photos),
            'contact_name' => trim((string) ($request->input('contact_name', $ad->contact_name))),
            'contact_phone' => trim((string) ($request->input('contact_phone', $ad->contact_phone))),
            'city' => trim((string) ($request->input('city', $ad->city))),
            'expired_at' => $expiredAt,
            'updated_at' => now(),
        ]);
        $ad = DB::table('iklan_gratis')->where('id', $id)->first();
        $ad->photos = $photos;
        return response()->json(['status' => 'success', 'message' => 'Iklan berhasil diperbarui', 'data' => $ad]);
    }

    /**
     * DELETE /api/iklan-gratis/{id} — hapus iklan (hanya pemilik).
     */
    public function iklanGratisDelete(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $this->ensureIklanGratisTables();
        $ad = DB::table('iklan_gratis')->where('id', $id)->first();
        if (!$ad) {
            return response()->json(['status' => 'error', 'message' => 'Iklan tidak ditemukan'], 404);
        }
        if ((string) $ad->user_id !== (string) $request->input('user_id')) {
            return response()->json(['status' => 'error', 'message' => 'Hanya pemilik iklan yang dapat menghapus'], 403);
        }
        DB::table('iklan_gratis')->where('id', $id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Iklan berhasil dihapus']);
    }

    /**
     * Pastikan tabel iklan_gratis & iklan_gratis_categories ada (autocreate production).
     */
    private function ensureIklanGratisTables()
    {
        if (!DB::getSchemaBuilder()->hasTable('iklan_gratis_categories')) {
            DB::statement('CREATE TABLE IF NOT EXISTS iklan_gratis_categories (
                id BIGSERIAL PRIMARY KEY, name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) UNIQUE NOT NULL, is_active BOOLEAN DEFAULT TRUE,
                sort_order INTEGER DEFAULT 0, created_at TIMESTAMPTZ DEFAULT NOW(),
                updated_at TIMESTAMPTZ DEFAULT NOW()
            )');
            DB::table('iklan_gratis_categories')->insert([
                ['name' => 'Properti', 'slug' => 'properti', 'sort_order' => 1],
                ['name' => 'Kendaraan', 'slug' => 'kendaraan', 'sort_order' => 2],
                ['name' => 'Elektronik', 'slug' => 'elektronik', 'sort_order' => 3],
                ['name' => 'Fashion', 'slug' => 'fashion', 'sort_order' => 4],
                ['name' => 'Kesehatan & Kecantikan', 'slug' => 'kesehatan-kecantikan', 'sort_order' => 5],
                ['name' => 'Hobi & Olahraga', 'slug' => 'hobi-olahraga', 'sort_order' => 6],
                ['name' => 'Lowongan Kerja', 'slug' => 'lowongan-kerja', 'sort_order' => 7],
                ['name' => 'Jasa', 'slug' => 'jasa', 'sort_order' => 8],
                ['name' => 'Makanan & Minuman', 'slug' => 'makanan-minuman', 'sort_order' => 9],
                ['name' => 'Peralatan Rumah Tangga', 'slug' => 'peralatan-rumah-tangga', 'sort_order' => 10],
                ['name' => 'Hewan Peliharaan', 'slug' => 'hewan-peliharaan', 'sort_order' => 11],
                ['name' => 'Pertanian & Perkebunan', 'slug' => 'pertanian-perkebunan', 'sort_order' => 12],
                ['name' => 'Bisnis & Industri', 'slug' => 'bisnis-industri', 'sort_order' => 13],
                ['name' => 'Komunitas & Event', 'slug' => 'komunitas-event', 'sort_order' => 14],
                ['name' => 'Lain-lain', 'slug' => 'lain-lain', 'sort_order' => 15],
            ]);
        }
        if (!DB::getSchemaBuilder()->hasTable('iklan_gratis')) {
            DB::statement('CREATE TABLE IF NOT EXISTS iklan_gratis (
                id BIGSERIAL PRIMARY KEY, user_id BIGINT NOT NULL,
                category_id BIGINT, title VARCHAR(255) NOT NULL,
                description TEXT, price NUMERIC(15,2) DEFAULT 0,
                photos TEXT, contact_name VARCHAR(255), contact_phone VARCHAR(20),
                city VARCHAR(100), status VARCHAR(20) DEFAULT \'ACTIVE\',
                expired_at TIMESTAMPTZ, posted_at TIMESTAMPTZ DEFAULT NOW(),
                created_at TIMESTAMPTZ DEFAULT NOW(), updated_at TIMESTAMPTZ DEFAULT NOW()
            )');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_iklan_gratis_user ON iklan_gratis(user_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_iklan_gratis_category ON iklan_gratis(category_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_iklan_gratis_status ON iklan_gratis(status)');
                        DB::statement('CREATE INDEX IF NOT EXISTS idx_iklan_gratis_expired ON iklan_gratis(expired_at)');
        }
    }

    // =========================================================================
    // MODUL WALLET (GrSaldo) — top up, withdraw, riwayat, rekening, PIN
    // =========================================================================

    /** Pastikan struktur wallet (table + kolom) tersedia di production BIGINT. */
    private function ensureWalletTables()
    {
        if (!DB::getSchemaBuilder()->hasTable('user_payment_methods')) {
            DB::statement('CREATE TABLE IF NOT EXISTS user_payment_methods (
                id BIGSERIAL PRIMARY KEY, user_id BIGINT NOT NULL,
                provider VARCHAR(50) DEFAULT \'BANK\', bank_name VARCHAR(100),
                account_number VARCHAR(100), account_holder VARCHAR(255),
                is_default BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMPTZ DEFAULT NOW(), updated_at TIMESTAMPTZ DEFAULT NOW()
            )');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_upm_user ON user_payment_methods(user_id)');
        }
        if (!DB::getSchemaBuilder()->hasTable('wallet_transactions')) {
            DB::statement('CREATE TABLE IF NOT EXISTS wallet_transactions (
                id BIGSERIAL PRIMARY KEY, wallet_id BIGINT NOT NULL,
                type VARCHAR(30) NOT NULL, amount NUMERIC(15,2) DEFAULT 0,
                balance_before NUMERIC(15,2) DEFAULT 0, balance_after NUMERIC(15,2) DEFAULT 0,
                status VARCHAR(30) DEFAULT \'PENDING\', method VARCHAR(50),
                reference_no VARCHAR(100), reference_id BIGINT,
                idempotency_key VARCHAR(100), description TEXT, failure_reason TEXT,
                expired_at TIMESTAMPTZ, created_at TIMESTAMPTZ DEFAULT NOW(), updated_at TIMESTAMPTZ DEFAULT NOW()
            )');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_wtx_wallet ON wallet_transactions(wallet_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_wtx_refno ON wallet_transactions(reference_no)');
        }
        if (!DB::getSchemaBuilder()->hasColumn('wallet_transactions', 'direction')) {
            DB::statement('ALTER TABLE wallet_transactions ADD COLUMN IF NOT EXISTS direction VARCHAR(20) NOT NULL DEFAULT \'CREDIT\'');
        }
        if (!DB::getSchemaBuilder()->hasColumn('wallet_transactions', 'is_earning')) {
            DB::statement('ALTER TABLE wallet_transactions ADD COLUMN IF NOT EXISTS is_earning BOOLEAN NOT NULL DEFAULT FALSE');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_wtx_earning ON wallet_transactions(is_earning)');
        }
        if (!DB::getSchemaBuilder()->hasColumn('wallet_transactions', 'user_id')) {
            DB::statement('ALTER TABLE wallet_transactions ADD COLUMN IF NOT EXISTS user_id BIGINT');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_wtx_user ON wallet_transactions(user_id)');
        }
        if (!DB::getSchemaBuilder()->hasColumn('wallet_transactions', 'reference_type')) {
            DB::statement('ALTER TABLE wallet_transactions ADD COLUMN IF NOT EXISTS reference_type VARCHAR(30)');
        }
        if (!DB::getSchemaBuilder()->hasColumn('users', 'wallet_pin')) {
            DB::statement('ALTER TABLE users ADD COLUMN IF NOT EXISTS wallet_pin VARCHAR(255)');
            DB::statement('ALTER TABLE users ADD COLUMN IF NOT EXISTS wallet_pin_attempts INTEGER DEFAULT 0');
            DB::statement('ALTER TABLE users ADD COLUMN IF NOT EXISTS wallet_locked_until TIMESTAMPTZ');
        }
    }

    /** Ambil user + wallet (autocreate baris bila belum ada), null bila user tidak ada. */
    private function resolveWallet($userId)
    {
        $this->ensureWalletTables();
        $user = null;
        if (is_numeric($userId)) {
            $user = DB::table('users')->where('id', (int) $userId)->first();
        }
        if (!$user) {
            $user = DB::table('users')->where('id', $userId)->first();
        }
        if (!$user) {
            return null;
        }
        $wallet = DB::table('wallets')->where('user_id', $user->id)->first();
        if (!$wallet) {
            DB::table('wallets')->insert([
                'user_id' => $user->id, 'balance' => 0, 'points' => 0,
                'status' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $wallet = DB::table('wallets')->where('user_id', $user->id)->first();
        }
        return ['user' => $user, 'wallet' => $wallet];
    }

    /** GET /api/wallet/transactions?user_id=X&type=&from=&to=&page= */
    public function walletTransactions(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $resolved = $this->resolveWallet($request->query('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $this->ensureWalletTables();
        $type = $request->query('type');
        $from = $request->query('from');
        $to = $request->query('to');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        $q = DB::table('wallet_transactions')->where('wallet_id', $resolved['wallet']->id);
        if ($type && strtoupper($type) !== 'ALL' && strtoupper($type) !== 'SEMUA') {
            $q->where('type', strtoupper($type));
        }
        $direction = strtoupper((string) $request->query('direction', ''));
        if (in_array($direction, ['CREDIT', 'DEBIT'])) {
            $q->where('direction', $direction);
        }
        $isEarning = $request->query('is_earning');
        if ($isEarning !== null && $isEarning !== '') {
            $q->where('is_earning', strtolower($isEarning) === 'true' || $isEarning === '1' ? true : false);
        }
        if ($from) {
            $q->where('created_at', '>=', $from);
        }
        if ($to) {
            $q->where('created_at', '<=', $to . ' 23:59:59');
        }
        $total = $q->count();
        $rows = $q->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return response()->json(['status' => 'success', 'data' => [
            'items' => $rows,
            'current_page' => $page,
            'total' => $total,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ]]);
    }

    /**
     * GET /api/wallet/summary?user_id=X — ringkasan GrSaldo satu sumber kebenaran.
     * Berisi saldo saat ini + total per kategori (earning, topup, withdraw, payment).
     */
    public function walletSummary(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $resolved = $this->resolveWallet($request->query('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $this->ensureWalletTables();
        $walletId = $resolved['wallet']->id;
        $wallet = DB::table('wallets')->where('id', $walletId)->first();
        $agg = DB::table('wallet_transactions')->where('wallet_id', $walletId)->selectRaw("
            COALESCE(SUM(CASE WHEN direction='CREDIT' THEN amount ELSE 0 END),0) AS total_in,
            COALESCE(SUM(CASE WHEN direction='DEBIT' THEN amount ELSE 0 END),0) AS total_out,
            COALESCE(SUM(CASE WHEN direction='CREDIT' AND is_earning THEN amount ELSE 0 END),0) AS total_earning,
            COALESCE(SUM(CASE WHEN type='TOPUP' THEN amount ELSE 0 END),0) AS total_topup,
            COALESCE(SUM(CASE WHEN type='WITHDRAW' THEN amount ELSE 0 END),0) AS total_withdraw,
            COUNT(*) AS total_transactions")->first();
        return response()->json(['status' => 'success', 'data' => [
            'balance' => (float) ($wallet->balance ?? 0),
            'currency' => 'IDR',
            'status' => $wallet->status ?? 'ACTIVE',
            'total_in' => round((float) $agg->total_in, 0),
            'total_out' => round((float) $agg->total_out, 0),
            'total_earning' => round((float) $agg->total_earning, 0),
            'total_topup' => round((float) $agg->total_topup, 0),
            'total_withdraw' => round((float) $agg->total_withdraw, 0),
            'total_transactions' => (int) $agg->total_transactions,
        ]]);
    }

    /**
     * Catat mutasi saldo wallet yang tersentral (single source of truth).
     * Direction: CREDIT (masuk) / DEBIT (keluar). is_earning true untuk penghasilan
     * (RIDE_EARNING, DELIVERY_EARNING, ORDER_EARNING, BONUS, REFERRAL).
     * Idempotensi via kunci [wallet_id + type + reference_id] bila reference_id diberikan.
     */
    private function postWalletTransaction(array $params): ?object
    {
        $this->ensureWalletTables();
        $walletId = $params['wallet_id'];
        $type = $params['type'];
        $amount = (float) $params['amount'];
        if ($amount <= 0) {
            return null;
        }
        $refId = $params['reference_id'] ?? null;
        if ($refId) {
            $dup = DB::table('wallet_transactions')
                ->where('wallet_id', $walletId)
                ->where('type', $type)
                ->where('reference_id', $refId)
                ->first();
            if ($dup) {
                return $dup;
            }
        }
        // Kunci baris wallet untuk mencegah race condition saldo
        $wallet = DB::table('wallets')->where('id', $walletId)->lockForUpdate()->first();
        if (!$wallet) {
            return null;
        }
        $before = (float) $wallet->balance;
        $credit = strtoupper((string) ($params['direction'] ?? 'CREDIT')) === 'CREDIT';
        $after = round($credit ? $before + $amount : $before - $amount, 2);
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $walletId,
            'type' => $type,
            'direction' => $credit ? 'CREDIT' : 'DEBIT',
            'is_earning' => (bool) ($params['is_earning'] ?? false),
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'status' => $params['status'] ?? 'SUCCESS',
            'reference_no' => $params['reference_no'] ?? null,
            'reference_id' => $refId,
            'reference_type' => $params['reference_type'] ?? null,
            'description' => $params['description'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('wallets')->where('id', $walletId)
            ->update(['balance' => DB::raw($credit ? 'balance + ' . $amount : 'balance - ' . $amount), 'updated_at' => now()]);
        return DB::table('wallet_transactions')->where('wallet_id', $walletId)
            ->where('type', $type)->where('reference_id', $refId)->first();
    }

    /**
     * Settlement wallet saat order berubah menjadi COMPLETED.
     * - Customer di-debit total order (RIDE_PAYMENT / ORDER_PAYMENT) bila saldo cukup
     * - Driver di-credit driver_net (is_earning=1)
     * - Merchant di-credit merchant_net (is_earning=1)
     * - Komisi admin dicatat sebagai ADMIN_FEE
     */
    public function settleOrderWallet($orderId)
    {
        $order = DB::table('orders')->where('id', $orderId)->first();
        if (!$order) {
            return;
        }
        $total = (float) ($order->total_amount ?? 0);
        if ($total <= 0) {
            $total = max((float) ($order->subtotal ?? 0) + (float) ($order->delivery_fee ?? 0) + (float) ($order->admin_commission_snapshot ?? 0) - (float) ($order->discount_amount ?? 0), 0);
        }
        // --- Customer: debit pembayaran order (bila saldo cukup) ---
        $customerId = $order->user_id ?? $order->customer_id ?? null;
        if ($customerId) {
            $cw = $this->resolveWallet((int) $customerId);
            if ($cw && (float) $cw['wallet']->balance >= $total) {
                $this->postWalletTransaction([
                    'wallet_id' => $cw['wallet']->id,
                    'type' => in_array($order->order_type ?? '', ['RIDE', 'DELIVERY']) ? 'RIDE_PAYMENT' : 'ORDER_PAYMENT',
                    'direction' => 'DEBIT',
                    'is_earning' => false,
                    'amount' => $total,
                    'reference_id' => (int) $order->id,
                    'reference_type' => 'ORDER',
                    'description' => 'Pembayaran order ' . ($order->order_number ?? ''),
                ]);
                DB::table('orders')->where('id', $order->id)->update(['payment_status' => 'PAID', 'updated_at' => now()]);
            }
        }
        // --- Driver: credit penghasilan (is_earning=1) ---
        if ($order->driver_id && in_array($order->order_type ?? '', ['RIDE', 'DELIVERY'])) {
            $driverUser = DB::table('drivers')->where('id', $order->driver_id)->first();
            if ($driverUser && $driverUser->user_id) {
                $dw = $this->resolveWallet($driverUser->user_id);
                if ($dw) {
                    $driverNet = max((float) ($order->delivery_fee ?? 0) - (float) ($order->admin_commission_snapshot ?? 0), 0);
                    if ($driverNet > 0) {
                        $this->postWalletTransaction([
                            'wallet_id' => $dw['wallet']->id,
                            'type' => $order->order_type === 'RIDE' ? 'RIDE_EARNING' : 'DELIVERY_EARNING',
                            'direction' => 'CREDIT',
                            'is_earning' => true,
                            'amount' => $driverNet,
                            'reference_id' => (int) $order->id,
                            'reference_type' => 'ORDER',
                            'description' => 'Penghasilan order ' . ($order->order_number ?? ''),
                        ]);
                    }
                }
            }
        }
        // --- Merchant: credit penghasilan (is_earning=1) ---
        if ($order->merchant_id && in_array($order->order_type ?? '', ['FOOD', 'MART', 'SHOP'])) {
            $merchant = DB::table('merchants')->where('id', $order->merchant_id)->first();
            if ($merchant && $merchant->owner_id) {
                $mw = $this->resolveWallet($merchant->owner_id);
                if ($mw) {
                    $merchantNet = max((float) ($order->subtotal ?? 0) - (float) ($order->merchant_commission_snapshot ?? 0), 0);
                    if ($merchantNet > 0) {
                        $this->postWalletTransaction([
                            'wallet_id' => $mw['wallet']->id,
                            'type' => 'ORDER_EARNING',
                            'direction' => 'CREDIT',
                            'is_earning' => true,
                            'amount' => $merchantNet,
                            'reference_id' => (int) $order->id,
                            'reference_type' => 'ORDER',
                            'description' => 'Penghasilan order ' . ($order->order_number ?? ''),
                        ]);
                    }
                }
            }
        }
    }

    /** GET /api/wallet/transactions/{id}?user_id=X */
    public function walletTransactionDetail(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $resolved = $this->resolveWallet($request->query('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $row = DB::table('wallet_transactions')->where('id', $id)
            ->where('wallet_id', $resolved['wallet']->id)->first();
        if (!$row) {
            return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $row]);
    }

    /**
     * POST /api/wallet/topup {user_id, amount, method, rekening_no?, account_holder?, idempotency_key}
     * method: VA_BANK, EWALLET, QRIS, CARD. Simulasi pembayaran manual → langsung SUCCESS.
     */
    public function walletTopup(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $resolved = $this->resolveWallet($request->input('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $amount = (float) ($request->input('amount') ?? 0);
        $method = strtoupper($request->input('method', 'VA_BANK'));
        if (!in_array($method, ['VA_BANK', 'EWALLET', 'QRIS', 'CARD'])) {
            $method = 'VA_BANK';
        }
        if ($amount < 10000) {
            return response()->json(['status' => 'error', 'message' => 'Minimum top up Rp 10.000'], 400);
        }
        if ($amount > 10000000) {
            return response()->json(['status' => 'error', 'message' => 'Maksimum top up Rp 10.000.000 per transaksi'], 400);
        }
        $this->ensureWalletTables();
        // Idempotency: cegah double submit
        $idem = $request->input('idempotency_key');
        if ($idem) {
            $dup = DB::table('wallet_transactions')->where('wallet_id', $resolved['wallet']->id)
                ->where('idempotency_key', $idem)->first();
            if ($dup) {
                return response()->json(['status' => 'success', 'data' => $dup, 'message' => 'Permintaan sudah diproses sebelumnya.']);
            }
        }
        $walletId = $resolved['wallet']->id;
        $before = (float) $resolved['wallet']->balance;
        $after = $before + $amount;
        $refNo = 'TRX-' . strtoupper(substr(md5(uniqid((string) $walletId, true)), 0, 12));
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $walletId, 'type' => 'TOPUP', 'amount' => $amount,
            'balance_before' => $before, 'balance_after' => $after, 'status' => 'SUCCESS',
            'method' => $method, 'reference_no' => $refNo, 'idempotency_key' => $idem,
            'description' => 'Top up GrSaldo via ' . $method,
            'direction' => 'CREDIT', 'is_earning' => false, 'user_id' => (int) $resolved['user']->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('wallets')->where('id', $walletId)
            ->update(['balance' => $after, 'updated_at' => now()]);
        $row = DB::table('wallet_transactions')->where('wallet_id', $walletId)
            ->where('reference_no', $refNo)->first();
        return response()->json(['status' => 'success', 'data' => $row], 201);
    }

    /** GET /api/wallet/topup/{reference_no}?user_id=X */
    public function walletTopupStatus(Request $request, $referenceNo)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $resolved = $this->resolveWallet($request->query('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $row = DB::table('wallet_transactions')->where('wallet_id', $resolved['wallet']->id)
            ->where('reference_no', $referenceNo)->first();
        if (!$row) {
            return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $row]);
    }

    /** POST /api/wallet/topup/{reference_no}/complete {user_id} — konfirmasi manual pembayaran. */
    public function walletTopupComplete(Request $request, $referenceNo)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $resolved = $this->resolveWallet($request->input('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $row = DB::table('wallet_transactions')->where('wallet_id', $resolved['wallet']->id)
            ->where('reference_no', $referenceNo)->first();
        if (!$row) {
            return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan'], 404);
        }
        if ($row->status === 'SUCCESS') {
            return response()->json(['status' => 'success', 'data' => $row, 'message' => 'Sudah selesai.']);
        }
        if ($row->status !== 'PENDING') {
            return response()->json(['status' => 'error', 'message' => 'Transaksi tidak bisa dikonfirmasi (status ' . $row->status . ')'], 400);
        }
        $walletId = $resolved['wallet']->id;
        $after = (float) $resolved['wallet']->balance + (float) $row->amount;
        DB::table('wallets')->where('id', $walletId)
            ->update(['balance' => $after, 'updated_at' => now()]);
        DB::table('wallet_transactions')->where('id', $row->id)->update([
            'status' => 'SUCCESS', 'balance_after' => $after,
            'updated_at' => now(),
        ]);
        return response()->json(['status' => 'success', 'data' => DB::table('wallet_transactions')->where('id', $row->id)->first()]);
    }

    /** GET /api/wallet/rekening?user_id=X */
    public function walletRekening(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $resolved = $this->resolveWallet($request->query('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $this->ensureWalletTables();
        $rows = DB::table('user_payment_methods')->where('user_id', $resolved['user']->id)
            ->orderByDesc('is_default')->orderBy('created_at')->get();
        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    /** POST /api/wallet/rekening {user_id, bank_name, account_number, account_holder, is_default} */
    public function walletRekeningStore(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $resolved = $this->resolveWallet($request->input('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $bankName = trim($request->input('bank_name', ''));
        $accountNumber = trim($request->input('account_number', ''));
        $accountHolder = trim($request->input('account_holder', ''));
        if ($bankName === '' || $accountNumber === '' || $accountHolder === '') {
            return response()->json(['status' => 'error', 'message' => 'Nama bank, nomor rekening, dan nama pemilik wajib diisi'], 400);
        }
        $this->ensureWalletTables();
        $uid = $resolved['user']->id;
        $isDefault = (bool) $request->input('is_default', false);
        if ($isDefault) {
            DB::table('user_payment_methods')->where('user_id', $uid)->update(['is_default' => false]);
        }
        $id = DB::table('user_payment_methods')->insertGetId([
            'user_id' => $uid, 'provider' => 'BANK', 'bank_name' => $bankName,
            'account_number' => $accountNumber, 'account_holder' => $accountHolder,
            'is_default' => $isDefault, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['status' => 'success', 'data' => DB::table('user_payment_methods')->where('id', $id)->first()], 201);
    }

    /** PUT /api/wallet/rekening/{id} {user_id, is_default?, account_number?, account_holder?} */
    public function walletRekeningUpdate(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $resolved = $this->resolveWallet($request->input('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $this->ensureWalletTables();
        $row = DB::table('user_payment_methods')->where('id', $id)
            ->where('user_id', $resolved['user']->id)->first();
        if (!$row) {
            return response()->json(['status' => 'error', 'message' => 'Rekening tidak ditemukan'], 404);
        }
        $payload = ['updated_at' => now()];
        if ($request->has('is_default')) {
            $isDefault = (bool) $request->input('is_default');
            if ($isDefault) {
                DB::table('user_payment_methods')->where('user_id', $resolved['user']->id)->update(['is_default' => false]);
            }
            $payload['is_default'] = $isDefault;
        }
        if ($request->has('account_number')) {
            $payload['account_number'] = trim($request->input('account_number'));
        }
        if ($request->has('account_holder')) {
            $payload['account_holder'] = trim($request->input('account_holder'));
        }
        DB::table('user_payment_methods')->where('id', $id)->update($payload);
        return response()->json(['status' => 'success', 'data' => DB::table('user_payment_methods')->where('id', $id)->first()]);
    }

    /** DELETE /api/wallet/rekening/{id}?user_id=X */
    public function walletRekeningDelete(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $resolved = $this->resolveWallet($request->input('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $this->ensureWalletTables();
        $row = DB::table('user_payment_methods')->where('id', $id)
            ->where('user_id', $resolved['user']->id)->first();
        if (!$row) {
            return response()->json(['status' => 'error', 'message' => 'Rekening tidak ditemukan'], 404);
        }
        DB::table('user_payment_methods')->where('id', $id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Rekening dihapus.']);
    }

    /**
     * POST /api/wallet/withdraw {user_id, amount, rekening_id, pin, idempotency_key}
     * Validasi PIN + rate-limit, saldo dicek server-side saat submit (anti race condition).
     */
    public function walletWithdraw(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $resolved = $this->resolveWallet($request->input('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $this->ensureWalletTables();
        $pin = trim((string) $request->input('pin', ''));
        // 1. Verifikasi PIN + rate limit
        $user = $resolved['user'];
        if ($user->wallet_pin === null) {
            return response()->json(['status' => 'error', 'message' => 'PIN wallet belum dibuat. Buat PIN terlebih dahulu.'], 400);
        }
        $locked = $user->wallet_locked_until !== null && strtotime($user->wallet_locked_until) > time();
        if ($locked) {
            $mins = (int) ceil((strtotime($user->wallet_locked_until) - time()) / 60);
            return response()->json(['status' => 'error', 'message' => "PIN terkunci karena terlalu banyak percobaan salah. Coba lagi dalam {$mins} menit."], 423);
        }
        if (!\Hash::check($pin, $user->wallet_pin)) {
            $attempts = min(5, (int) ($user->wallet_pin_attempts ?? 0) + 1);
            $update = ['wallet_pin_attempts' => $attempts, 'updated_at' => now()];
            if ($attempts >= 5) {
                $update['wallet_locked_until'] = now()->addMinutes(5);
            }
            DB::table('users')->where('id', $user->id)->update($update);
            return response()->json(['status' => 'error', 'message' => $attempts >= 5 ? 'PIN salah 5x, wallet terkunci 5 menit.' : 'PIN salah. Sisa percobaan: ' . (5 - $attempts) . '.'], 401);
        }
        DB::table('users')->where('id', $user->id)->update(['wallet_pin_attempts' => 0, 'wallet_locked_until' => null]);

        // 2. Validasi nominal & saldo (server-side)
        $amount = (float) ($request->input('amount') ?? 0);
        if ($amount < 25000) {
            return response()->json(['status' => 'error', 'message' => 'Minimum penarikan Rp 25.000'], 400);
        }
        if ($amount > 5000000) {
            return response()->json(['status' => 'error', 'message' => 'Maksimum penarikan Rp 5.000.000 per transaksi'], 400);
        }
        $rekeningId = $request->input('rekening_id');
        $rekening = DB::table('user_payment_methods')->where('id', $rekeningId)
            ->where('user_id', $user->id)->first();
        if (!$rekening) {
            return response()->json(['status' => 'error', 'message' => 'Rekening tujuan tidak ditemukan'], 404);
        }
        // Kunci baris wallet untuk mencegah race condition saldo
        $wallet = DB::table('wallets')->where('id', $resolved['wallet']->id)->lockForUpdate()->first();
        if ((float) $wallet->balance < $amount) {
            return response()->json(['status' => 'error', 'message' => 'Saldo tidak cukup untuk penarikan ini.'], 400);
        }

        // Idempotency
        $idem = $request->input('idempotency_key');
        if ($idem) {
            $dup = DB::table('wallet_transactions')->where('wallet_id', $wallet->id)
                ->where('idempotency_key', $idem)->first();
            if ($dup) {
                return response()->json(['status' => 'success', 'data' => $dup, 'message' => 'Permintaan sudah diproses sebelumnya.']);
            }
        }

        $refNo = 'WD-' . strtoupper(substr(md5(uniqid((string) $wallet->id, true)), 0, 12));
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $wallet->id, 'type' => 'WITHDRAW', 'amount' => $amount,
            'balance_before' => (float) $wallet->balance,
            'balance_after' => (float) $wallet->balance - $amount,
            'status' => 'SUCCESS', 'method' => 'BANK_TRANSFER',
            'reference_no' => $refNo, 'reference_id' => $rekeningId, 'idempotency_key' => $idem,
            'description' => "Tarik dana ke {$rekening->bank_name} {$rekening->account_number} a.n. {$rekening->account_holder}",
            'direction' => 'DEBIT', 'is_earning' => false, 'user_id' => (int) $user->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('wallets')->where('id', $wallet->id)
            ->update(['balance' => DB::raw('balance - ' . $amount), 'updated_at' => now()]);
        $row = DB::table('wallet_transactions')->where('wallet_id', $wallet->id)
            ->where('reference_no', $refNo)->first();
        return response()->json(['status' => 'success', 'data' => $row, 'message' => 'Penarikan berhasil diproses.'], 201);
    }

    /** GET /api/wallet/withdraws?user_id=X */
    public function walletWithdraws(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $resolved = $this->resolveWallet($request->query('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $rows = DB::table('wallet_transactions')->where('wallet_id', $resolved['wallet']->id)
            ->where('type', 'WITHDRAW')->orderBy('created_at', 'desc')->limit(50)->get();
        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    /**
     * POST /api/wallet/pin/set {user_id, old_pin? (wajib jika sudah punya PIN), new_pin (6 digit)}
     */
    public function walletPinSet(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $resolved = $this->resolveWallet($request->input('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $newPin = trim((string) $request->input('new_pin', ''));
        if (!preg_match('/^\d{6}$/', $newPin)) {
            return response()->json(['status' => 'error', 'message' => 'PIN harus 6 digit angka.'], 400);
        }
        $this->ensureWalletTables();
        $user = $resolved['user'];
        if ($user->wallet_pin !== null) {
            $oldPin = trim((string) $request->input('old_pin', ''));
            if (!\Hash::check($oldPin, $user->wallet_pin)) {
                return response()->json(['status' => 'error', 'message' => 'PIN lama salah.'], 401);
            }
        }
        DB::table('users')->where('id', $user->id)->update([
            'wallet_pin' => \Hash::make($newPin),
            'wallet_pin_attempts' => 0, 'wallet_locked_until' => null,
            'updated_at' => now(),
        ]);
        return response()->json(['status' => 'success', 'message' => 'PIN wallet berhasil dibuat/diubah.']);
    }

    /** POST /api/wallet/pin/verify {user_id, pin} — cek status & validitas PIN. */
    public function walletPinVerify(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $resolved = $this->resolveWallet($request->input('user_id'));
        if (!$resolved) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        $user = $resolved['user'];
        if ($user->wallet_pin === null) {
            return response()->json(['status' => 'success', 'data' => ['pin_set' => false], 'message' => 'PIN belum dibuat.']);
        }
        $pin = trim((string) $request->input('pin', ''));
        if (\Hash::check($pin, $user->wallet_pin)) {
            DB::table('users')->where('id', $user->id)->update(['wallet_pin_attempts' => 0, 'wallet_locked_until' => null]);
            return response()->json(['status' => 'success', 'data' => ['pin_set' => true, 'valid' => true]]);
        }
        return response()->json(['status' => 'error', 'message' => 'PIN salah.'], 401);
    }

    /**
     * Token sesi singkat untuk webview PPOB.
     * GET /api/ppob/webview-token?user_id=N
     * Mengembalikan token deterministik yang valid 1 jam (berbasis jam unix),
     * dihitung dari APP_KEY sehingga tidak perlu tabel baru di database.
     */
    public function ppobWebviewToken(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $userId = (int) $request->input('user_id', 0);
        if ($userId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'user_id tidak valid.'], 422);
        }
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan.'], 404);
        }
        $hour = (int) (time() / 3600);
        $token = hash('sha256', 'ppob-' . $user->id . '-' . config('app.key') . '-' . $hour);
        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
                'user_id' => $user->id,
                'full_name' => $user->full_name,
                'phone' => $user->phone ?? null,
                'hour' => $hour,
            ],
        ]);
    }

    /**
     * Ensure kendaraan tables exist (portable, idempotent).
     */
    /**
     * Token sesi singkat untuk webview Iklan Baris.
     * GET /api/iklan-gratis/webview-token?user_id=N
     * Valid 1 jam (deterministik, berbasis jam unix + APP_KEY).
     * Hanya user role MEMBER yang diizinkan.
     */
    public function iklanWebviewToken(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $userId = (int) $request->input('user_id', 0);
        if ($userId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'user_id tidak valid.'], 422);
        }
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan.'], 404);
        }
        if (strtoupper((string) ($user->role ?? 'MEMBER')) !== 'MEMBER') {
            return response()->json(['status' => 'error', 'message' => 'Akun ini tidak diizinkan (' . strtoupper((string) ($user->role ?? '')) . ').'], 403);
        }
        $hour = (int) (time() / 3600);
        $token = hash('sha256', 'iklan-' . $user->id . '-' . config('app.key') . '-' . $hour);
        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
                'user_id' => $user->id,
                'full_name' => $user->full_name,
                'phone' => $user->phone ?? null,
                'hour' => $hour,
            ],
        ]);
    }

    private function ensureKendaraanTables()
    {
        if (!DB::getSchemaBuilder()->hasTable('driver_vehicles')) {
            DB::statement('CREATE TABLE driver_vehicles (
                id SERIAL PRIMARY KEY,
                driver_id BIGINT NOT NULL,
                vehicle_type VARCHAR(30) NOT NULL,
                brand VARCHAR(100) NULL,
                model VARCHAR(100) NULL,
                plate_number VARCHAR(20) NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uq_dv_plat UNIQUE (plate_number)
            )');
        }
        foreach ([
            ['year_kendaraan', 'INT NULL'],
            ['color', 'VARCHAR(30) NULL'],
            ['foto_kendaraan', 'VARCHAR(255) NULL'],
            ['foto_stnk', 'VARCHAR(255) NULL'],
            ['status_verifikasi', "VARCHAR(20) NOT NULL DEFAULT 'approved'"],
            ['is_default', 'BOOLEAN NOT NULL DEFAULT FALSE'],
            ['deleted_at', 'TIMESTAMP NULL'],
        ] as $col) {
            if (!DB::getSchemaBuilder()->hasColumn('driver_vehicles', $col[0])) {
                DB::statement("ALTER TABLE driver_vehicles ADD COLUMN {$col[0]} {$col[1]}");
            }
        }
    }

    /**
     * Transform driver_vehicles row to kendaraan payload.
     */
    private function kendaraanPayload($v)
    {
        return [
            'id' => (int) $v->id,
            'kapasitas' => (int) ($v->capacity ?? 1),
            'jenis_kendaraan' => $v->vehicle_type,
            'plat_nomor' => $v->plate_number,
            'merk' => $v->brand,
            'model' => $v->model,
            'tahun' => $v->year_kendaraan ?? null,
            'warna' => $v->color ?? null,
            'foto_kendaraan' => $v->foto_kendaraan ?? null,
            'foto_stnk' => $v->foto_stnk ?? null,
            'is_aktif' => (bool) $v->is_active,
            'is_default' => (bool) ($v->is_default ?? false),
            'status_verifikasi' => $v->status_verifikasi ?? 'approved',
            'deleted_at' => $v->deleted_at ?? null,
        ];
    }

    /**
     * List kendaraan milik driver yang login.
     * GET /api/driver/kendaraan?user_id=
     */
    public function kendaraanList(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $this->ensureKendaraanTables();
        $userId = (int) $request->query('user_id', 0);
        if ($userId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'user_id diperlukan.'], 400);
        }
        $driver = DB::table('drivers')->where('user_id', $userId)->first();
        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver tidak ditemukan.'], 404);
        }
        $list = DB::table('driver_vehicles')
            ->where('driver_id', $driver->id)
                        ->orderBy('is_default', 'desc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn($v) => $this->kendaraanPayload($v));
        return response()->json(['status' => 'success', 'data' => $list]);
    }

    /**
     * Tambah kendaraan baru.
     * POST /api/driver/kendaraan (json: user_id, jenis_kendaraan, plat_nomor, merk?, model?, tahun?, warna?, foto_kendaraan?, foto_stnk?)
     */
    public function kendaraanStore(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $this->ensureKendaraanTables();
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'jenis_kendaraan' => 'required|string|in:MOTOR,MOBIL,BAJAJ,TRUK,PICKUP_TERBUKA,PICKUP_BOX',
            'plat_nomor' => 'required|string|max:20',
            'merk' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'tahun' => 'nullable|integer|min:1950|max:' . (date('Y') + 1),
            'warna' => 'nullable|string|max:30',
            'foto_kendaraan' => 'nullable|string|max:5000000',
            'foto_stnk' => 'nullable|string|max:5000000',
            'kapasitas' => 'nullable|integer|min:1|max:20',
        ]);
        $driver = DB::table('drivers')->where('user_id', (int) $validated['user_id'])->first();
        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver tidak ditemukan.'], 404);
        }
        $plate = strtoupper(trim($validated['plat_nomor']));
        if (DB::table('driver_vehicles')->where('plate_number', $plate)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Plat nomor sudah terdaftar di sistem.'], 409);
        }
        $isNewFirst = DB::table('driver_vehicles')->where('driver_id', $driver->id)->count() === 0;
        $id = DB::table('driver_vehicles')->insertGetId([
            'driver_id' => $driver->id,
            'vehicle_type' => $validated['jenis_kendaraan'],
            'brand' => $validated['merk'] ?? null,
            'model' => $validated['model'] ?? null,
            'year_kendaraan' => $validated['tahun'] ?? null,
            'color' => $validated['warna'] ?? null,
            'plate_number' => $plate,
            'foto_kendaraan' => $validated['foto_kendaraan'] ?? null,
            'foto_stnk' => $validated['foto_stnk'] ?? null,
            'capacity' => isset($validated['kapasitas']) ? (int) $validated['kapasitas'] : (strtoupper((string) $validated['jenis_kendaraan']) === 'MOTOR' ? 1 : 4),
            'is_active' => true,
            'is_default' => $isNewFirst,
            'status_verifikasi' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $v = DB::table('driver_vehicles')->where('id', $id)->first();
        return response()->json(['status' => 'success', 'message' => 'Kendaraan berhasil ditambahkan.', 'data' => $this->kendaraanPayload($v)], 201);
    }

    /**
     * Update data kendaraan.
     * PUT /api/driver/kendaraan/{id} (json: user_id, plat_nomor?, merk?, model?, tahun?, warna?, foto_kendaraan?, foto_stnk?)
     */
    public function kendaraanUpdate(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $this->ensureKendaraanTables();
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'plat_nomor' => 'nullable|string|max:20',
            'merk' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'tahun' => 'nullable|integer|min:1950|max:' . (date('Y') + 1),
            'warna' => 'nullable|string|max:30',
            'foto_kendaraan' => 'nullable|string|max:5000000',
            'foto_stnk' => 'nullable|string|max:5000000',
            'kapasitas' => 'nullable|integer|min:1|max:20',
        ]);
        $driver = DB::table('drivers')->where('user_id', (int) $validated['user_id'])->first();
        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver tidak ditemukan.'], 404);
        }
        $v = DB::table('driver_vehicles')->where('id', $id)->where('driver_id', $driver->id)->first();
        if (!$v) {
            return response()->json(['status' => 'error', 'message' => 'Kendaraan tidak ditemukan.'], 404);
        }
        if (!empty($validated['plat_nomor'])) {
            $plate = strtoupper(trim($validated['plat_nomor']));
            if ($plate !== $v->plate_number && DB::table('driver_vehicles')->where('plate_number', $plate)->exists()) {
                return response()->json(['status' => 'error', 'message' => 'Plat nomor sudah terdaftar di sistem.'], 409);
            }
        }
        $upd = [
            'brand' => $validated['merk'] ?? $v->brand,
            'model' => $validated['model'] ?? $v->model,
            'capacity' => isset($validated['kapasitas']) ? (int) $validated['kapasitas'] : ($v->capacity ?? 1),
            'year_kendaraan' => $validated['tahun'] ?? $v->year_kendaraan,
            'color' => $validated['warna'] ?? $v->color,
            'foto_kendaraan' => $validated['foto_kendaraan'] ?? $v->foto_kendaraan,
            'foto_stnk' => $validated['foto_stnk'] ?? $v->foto_stnk,
            'updated_at' => now(),
        ];
        if (!empty($validated['plat_nomor'])) {
            $upd['plate_number'] = strtoupper(trim($validated['plat_nomor']));
        }
        DB::table('driver_vehicles')->where('id', $id)->update($upd);
        $v = DB::table('driver_vehicles')->where('id', $id)->first();
        return response()->json(['status' => 'success', 'message' => 'Kendaraan berhasil diperbarui.', 'data' => $this->kendaraanPayload($v)]);
    }

    /**
     * Soft delete kendaraan (tidak boleh hapus jika sedang dalam order berjalan).
     * DELETE /api/driver/kendaraan/{id}?user_id=
     */
    public function kendaraanDestroy(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $this->ensureKendaraanTables();
        $userId = (int) $request->query('user_id', 0);
        $driver = DB::table('drivers')->where('user_id', $userId)->first();
        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver tidak ditemukan.'], 404);
        }
        $v = DB::table('driver_vehicles')->where('id', $id)->where('driver_id', $driver->id)->first();
        if (!$v) {
            return response()->json(['status' => 'error', 'message' => 'Kendaraan tidak ditemukan.'], 404);
        }
        // Cek order berjalan: driver assigned & status aktif (bukan selesai/batal)
        $inOrder = DB::table('orders')
            ->where('driver_id', $driver->id)
            ->whereNotIn('status', ['COMPLETED', 'CANCELED', 'FAILED'])
            ->count();
        if ($inOrder > 0) {
            return response()->json(['status' => 'error', 'message' => 'Kendaraan tidak dapat dihapus karena sedang dalam order berjalan.'], 409);
        }
        DB::table('driver_vehicles')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        return response()->json(['status' => 'success', 'message' => 'Kendaraan berhasil dihapus.']);
    }

    /**
     * Toggle aktif/nonaktif untuk bid. Aturan: max 1 kendaraan aktif per jenis kendaraan.
     * PATCH /api/driver/kendaraan/{id}/toggle-aktif?user_id=
     */
    public function kendaraanToggleAktif(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $this->ensureKendaraanTables();
        $userId = (int) $request->query('user_id', 0);
        $driver = DB::table('drivers')->where('user_id', $userId)->first();
        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver tidak ditemukan.'], 404);
        }
        $v = DB::table('driver_vehicles')->where('id', $id)->where('driver_id', $driver->id)->first();
        if (!$v) {
            return response()->json(['status' => 'error', 'message' => 'Kendaraan tidak ditemukan.'], 404);
        }
        if ($v->status_verifikasi !== 'approved') {
            return response()->json(['status' => 'error', 'message' => 'Kendaraan belum terverifikasi sehingga tidak dapat diaktifkan.'], 409);
        }
        $newActive = !$v->is_active;
        if ($newActive) {
            // Max 1 aktif per jenis: matikan kendaraan aktif lain dengan jenis sama
            DB::table('driver_vehicles')
                ->where('driver_id', $driver->id)
                ->where('vehicle_type', $v->vehicle_type)
                ->where('is_active', true)
                                ->where('id', '!=', $id)
                ->update(['is_active' => false, 'updated_at' => now()]);
        }
        DB::table('driver_vehicles')->where('id', $id)->update(['is_active' => $newActive, 'updated_at' => now()]);
        $v = DB::table('driver_vehicles')->where('id', $id)->first();
        return response()->json([
            'status' => 'success',
            'message' => $newActive ? 'Kendaraan aktif untuk menerima order.' : 'Kendaraan dinonaktifkan dari bid order.',
            'data' => $this->kendaraanPayload($v),
        ]);
    }

    /**
     * Set kendaraan utama.
     * PATCH /api/driver/kendaraan/{id}/set-default?user_id=
     */
    public function kendaraanSetDefault(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }

        $this->ensureKendaraanTables();
        $userId = (int) $request->query('user_id', 0);
        $driver = DB::table('drivers')->where('user_id', $userId)->first();
        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver tidak ditemukan.'], 404);
        }
        $v = DB::table('driver_vehicles')->where('id', $id)->where('driver_id', $driver->id)->first();
        if (!$v) {
            return response()->json(['status' => 'error', 'message' => 'Kendaraan tidak ditemukan.'], 404);
        }
        DB::table('driver_vehicles')->where('driver_id', $driver->id)->update(['is_default' => false, 'updated_at' => now()]);
        DB::table('driver_vehicles')->where('id', $id)->update(['is_default' => true, 'updated_at' => now()]);
        $v = DB::table('driver_vehicles')->where('id', $id)->first();
        return response()->json(['status' => 'success', 'message' => 'Kendaraan utama berhasil diatur.', 'data' => $this->kendaraanPayload($v)]);
    }

    // =========================================================================
    // MODUL RIDE-HAILING (GrAntar) — ride lifecycle + settlement GrSaldo
    // =========================================================================

    /** Pastikan kolom penumpang di orders (snapshot) + tabel passenger_contacts + ride_ratings tersedia. */
    private function ensureRidesTables()
    {
        if (!DB::getSchemaBuilder()->hasColumn('driver_vehicles', 'capacity')) {
            DB::statement('ALTER TABLE driver_vehicles ADD COLUMN IF NOT EXISTS capacity INTEGER NOT NULL DEFAULT 1');
        }
        foreach (['vehicle_type', 'vehicle_capacity', 'passenger_count'] as $col) {
            if (!DB::getSchemaBuilder()->hasColumn('orders', $col)) {
                DB::statement('ALTER TABLE orders ADD COLUMN IF NOT EXISTS ' . $col . ($col === 'vehicle_type' ? ' VARCHAR(20)' : ' INTEGER'));  
            }
        }
        foreach (['payment_status' => 'VARCHAR(30) DEFAULT \'UNPAID\'', 'is_cod' => 'BOOLEAN NOT NULL DEFAULT FALSE', 'payment_method_snapshot' => 'VARCHAR(30)', 'confirmed_at' => 'TIMESTAMP', 'picked_up_at' => 'TIMESTAMP', 'started_at' => 'TIMESTAMP', 'completed_at' => 'TIMESTAMP', 'cancelled_at' => 'TIMESTAMP', 'cancel_reason' => 'TEXT', 'cancelled_by' => 'BIGINT'] as $col => $type) {
            if (!DB::getSchemaBuilder()->hasColumn('orders', $col)) {
                DB::statement('ALTER TABLE orders ADD COLUMN IF NOT EXISTS ' . $col . ' ' . $type);
            }
        }
        if (!DB::getSchemaBuilder()->hasTable('ride_services')) {
            DB::statement('CREATE TABLE IF NOT EXISTS ride_services (
                id BIGSERIAL PRIMARY KEY,
                code VARCHAR(30) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                vehicle_type VARCHAR(20) NOT NULL,
                capacity INTEGER NOT NULL,
                base_fare INTEGER NOT NULL,
                fare_per_km INTEGER NOT NULL,
                minimum_fare INTEGER NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                sort_order INTEGER NOT NULL DEFAULT 0,
                icon VARCHAR(50),
                created_at TIMESTAMPTZ DEFAULT NOW(), updated_at TIMESTAMPTZ DEFAULT NOW()
            )');
            $this->seedRideServices();
        } elseif (DB::table('ride_services')->count() === 0) {
            $this->seedRideServices();
        }
        if (DB::getSchemaBuilder()->hasTable('ride_ratings') && DB::getSchemaBuilder()->hasColumn('ride_ratings', 'ride_id')) {
            try {
                DB::statement('ALTER TABLE ride_ratings ALTER COLUMN ride_id TYPE VARCHAR(36) USING ride_id::text');
            } catch (\Throwable $e) {
                // Kolom sudah varchar atau tidak bisa diubah — aman diabaikan
            }
        }
        foreach (['passenger_type', 'passenger_name', 'passenger_phone', 'passenger_notes'] as $col) {
            if (!DB::getSchemaBuilder()->hasColumn('orders', $col)) {
                DB::statement('ALTER TABLE orders ADD COLUMN IF NOT EXISTS ' . $col . ($col === 'passenger_type' ? " VARCHAR(20) DEFAULT 'SELF'" : ($col === 'passenger_notes' ? ' TEXT' : ' VARCHAR(100)')));
            }
        }
        if (!DB::getSchemaBuilder()->hasColumn('orders', 'service_type')) {
            DB::statement('ALTER TABLE orders ADD COLUMN IF NOT EXISTS service_type VARCHAR(20)');
        }
        if (!DB::getSchemaBuilder()->hasColumn('orders', 'distance_km')) {
            DB::statement('ALTER TABLE orders ADD COLUMN IF NOT EXISTS distance_km DECIMAL(10, 2) DEFAULT 0');
        }
        if (!DB::getSchemaBuilder()->hasColumn('orders', 'vehicle_id')) {
            DB::statement('ALTER TABLE orders ADD COLUMN IF NOT EXISTS vehicle_id VARCHAR(100)');
        }
        if (!DB::getSchemaBuilder()->hasTable('passenger_contacts')) {
            DB::statement('CREATE TABLE IF NOT EXISTS passenger_contacts (
                id BIGSERIAL PRIMARY KEY, user_id BIGINT NOT NULL,
                name VARCHAR(100) NOT NULL, phone VARCHAR(25) NOT NULL,
                relationship VARCHAR(50), is_favorite BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMPTZ DEFAULT NOW(), updated_at TIMESTAMPTZ DEFAULT NOW()
            )');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_pax_contacts_user ON passenger_contacts(user_id)');
        }
        if (!DB::getSchemaBuilder()->hasTable('ride_ratings')) {
            DB::statement('CREATE TABLE IF NOT EXISTS ride_ratings (
                id BIGSERIAL PRIMARY KEY, ride_id VARCHAR(36) NOT NULL,
                from_user_id BIGINT NOT NULL, to_user_id BIGINT NOT NULL,
                rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
                comment TEXT, created_at TIMESTAMPTZ DEFAULT NOW()
            )');
        }
        DB::statement('CREATE INDEX IF NOT EXISTS idx_orders_ride ON orders(order_type, status)');
    }

    /** Seed katalog layanan ride (Motor, Mobil 4, Mobil 6). Dapat ditambah Admin tanpa update APK. */
    private function seedRideServices(): void
    {
        $defaults = [
            ['code' => 'MOTOR', 'name' => 'Motor', 'vehicle_type' => 'MOTOR', 'capacity' => 1, 'base_fare' => 3000, 'fare_per_km' => $this->getSettingFloat('ride_cost_per_km', 5000), 'minimum_fare' => 5000, 'sort_order' => 1, 'icon' => 'motorcycle'],
            ['code' => 'MOBIL_4', 'name' => 'Mobil 4 Penumpang', 'vehicle_type' => 'MOBIL', 'capacity' => 4, 'base_fare' => 6000, 'fare_per_km' => $this->getSettingFloat('ride_cost_per_km', 5000), 'minimum_fare' => 10000, 'sort_order' => 2, 'icon' => 'car'],
            ['code' => 'MOBIL_6', 'name' => 'Mobil 6 Penumpang', 'vehicle_type' => 'MOBIL', 'capacity' => 6, 'base_fare' => 8000, 'fare_per_km' => $this->getSettingFloat('ride_cost_per_km', 5000), 'minimum_fare' => 12000, 'sort_order' => 3, 'icon' => 'car'],
        ];
        foreach ($defaults as $s) {
            DB::table('ride_services')->insert(array_merge($s, ['created_at' => now(), 'updated_at' => now()]));
        }
    }

    /** GET /api/ride-services?vehicle_type?= */
    public function rideServices(Request $request)
    {
        $this->ensureRidesTables();
        $q = DB::table('ride_services')->where('is_active', true);
        $vt = (string) ($request->query('vehicle_type') ?? '');
        if ($vt !== '') {
            $q = $q->where('vehicle_type', strtoupper($vt));
        }
        return response()->json(['status' => 'success', 'data' => $q->orderBy('sort_order')->get()]);
    }

    /** Tarif ride: base + jarak × per-km; durasi estimasi. */
    private function calcRideFare(string $serviceType, float $distanceKm): array
    {
        $this->ensureRidesTables();
        $svc = DB::table('ride_services')->where('code', strtoupper($serviceType))->where('is_active', true)->first();
        $base = $svc ? (float) $svc->base_fare : ((strtoupper($serviceType) === 'MOBIL' || str_starts_with(strtoupper($serviceType), 'MOBIL')) ? 6000.0 : 3000.0);
        $costPerKm = $svc ? (float) $svc->fare_per_km : $this->getSettingFloat('ride_cost_per_km', 5000);
        $distanceFare = round(ceil(max($distanceKm, 1)) * $costPerKm, 0);
        $total = max(round($base + $distanceFare, 0), (float) ($svc ? $svc->minimum_fare : 5000));
        $duration = (int) ceil(($distanceKm / 30.0) * 60);
        return ['base_fare' => (int) $base, 'distance_fare' => (int) $distanceFare, 'total' => (int) $total, 'duration_minutes' => $duration, 'cost_per_km' => $costPerKm, 'minimum_fare' => (int) ($svc ? $svc->minimum_fare : 5000)];
    }



    private function ridePayload($ride): array
    {
        $driverName = null;
        $driverRating = null;
        $vehicle = null;
        if ($ride->driver_id) {
            $d = DB::table('drivers')->where('id', $ride->driver_id)->first();
            if ($d) {
                $u = DB::table('users')->where('id', $d->user_id)->first();
                $driverName = $u ? $u->full_name : null;
                $driverRating = $d->rating ? (float) $d->rating : null;
                if (is_string($ride->vehicle_id ?? null) && $ride->vehicle_id !== '') {
                    $parts = preg_split('/•/', (string) $ride->vehicle_id, 3);
                    $vehicle = [
                        'snapshot' => trim($ride->vehicle_id),
                        'brand_model' => trim($parts[0] ?? ''),
                        'plate_number' => trim($parts[2] ?? ''),
                        'vehicle_type' => $ride->vehicle_type ?? null,
                        'vehicle_capacity' => $ride->vehicle_capacity ?? null,
                        'passenger_count' => $ride->passenger_count ?? null,
                    ];
                }
            }
        }
        $rating = DB::table('ride_ratings')->where('ride_id', (string) $ride->id)->where('from_user_id', $ride->user_id)->first();
        $svc = DB::table('ride_services')->where('code', (string) ($ride->service_type ?? ''))->first();
        $p = (array) $ride;
        $p['driver_name'] = $driverName;
        $p['driver_rating'] = $driverRating;
        $p['service'] = $svc ? ['code' => $svc->code, 'name' => $svc->name, 'vehicle_type' => $svc->vehicle_type, 'capacity' => (int) $svc->capacity, 'base_fare' => (int) $svc->base_fare, 'fare_per_km' => (int) $svc->fare_per_km, 'minimum_fare' => (int) $svc->minimum_fare] : null;
        $p['vehicle'] = $vehicle;
        $p['customer_rated'] = $rating ? (int) $rating->rating : null;
        $p['distance_km'] = $ride->ride_distance_km ?? $ride->distance_km ?? 0;
        return $p;
    }

    /** POST /api/rides/estimate {service_type, pickup_lat, pickup_lng, destination_lat, destination_lng} */
    public function ridesEstimate(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $validated = $request->validate([
            'service_code' => 'required|string|max:30',
            'vehicle_capacity' => 'nullable|integer|min:1|max:20',
            'passenger_count' => 'nullable|integer|min:1|max:20',
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'destination_lat' => 'required|numeric|between:-90,90',
            'destination_lng' => 'required|numeric|between:-180,180',
        ]);
        $this->ensureRidesTables();
        $svc = DB::table('ride_services')->where('code', strtoupper($validated['service_code']))->where('is_active', true)->first();
        if (!$svc) {
            return response()->json(['status' => 'error', 'message' => 'Layanan tidak tersedia.'], 422);
        }
        $distance = $this->haversineKm((float) $validated['pickup_lat'], (float) $validated['pickup_lng'], (float) $validated['destination_lat'], (float) $validated['destination_lng']);
        $distanceKm = round(max($distance, 1), 1);
        $fare = $this->calcRideFare($svc->code, $distanceKm);
        $paxCount = (int) ($validated['passenger_count'] ?? $svc->capacity);
        $paxCount = min(max($paxCount, 1), (int) $svc->capacity);
        return response()->json(['status' => 'success', 'data' => array_merge($fare, ['distance_km' => $distanceKm, 'service_code' => $svc->code, 'service_name' => $svc->name, 'vehicle_capacity' => (int) $svc->capacity, 'passenger_count' => $paxCount])]);
    }

    /** POST /api/rides {service_type, pickup_*, destination_*, payment_method, note?} */
    public function ridesStore(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $validated = $request->validate([
            'service_code' => 'required|string|max:30',
            'passenger_count' => 'nullable|integer|min:1|max:20',
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'pickup_address' => 'nullable|string|max:500',
            'destination_lat' => 'required|numeric|between:-90,90',
            'destination_lng' => 'required|numeric|between:-180,180',
            'destination_address' => 'nullable|string|max:500',
            'payment_method' => 'nullable|string|in:GRSALDO,CASH',
            'note' => 'nullable|string|max:500',
            // Penumpang: SELF atau OTHER (nama + HP penumpang, Gojek-style)
            'passenger_type' => 'nullable|string|in:SELF,OTHER',
            'passenger_name' => 'nullable|string|max:100',
            'passenger_phone' => 'nullable|string|max:25',
            'passenger_notes' => 'nullable|string|max:500',
        ]);
        $this->ensureRidesTables();
        $svc = DB::table('ride_services')->where('code', strtoupper($validated['service_code']))->where('is_active', true)->first();
        if (!$svc) {
            return response()->json(['status' => 'error', 'message' => 'Layanan tidak tersedia.'], 422);
        }
        $userId = (int) $request->input('user_id', 0);
        $user = DB::table('users')->where('id', $userId)->first();
        $distance = $this->haversineKm((float) $validated['pickup_lat'], (float) $validated['pickup_lng'], (float) $validated['destination_lat'], (float) $validated['destination_lng']);
        $distanceKm = round(max($distance, 1), 1);
        $fare = $this->calcRideFare($svc->code, $distanceKm);
        $paxCount = (int) ($validated['passenger_count'] ?? $svc->capacity);
        $paxCount = min(max($paxCount, 1), (int) $svc->capacity);
        $paymentMethod = strtoupper((string) ($validated['payment_method'] ?? 'GRSALDO'));
        $costPerKm = $fare['cost_per_km'];
        // Snapshot penumpang (jangan pernah ambil dari users saat runtime)
        if (strtoupper((string) ($validated['passenger_type'] ?? 'SELF')) === 'OTHER') {
            if (empty(trim((string) ($validated['passenger_name'] ?? ''))) || empty(trim((string) ($validated['passenger_phone'] ?? '')))) {
                return response()->json(['status' => 'error', 'message' => 'Nama dan nomor HP penumpang wajib diisi.'], 422);
            }
            $paxType = 'OTHER';
            $paxName = trim((string) $validated['passenger_name']);
            $paxPhone = trim((string) $validated['passenger_phone']);
        } else {
            $paxType = 'SELF';
            $paxName = $user ? $user->full_name : '';
            $paxPhone = $user ? (string) ($user->phone ?? '') : '';
        }
        // Cek saldo bila GRSALDO
        if ($paymentMethod === 'GRSALDO') {
            $cw = $this->resolveWallet($userId);
            if ($cw && (float) $cw['wallet']->balance < $fare['total']) {
                return response()->json(['status' => 'error', 'message' => 'Saldo GrSaldo tidak cukup (butuh Rp ' . number_format($fare['total'], 0, ',', '.') . '). Silakan top up atau pilih CASH.'], 400);
            }
        }
        $orderNo = 'GR-' . strtoupper(substr(md5(uniqid((string) $userId, true)), 0, 6)) . '-' . now()->format('YmdHi');
        $orderId = DB::table('orders')->insertGetId([
            'order_number' => $orderNo,
            'order_type' => 'RIDE',
            'status' => 'SEARCHING_DRIVER',
            'user_id' => $userId,
            'service_type' => $svc->code,
            'vehicle_type' => $svc->vehicle_type,
            'vehicle_capacity' => (int) $svc->capacity,
            'passenger_count' => $paxCount,
            'pickup_address' => $validated['pickup_address'] ?? ('Lokasi saya (' . $validated['pickup_lat'] . ',' . $validated['pickup_lng'] . ')'),
            'pickup_lat' => $validated['pickup_lat'],
            'pickup_lng' => $validated['pickup_lng'],
            'dropoff_address' => $validated['destination_address'] ?? ('Tujuan (' . $validated['destination_lat'] . ',' . $validated['destination_lng'] . ')'),
            'dropoff_lat' => $validated['destination_lat'],
            'dropoff_lng' => $validated['destination_lng'],
            'ride_distance_km' => $distanceKm,
            'cost_per_km_snapshot' => $costPerKm,
            'subtotal' => 0,
            'delivery_address' => $validated['destination_address'] ?? ('Tujuan (' . $validated['destination_lat'] . ',' . $validated['destination_lng'] . ')'),
            'recipient_name' => ($paxType === 'OTHER') ? $paxName : ($user ? $user->full_name : 'Penumpang'),
            'recipient_phone' => ($paxType === 'OTHER') ? $paxPhone : (string) ($user->phone ?? ''),
            'delivery_fee' => $fare['total'],
            'total_amount' => $fare['total'],
            'payment_status' => 'UNPAID',
            'is_cod' => $paymentMethod === 'CASH',
            'payment_method_snapshot' => $paymentMethod,
            'note' => $validated['note'] ?? null,
            'passenger_type' => $paxType,
            'passenger_name' => $paxName,
            'passenger_phone' => $paxPhone,
            'passenger_notes' => $validated['passenger_notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['status' => 'success', 'message' => 'Permintaan perjalanan dibuat. Mencari driver terdekat...', 'data' => array_merge($this->ridePayload(DB::table('orders')->where('id', $orderId)->first()), ['estimated_fare' => $fare['total'], 'distance_km' => $distanceKm, 'duration_minutes' => $fare['duration_minutes']])], 201);
    }

    /** GET /api/rides/current?user_id= — driver: ride aktif yang sedang ditangani (polling). */
    public function driverRidesCurrent(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $this->ensureRidesTables();
        $userId = (int) $request->query('user_id', 0);
        $driver = DB::table('drivers')->where('user_id', $userId)->first();
        if (!$driver) {
            return response()->json(['status' => 'success', 'data' => null]);
        }
        $ride = DB::table('orders')->where('order_type', 'RIDE')->where('driver_id', $driver->id)
            ->whereIn('status', ['DRIVER_ACCEPTED', 'DRIVER_ARRIVING', 'DRIVER_ARRIVED', 'TRIP_STARTED'])
            ->orderBy('confirmed_at', 'desc')->first();
        if (!$ride) {
            return response()->json(['status' => 'success', 'data' => null]);
        }
        return response()->json(['status' => 'success', 'data' => $this->ridePayload($ride)]);
    }

    /** GET /api/rides/history?user_id=&role=customer|driver */
    public function ridesHistory(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $this->ensureRidesTables();
        $userId = (int) $request->query('user_id', 0);
        $role = $request->query('role', 'customer');
        if ($role === 'driver') {
            $driver = DB::table('drivers')->where('user_id', $userId)->first();
            if (!$driver) {
                return response()->json(['status' => 'success', 'data' => []]);
            }
            $rides = DB::table('orders')->where('order_type', 'RIDE')->where('driver_id', $driver->id)->orderBy('created_at', 'desc')->limit(50)->get();
        } else {
            $rides = DB::table('orders')->where('order_type', 'RIDE')->where('user_id', $userId)->orderBy('created_at', 'desc')->limit(50)->get();
        }
        return response()->json(['status' => 'success', 'data' => $rides->map(fn($r) => $this->ridePayload($r))]);
    }

    /** GET /api/rides/{id}?user_id= — customer & driver melihat ride (polling). */
    public function ridesShow(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $this->ensureRidesTables();
        $userId = (int) $request->query('user_id', 0);
        $ride = DB::table('orders')->where('id', $id)->first();
        if (!$ride) {
            return response()->json(['status' => 'error', 'message' => 'Perjalanan tidak ditemukan.'], 404);
        }
        // Hanya customer pemilik atau driver yang ditugaskan yang boleh lihat
        $driverUserId = null;
        if ($ride->driver_id) {
            $d = DB::table('drivers')->where('id', $ride->driver_id)->first();
            $driverUserId = $d ? (int) $d->user_id : null;
        }
        if ((int) $ride->user_id !== $userId && $driverUserId !== $userId) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        // Lokasi driver real-time terakhir saat trip aktif
        $driverLocation = null;
        if ($ride->driver_id && in_array($ride->status, ['DRIVER_ARRIVING', 'TRIP_STARTED'])) {
            $driverLocation = DB::table('driver_locations')->where('driver_id', $ride->driver_id)->orderBy('recorded_at', 'desc')->first();
        }
        return response()->json(['status' => 'success', 'data' => array_merge($this->ridePayload($ride), ['driver_location' => $driverLocation])]);
    }

    /** POST /api/rides/{id}/accept?user_id= — driver menerima ride (atomic lock, cegah double accept). */
    public function ridesAccept(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $this->ensureRidesTables();
        $userId = (int) $request->input('user_id', 0);
        $driver = DB::table('drivers')->where('user_id', $userId)->first();
        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Akun driver tidak ditemukan.'], 404);
        }
        try {
            $accepted = DB::transaction(function () use ($id, $driver) {
                $ride = DB::table('orders')->where('id', $id)->lockForUpdate()->first();
                if (!$ride) {
                    return ['ok' => false, 'msg' => 'Perjalanan tidak ditemukan.'];
                }
                if ($ride->status !== 'SEARCHING_DRIVER') {
                    return ['ok' => false, 'msg' => 'Permintaan ini sudah ditangani driver lain atau tidak lagi menunggu.'];
                }
                // Kendaraan driver: vehicle_type sesuai + kapasitas >= jumlah penumpang yang dipesan
                $paxCount = (int) max((int) ($ride->passenger_count ?? 0), 1);
                $vehicle = DB::table('driver_vehicles')
                    ->where('driver_id', $driver->id)->where('vehicle_type', $ride->vehicle_type)
                    ->where('is_active', true)                    ->where('capacity', '>=', $paxCount)
                    ->orderByDesc('capacity')->first();
                if (!$vehicle) {
                    return ['ok' => false, 'msg' => 'Kendaraan Anda tidak memenuhi kapasitas penumpang yang diminta (' . $paxCount . ' penumpang).'];
                }
                DB::table('orders')->where('id', $id)->update([
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->brand . ' ' . $vehicle->model . ' • ' . strtoupper((string) $vehicle->plate_number),
                    'status' => 'DRIVER_ACCEPTED',
                    'confirmed_at' => now(),
                    'updated_at' => now(),
                ]);
                return ['ok' => true, 'msg' => 'Perjalanan diterima.'];
            });
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menerima permintaan.'], 409);
        }
        if (!$accepted['ok']) {
            return response()->json(['status' => 'error', 'message' => $accepted['msg']], 409);
        }
        $ride = DB::table('orders')->where('id', $id)->first();
        return response()->json(['status' => 'success', 'message' => $accepted['msg'], 'data' => $this->ridePayload($ride)]);
    }

    private function rideAction(Request $request, $id, string $fromStatus, string $toStatus, array $extra = [], string $successMsg = 'Status diperbarui.')
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $this->ensureRidesTables();
        $userId = (int) $request->input('user_id', 0);
        $driver = DB::table('drivers')->where('user_id', $userId)->first();
        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Akun driver tidak ditemukan.'], 404);
        }
        $ride = DB::table('orders')->where('id', $id)->first();
        if (!$ride) {
            return response()->json(['status' => 'error', 'message' => 'Perjalanan tidak ditemukan.'], 404);
        }
        if ((string) $ride->driver_id !== (string) $driver->id) {
            return response()->json(['status' => 'error', 'message' => 'Bukan perjalanan Anda.'], 403);
        }
        if ($ride->status !== $fromStatus) {
            return response()->json(['status' => 'error', 'message' => 'Status saat ini tidak memungkinkan aksi ini (' . $ride->status . ').'], 409);
        }
        $update = array_merge(['status' => $toStatus, 'updated_at' => now()], $extra);
        DB::table('orders')->where('id', $id)->update($update);
        return response()->json(['status' => 'success', 'message' => $successMsg, 'data' => $this->ridePayload(DB::table('orders')->where('id', $id)->first())]);
    }

    /** POST /api/rides/{id}/arriving?user_id= — driver mulai menuju penumpang. */
    public function ridesArriving(Request $request, $id)
    {
        return $this->rideAction($request, $id, 'DRIVER_ACCEPTED', 'DRIVER_ARRIVING', [], 'Driver sedang menuju lokasi penjemputan.');
    }

    /** POST /api/rides/{id}/arrive?user_id= — driver sampai. */
    public function ridesArrive(Request $request, $id)
    {
        return $this->rideAction($request, $id, 'DRIVER_ARRIVING', 'DRIVER_ARRIVED', ['picked_up_at' => now()], 'Driver sudah tiba di lokasi penjemputan.');
    }

    /** POST /api/rides/{id}/start?user_id= — mulai perjalanan. */
    public function ridesStart(Request $request, $id)
    {
        return $this->rideAction($request, $id, 'DRIVER_ARRIVED', 'TRIP_STARTED', ['started_at' => now()], 'Perjalanan dimulai.');
    }

    /** POST /api/rides/{id}/complete?user_id= — trip selesai; hitung final fare server-side + settlement GrSaldo atomik. */
    public function ridesComplete(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $this->ensureRidesTables();
        $userId = (int) $request->input('user_id', 0);
        $driver = DB::table('drivers')->where('user_id', $userId)->first();
        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Akun driver tidak ditemukan.'], 404);
        }
        try {
            $result = DB::transaction(function () use ($id, $driver) {
                $ride = DB::table('orders')->where('id', $id)->lockForUpdate()->first();
                if (!$ride) {
                    return ['ok' => false, 'msg' => 'Perjalanan tidak ditemukan.'];
                }
                if ($ride->status !== 'TRIP_STARTED') {
                    return ['ok' => false, 'msg' => 'Trip belum dimulai.'];
                }
                if ((string) $ride->driver_id !== (string) $driver->id) {
                    return ['ok' => false, 'msg' => 'Bukan perjalanan Anda.'];
                }
                // Final fare: hitung ulang di server sesuai katalog layanan (jangan percaya Flutter)
                $actualDist = $this->haversineKm((float) $ride->pickup_lat, (float) $ride->pickup_lng, (float) $ride->dropoff_lat, (float) $ride->dropoff_lng);
                $fareFinal = $this->calcRideFare((string) ($ride->service_type ?? 'MOTOR'), max(round($actualDist, 1), 1));
                $finalFare = (int) $fareFinal['total'];
                // Settlement GrSaldo atomik
                $settled = $this->settleRideWallet($ride, $finalFare);
                DB::table('orders')->where('id', $id)->update([
                    'total_amount' => $finalFare,
                    'delivery_fee' => $finalFare,
                    'payment_status' => $settled['payment_status'],
                    'status' => 'COMPLETED',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);
                // Update statistik driver
                DB::table('drivers')->where('id', $driver->id)->increment('total_trips');
                return ['ok' => true, 'msg' => 'Perjalanan selesai.', 'final_fare' => $finalFare, 'payment_status' => $settled['payment_status']];
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Gagal menyelesaikan perjalanan.'], 409);
        }
        if (!$result['ok']) {
            return response()->json(['status' => 'error', 'message' => $result['msg']], 409);
        }
        return response()->json(['status' => 'success', 'message' => $result['msg'], 'data' => array_merge($this->ridePayload(DB::table('orders')->where('id', $id)->first()), ['final_fare' => (int) $result['final_fare'], 'payment_status' => $result['payment_status']])]);
    }

    /**
     * Settlement ride wallet (atomic terhadap wallet row):
     * GRSALDO: customer DEBIT final_fare (RIDE_PAYMENT) + driver CREDIT RIDE_EARNING is_earning=1.
     * CASH: hanya driver earning dicatat sebagai CASH_RIDE_EARNING (platform tidak menerima uang).
     */
    private function settleRideWallet($ride, int $finalFare): array
    {
        $this->ensureWalletTables();
        $paymentStatus = 'PAID';
        if ((bool) ($ride->is_cod ?? false) || strtoupper((string) ($ride->payment_method ?? '')) === 'CASH') {
            // Driver menerima uang tunai langsung; catat earning tetap (is_earning=1)
            $dw = $this->resolveWallet($this->driverUserId($ride));
            if ($dw && $finalFare > 0) {
                $this->postWalletTransaction([
                    'wallet_id' => $dw['wallet']->id,
                    'type' => 'CASH_RIDE_EARNING',
                    'direction' => 'CREDIT',
                    'is_earning' => true,
                    'amount' => (float) $finalFare,
                    'reference_id' => (string) $ride->id,
                    'reference_type' => 'RIDE',
                    'user_id' => (int) $dw['user']->id,
                    'description' => 'Pendapatan ride tunai ' . ($ride->order_number ?? ''),
                ]);
            }
            return ['payment_status' => 'CASH'];
        }
        // GRSALDO: debit customer, credit driver
        $cw = $this->resolveWallet((int) $ride->user_id);
        if ($cw && (float) $cw['wallet']->balance >= $finalFare) {
            $this->postWalletTransaction([
                'wallet_id' => $cw['wallet']->id,
                'type' => 'RIDE_PAYMENT',
                'direction' => 'DEBIT',
                'is_earning' => false,
                'amount' => (float) $finalFare,
                'reference_id' => (string) $ride->id,
                'reference_type' => 'RIDE',
                'user_id' => (int) $ride->user_id,
                'description' => 'Pembayaran ride ' . ($ride->order_number ?? ''),
            ]);
            $dw = $this->resolveWallet($this->driverUserId($ride));
            if ($dw && $finalFare > 0) {
                $this->postWalletTransaction([
                    'wallet_id' => $dw['wallet']->id,
                    'type' => 'RIDE_EARNING',
                    'direction' => 'CREDIT',
                    'is_earning' => true,
                    'amount' => (float) $finalFare,
                    'reference_id' => (string) $ride->id,
                    'reference_type' => 'RIDE',
                    'user_id' => (int) $dw['user']->id,
                    'description' => 'Penghasilan ride ' . ($ride->order_number ?? ''),
                ]);
            }
        } else {
            $paymentStatus = 'UNPAID';
        }
        return ['payment_status' => $paymentStatus];
    }

    private function driverUserId($ride): ?int
    {
        if (!$ride->driver_id) {
            return null;
        }
        $d = DB::table('drivers')->where('id', $ride->driver_id)->first();
        return $d ? (int) $d->user_id : null;
    }

    /** POST /api/rides/{id}/cancel?user_id= — batalkan (customer/driver) dari status awal. */
    public function ridesCancel(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $this->ensureRidesTables();
        $userId = (int) $request->input('user_id', 0);
        $ride = DB::table('orders')->where('id', $id)->first();
        if (!$ride) {
            return response()->json(['status' => 'error', 'message' => 'Perjalanan tidak ditemukan.'], 404);
        }
        $driverUserId = $this->driverUserId($ride);
        if ((int) $ride->user_id !== $userId && $driverUserId !== $userId) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        if (!in_array($ride->status, ['DRAFT', 'SEARCHING_DRIVER', 'DRIVER_ACCEPTED', 'DRIVER_ARRIVING', 'DRIVER_ARRIVED'])) {
            return response()->json(['status' => 'error', 'message' => 'Perjalanan ini sudah berjalan dan tidak bisa dibatalkan.'], 409);
        }
        $reason = trim((string) ($request->input('cancellation_reason') ?? ''));
        DB::table('orders')->where('id', $id)->update([
            'status' => 'CANCELLED',
            'cancel_reason' => $reason ?: null,
            'cancelled_by' => $userId,
            'cancelled_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['status' => 'success', 'message' => 'Perjalanan dibatalkan.']);
    }

    /** POST /api/rides/{id}/rate {user_id, rating 1-5, comment?} — customer nilai driver; driver nilai customer. */
    public function ridesRate(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $this->ensureRidesTables();
        $userId = (int) $request->input('user_id', 0);
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);
        $ride = DB::table('orders')->where('id', $id)->first();
        if (!$ride) {
            return response()->json(['status' => 'error', 'message' => 'Perjalanan tidak ditemukan.'], 404);
        }
        $driverUserId = $this->driverUserId($ride);
        if ((int) $ride->user_id !== $userId && $driverUserId !== $userId) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        if ($ride->status !== 'COMPLETED') {
            return response()->json(['status' => 'error', 'message' => 'Rating hanya tersedia setelah perjalanan selesai.'], 409);
        }
        $dup = DB::table('ride_ratings')->where('ride_id', (string) $ride->id)->where('from_user_id', $userId)->first();
        if ($dup) {
            return response()->json(['status' => 'error', 'message' => 'Anda sudah memberikan rating untuk perjalanan ini.'], 409);
        }
        // to_user: customer nilai driver; driver nilai customer
        $toUserId = ($userId === $driverUserId) ? (int) $ride->user_id : $driverUserId;
        DB::table('ride_ratings')->insert([
            'ride_id' => (string) $ride->id,
            'from_user_id' => $userId,
            'to_user_id' => $toUserId,
            'rating' => (int) $validated['rating'],
            'comment' => trim((string) ($validated['comment'] ?? '')) ?: null,
            'created_at' => now(),
        ]);
        // Perbarui rating rata-rata driver
        if ($toUserId !== $driverUserId) {
            $avg = DB::table('ride_ratings')
                ->join('orders', function ($j) {
                    $j->on('ride_ratings.ride_id', '=', DB::raw("orders.id::text"));
                })
                ->where('orders.driver_id', DB::table('drivers')->where('user_id', $toUserId)->value('id'))
                ->avg('ride_ratings.rating');
            if ($avg !== null) {
                DB::table('drivers')->where('user_id', $toUserId)->update(['rating' => round((float) $avg, 2), 'updated_at' => now()]);
            }
        }
        return response()->json(['status' => 'success', 'message' => 'Rating berhasil dikirim. Terima kasih!']);
    }

    // =========================================================================
    // PENUMPANG (passenger_contacts): pilih 'Saya' atau 'Orang lain'
    // =========================================================================

    /** GET /api/passenger-contacts?user_id= */
    public function passengerContactsIndex(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $this->ensureRidesTables();
        $userId = (int) $request->query('user_id', 0);
        $user = DB::table('users')->where('id', $userId)->first();
        $contacts = DB::table('passenger_contacts')->where('user_id', $userId)->orderBy('is_favorite', 'desc')->orderBy('name')->get();
        $self = null;
        if ($user) {
            $self = ['id' => null, 'name' => $user->full_name, 'phone' => (string) ($user->phone ?? ''), 'relationship' => 'Saya', 'is_self' => true];
        }
        return response()->json(['status' => 'success', 'data' => ['self' => $self, 'contacts' => $contacts]]);
    }

    /** POST /api/passenger-contacts {user_id, name, phone, relationship?, is_favorite?} */
    public function passengerContactsStore(Request $request)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $this->ensureRidesTables();
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:25',
            'relationship' => 'nullable|string|max:50',
            'is_favorite' => 'nullable|boolean',
        ]);
        $id = DB::table('passenger_contacts')->insertGetId([
            'user_id' => (int) $request->input('user_id', 0),
            'name' => trim((string) $validated['name']),
            'phone' => trim((string) $validated['phone']),
            'relationship' => trim((string) ($validated['relationship'] ?? '')) ?: null,
            'is_favorite' => (bool) ($validated['is_favorite'] ?? false),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['status' => 'success', 'message' => 'Penumpang ditambahkan.', 'data' => DB::table('passenger_contacts')->where('id', $id)->first()], 201);
    }

    /** PUT /api/passenger-contacts/{id} */
    public function passengerContactsUpdate(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $this->ensureRidesTables();
        $contact = DB::table('passenger_contacts')->where('id', $id)->first();
        if (!$contact) {
            return response()->json(['status' => 'error', 'message' => 'Kontak tidak ditemukan.'], 404);
        }
        if ((int) $contact->user_id !== (int) $request->input('user_id', 0)) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:25',
            'relationship' => 'nullable|string|max:50',
            'is_favorite' => 'nullable|boolean',
        ]);
        DB::table('passenger_contacts')->where('id', $id)->update(array_filter([
            'name' => isset($validated['name']) ? trim((string) $validated['name']) : null,
            'phone' => isset($validated['phone']) ? trim((string) $validated['phone']) : null,
            'relationship' => isset($validated['relationship']) ? trim((string) $validated['relationship']) : null,
            'is_favorite' => $validated['is_favorite'] ?? null,
            'updated_at' => now(),
        ], fn($v) => $v !== null));
        return response()->json(['status' => 'success', 'message' => 'Penumpang diperbarui.', 'data' => DB::table('passenger_contacts')->where('id', $id)->first()]);
    }

    /** DELETE /api/passenger-contacts/{id}?user_id= */
    public function passengerContactsDestroy(Request $request, $id)
    {
        $check = $this->requireMember($request);
        if ($check !== null) {
            return $check;
        }
        $this->ensureRidesTables();
        $contact = DB::table('passenger_contacts')->where('id', $id)->first();
        if (!$contact) {
            return response()->json(['status' => 'error', 'message' => 'Kontak tidak ditemukan.'], 404);
        }
        if ((int) $contact->user_id !== (int) $request->query('user_id', 0)) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }
        DB::table('passenger_contacts')->where('id', $id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Penumpang dihapus.']);
    }
}
