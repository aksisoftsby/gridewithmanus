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
        $status = $request->query('status');
        $query = DB::table('orders');
        if ($driverId) {
            $query->where('driver_id', $driverId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
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

        $order = DB::table('orders')->where('id', $id)->first();
        $order->invoice = $this->buildInvoiceBreakdown($order);
        return response()->json([
            'status' => 'success',
            'data' => $order
        ], 201);
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
}
