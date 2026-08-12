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
        $status = $request->query('status');
        $query = DB::table('orders');

        if ($driverId) {
            $query->where('driver_id', $driverId);
        }
        if ($status) {
            $query->where('status', $status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('created_at', 'desc')->limit(100)->get()
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
     * Create a new order. Supports DELIVERY (GPS antar-jemput) orders.
     * POST /api/orders { order_type, user_id, pickup_address, pickup_lat, pickup_lng,
     *  dropoff_address, dropoff_lat, dropoff_lng, delivery_fee, note? }
     */
    public function ordersStore(Request $request)
    {
        $validated = $request->validate([
            'order_type' => 'required|string|in:FOOD,MART,SHOP,DELIVERY',
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
        ]);

        $orderType = $validated['order_type'];
        $subtotal = 0;
        $deliveryFee = $validated['delivery_fee'] ?? 10000;

        // For DELIVERY orders, compute distance-based fee if GPS provided
        if ($orderType === 'DELIVERY' && isset($validated['pickup_lat'], $validated['dropoff_lat'])) {
            $km = $this->haversineKm(
                (float) $validated['pickup_lat'], (float) $validated['pickup_lng'],
                (float) $validated['dropoff_lat'], (float) $validated['dropoff_lng']
            );
            $deliveryFee = round(10000 + ($km * 2500), -2); // Rp 10.000 base + Rp 2.500/km
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
            'total_amount' => $subtotal + $deliveryFee,
            'note' => $validated['note'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => DB::table('orders')->where('id', $id)->first()
        ], 201);
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
