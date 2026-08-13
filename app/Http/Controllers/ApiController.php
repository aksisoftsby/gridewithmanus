<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
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
     * GET /api/wallets?user_id=X — saldo dompet pelanggan (GridePay) untuk app_customer.
     * Autocreate wallets table & row bila belum ada (production menggunakan BIGINT).
     */
    public function wallets(Request $request)
    {
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
                    'customer' => 'https://gride.web.id/apk/customer.apk',
                    'driver' => 'https://gride.web.id/apk/driver.apk',
                    'merchant' => 'https://gride.web.id/apk/merchant.apk',
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
            'vehicle_type' => 'nullable|string|in:MOTOR,MOBIL',
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
            'role' => 'DRIVER',
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
                'role' => 'DRIVER',
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
        $vehicle = null;
        if ($driver && DB::getSchemaBuilder()->hasTable('driver_vehicles')) {
            $vehicle = DB::table('driver_vehicles')
                ->where('driver_id', $driver->id)
                ->where('is_active', true)
                ->first();
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
        $userId = (int) $request->query('user_id', 0);
        if ($userId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'user_id diperlukan.'], 400);
        }
        $driver = DB::table('drivers')->where('user_id', $userId)->first();
        if (!$driver) {
            return response()->json(['status' => 'error', 'message' => 'Driver tidak ditemukan.'], 404);
        }
        $orders = DB::table('orders')
            ->where('driver_id', $driver->id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $earned = 0.0;
        $pending = 0.0;
        $history = [];
        foreach ($orders as $o) {
            $net = (float) (($o->delivery_fee ?? 0) - ($o->admin_commission_snapshot ?? 0));
            if ($net < 0) $net = 0;
            if (strtoupper((string) ($o->status ?? '')) === 'COMPLETED') {
                $earned += $net;
            } else {
                $pending += $net;
            }
            $history[] = [
                'order_number' => $o->order_number,
                'order_type' => $o->order_type,
                'status' => $o->status,
                'pickup_address' => $o->pickup_address,
                'dropoff_address' => $o->dropoff_address,
                'driver_net' => round($net, 0),
                'created_at' => $o->created_at,
            ];
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
            'role' => 'MERCHANT',
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
                'role' => 'MERCHANT',
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
        $userId = (int) $request->query('user_id', 0);
        if ($userId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'user_id diperlukan.'], 400);
        }
        $merchant = DB::table('merchants')->where('owner_id', $userId)->first();
        if (!$merchant) {
            return response()->json(['status' => 'error', 'message' => 'Toko tidak ditemukan untuk akun ini.'], 404);
        }
        $orders = DB::table('orders')
            ->where('merchant_id', $merchant->id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $earned = 0.0;
        $pending = 0.0;
        $history = [];
        foreach ($orders as $o) {
            $subtotal = (float) ($o->subtotal ?? 0);
            $commission = (float) ($o->merchant_commission_snapshot ?? 0);
            $net = max($subtotal - $commission, 0);
            if (strtoupper((string) ($o->status ?? '')) === 'COMPLETED') {
                $earned += $net;
            } else {
                $pending += $net;
            }
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
            'role' => 'CUSTOMER',
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
    // MODUL WALLET (GridePay) — top up, withdraw, riwayat, rekening, PIN
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

    /** GET /api/wallet/transactions/{id}?user_id=X */
    public function walletTransactionDetail(Request $request, $id)
    {
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
            'description' => 'Top up GridePay via ' . $method,
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
}
