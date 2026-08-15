<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Controller panel /admin/kota — login khusus MANAGER.
 *
 * Fitur:
 * - Dashboard (statistik wilayah & coverage manager)
 * - Wilayah (data provinsi → kota/kabupaten, dropdown)
 * - Coverage (kota tanggung jawab manager; tambah/hapus HANYA oleh ADMIN super)
 * - Members (merchant & driver sesuai coverage kota manager)
 * - Users panel kota (ubah role_kota — HANYA ADMIN super)
 */
class KotaController extends Controller
{
    public static function kotaNav(): array
    {
        return [
            ['label' => 'Dashboard', 'route' => 'kota.dashboard', 'icon' => 'fa-gauge', 'group' => null],
            ['label' => 'Rides', 'route' => 'kota.rides.index', 'icon' => 'fa-car-side', 'group' => 'OPERATIONS', '_new_group' => true],
            ['label' => 'Deliveries', 'route' => 'kota.deliveries.index', 'icon' => 'fa-box', 'group' => 'OPERATIONS'],
            ['label' => 'Orders', 'route' => 'kota.orders.index', 'icon' => 'fa-receipt', 'group' => 'OPERATIONS'],
            ['label' => 'Transactions', 'route' => 'kota.transactions.index', 'icon' => 'fa-money-bill-transfer', 'group' => 'OPERATIONS'],
            ['label' => 'Customers', 'route' => 'kota.customers.index', 'icon' => 'fa-user', 'group' => 'PEOPLE', '_new_group' => true],
            ['label' => 'Drivers', 'route' => 'kota.drivers.index', 'icon' => 'fa-motorcycle', 'group' => 'PEOPLE'],
            ['label' => 'Merchants', 'route' => 'kota.merchants.index', 'icon' => 'fa-store', 'group' => 'PEOPLE'],
            ['label' => 'Payments', 'route' => 'kota.payments.index', 'icon' => 'fa-credit-card', 'group' => 'FINANCE', '_new_group' => true],
            ['label' => 'Wallets', 'route' => 'kota.wallets.index', 'icon' => 'fa-wallet', 'group' => 'FINANCE'],
            ['label' => 'Wallet Trans.', 'route' => 'kota.wallet.transactions.index', 'icon' => 'fa-list-ul', 'group' => 'FINANCE'],
            ['label' => 'Reports', 'route' => 'kota.reports.index', 'icon' => 'fa-chart-column', 'group' => 'REPORTS', '_new_group' => true],
            ['label' => 'Complaints', 'route' => 'kota.complaints.index', 'icon' => 'fa-headset', 'group' => 'SUPPORT', '_new_group' => true],
            ['label' => 'Audit Logs', 'route' => 'kota.audit.index', 'icon' => 'fa-clock-rotate-left', 'group' => 'SYSTEM', '_new_group' => true],
            ['label' => 'My Profile', 'route' => 'kota.profile', 'icon' => 'fa-user-gear', 'group' => 'SYSTEM'],
            ['label' => 'Coverage', 'route' => 'kota.coverage.index', 'icon' => 'fa-map-pin', 'group' => 'AREA', '_new_group' => true],
            ['label' => 'Wilayah', 'route' => 'kota.wilayah.index', 'icon' => 'fa-map-location-dot', 'group' => 'AREA'],
            ['label' => 'Website', 'route' => 'home', 'icon' => 'fa-globe', 'group' => null, 'external' => true],
        ];
    }

    /** Mana user saat ini adalah ADMIN super panel kota. */
    private static function isAdminSuper(): bool
    {
        $user = Auth::user();
        return strtoupper((string) ($user->role_kota ?? 'MEMBER')) === 'ADMIN';
    }

    /** ID kota-kota yang menjadi coverage user saat ini. */
    private static function coverageKotaIds($user): array
    {
        return DB::table('manager_coverage')
            ->where('user_id', $user->id)
            ->pluck('id_kota')
            ->toArray();
    }

    /** Nama kota coverage user saat ini (untuk ditampilkan di view). */
    public function coverageKotaNamesDisplay(): array
    {
        return self::coverageKotaNames(Auth::user());
    }

    /** Nama kota coverage user saat ini (untuk filter merchants.city). */
    private static function coverageKotaNames($user): array
    {
        $ids = self::coverageKotaIds($user);
        if (empty($ids)) {
            return [];
        }
        return DB::table('kota_kabupatens')
            ->whereIn('id', $ids)
            ->pluck('nama')
            ->toArray();
    }

        public function dashboard()
    {
        $user = Auth::user();
        $kotaIds = self::coverageKotaIds($user);
        $cityNames = !empty($kotaIds) ? DB::table('kota_kabupatens')->whereIn('id', $kotaIds)->pluck('nama')->toArray() : [];

        $stats = [
            'provinsi' => DB::table('provinsis')->count(),
            'kota' => DB::table('kota_kabupatens')->count(),
            'coverage_kota' => count($kotaIds),
            'user' => DB::table('users')->count(),
        ];

        // Merchant scoped ke coverage area manager (kolom city_id + city ILIKE).
        $mq = DB::table('merchants');
        \App\Services\ManagerAreaService::applyScope($mq, '', 'merchant');
        $stats['merchant'] = (clone $mq)->count();
        $stats['merchant_active'] = (clone $mq)->where('status', 'ACTIVE')->count();

        // Driver scoped ke operating_city area manager.
        $dq = DB::table('drivers');
        \App\Services\ManagerAreaService::applyScope($dq, '', 'driver');
        $stats['driver'] = (clone $dq)->count();
        $stats['driver_online'] = (clone $dq)->where('status', 'ONLINE')->count();
        $stats['driver_offline'] = (clone $dq)->where('status', 'OFFLINE')->count();
        $stats['driver_verified'] = (clone $dq)->where('is_verified', true)->count();

        // Customer (user MEMBER) scoped ke area.
        $cq = DB::table('users')->where('role', 'MEMBER');
        \App\Services\ManagerAreaService::applyScope($cq, '', 'customer');
        $stats['customer'] = (clone $cq)->count();

        // Orders / rides scoped ke area hari ini + total.
        $oq = DB::table('orders as o');
        \App\Services\ManagerAreaService::scopeOrders($oq, $user);
        $stats['orders_today'] = (clone $oq)->whereDate('o.created_at', now()->format('Y-m-d'))->count();
        $stats['orders_total'] = (clone $oq)->count();
        $stats['orders_completed_today'] = (clone $oq)->whereDate('o.created_at', now()->format('Y-m-d'))->where('o.status', 'COMPLETED')->count();
        $stats['orders_cancelled_today'] = (clone $oq)->whereDate('o.created_at', now()->format('Y-m-d'))->where('o.status', 'CANCELLED')->count();
        $stats['revenue_today'] = (clone $oq)->whereDate('o.created_at', now()->format('Y-m-d'))->where('o.status', 'COMPLETED')->sum('o.total_amount') ?? 0;

        // Pendapatan area manager hari ini (wallet earning driver/merchant di area).
        $ids = (clone $dq)->pluck('user_id')->all();
        $stats['earnings_today'] = 0;
        if (!empty($ids)) {
            $stats['earnings_today'] = DB::table('wallet_transactions')
                ->whereIn('user_id', $ids)
                ->where('is_earning', true)
                ->where('status', 'SUCCESS')
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->sum('amount') ?? 0;
        }

        return view('kota.dashboard', compact('user', 'stats', 'kotaIds', 'cityNames'));
    }

    public function wilayahIndex(Request $request)
    {
        $searchProvinsi = $request->get('provinsi');
        $provinsis = DB::table('provinsis')->orderBy('nama')->get();
        $kota = DB::table('kota_kabupatens')
            ->join('provinsis', 'kota_kabupatens.provinsi_id', '=', 'provinsis.id')
            ->select('kota_kabupatens.*', 'provinsis.nama as provinsi_nama')
            ->when($searchProvinsi, fn ($q) => $q->where('kota_kabupatens.provinsi_id', $searchProvinsi))
            ->orderBy('provinsis.nama')
            ->orderBy('kota_kabupatens.nama')
            ->paginate(50);
        return view('kota.wilayah.index', compact('provinsis', 'kota', 'searchProvinsi'));
    }

    public function wilayahDetail($id)
    {
        $kota = DB::table('kota_kabupatens')
            ->join('provinsis', 'kota_kabupatens.provinsi_id', '=', 'provinsis.id')
            ->select('kota_kabupatens.*', 'provinsis.nama as provinsi_nama')
            ->where('kota_kabupatens.id', $id)
            ->first();
        if (!$kota) {
            abort(404);
        }
        return view('kota.wilayah.detail', compact('kota'));
    }

    // ============================================================
    // Coverage kota
    // ============================================================

    public function coverageIndex()
    {
        $user = Auth::user();
        $isAdmin = self::isAdminSuper();

        // ADMIN super: lihat coverage SEMUA manager
        if ($isAdmin) {
            $coverage = DB::table('manager_coverage')
                ->join('users', 'manager_coverage.user_id', '=', 'users.id')
                ->join('kota_kabupatens', 'manager_coverage.id_kota', '=', 'kota_kabupatens.id')
                ->join('provinsis', 'kota_kabupatens.provinsi_id', '=', 'provinsis.id')
                ->select(
                    'manager_coverage.id as coverage_id',
                    'users.id as user_id',
                    'users.full_name',
                    'users.email',
                    'kota_kabupatens.nama as kota_nama',
                    'provinsis.nama as provinsi_nama',
                    'manager_coverage.created_at'
                )
                ->orderBy('users.full_name')
                ->orderBy('provinsis.nama')
                ->get();
        } else {
            $coverage = DB::table('manager_coverage')
                ->join('kota_kabupatens', 'manager_coverage.id_kota', '=', 'kota_kabupatens.id')
                ->join('provinsis', 'kota_kabupatens.provinsi_id', '=', 'provinsis.id')
                ->where('manager_coverage.user_id', $user->id)
                ->select('manager_coverage.id as coverage_id', 'kota_kabupatens.nama as kota_nama', 'provinsis.nama as provinsi_nama', 'manager_coverage.created_at')
                ->orderBy('provinsis.nama')
                ->orderBy('kota_kabupatens.nama')
                ->get();
        }

        $provinsis = DB::table('provinsis')->orderBy('nama')->get();
        $allKota = DB::table('kota_kabupatens')->select('id', 'nama', 'provinsi_id')->get();
        $managers = DB::table('users')
            ->where('role_kota', 'MANAGER')
            ->select('id', 'full_name', 'email')
            ->orderBy('full_name')
            ->get();
        return view('kota.coverage.index', compact('user', 'isAdmin', 'coverage', 'provinsis', 'allKota', 'managers'));
    }

    /**
     * Tambah kota ke coverage. HANYA ADMIN super.
     */
    public function coverageAdd(Request $request)
    {
        if (!self::isAdminSuper()) {
            abort(403, 'Hanya ADMIN super yang boleh mengelola coverage.');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'id_kota' => ['required', 'exists:kota_kabupatens,id'],
        ]);

        $exists = DB::table('manager_coverage')
            ->where('user_id', $validated['user_id'])
            ->where('id_kota', $validated['id_kota'])
            ->exists();

        if (!$exists) {
            DB::table('manager_coverage')->insert([
                'user_id' => $validated['user_id'],
                'id_kota' => $validated['id_kota'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Coverage kota berhasil ditambahkan.');
    }

    public function coverageRemove($id)
    {
        if (!self::isAdminSuper()) {
            abort(403, 'Hanya ADMIN super yang boleh mengelola coverage.');
        }

        $row = DB::table('manager_coverage')->where('id', $id)->first();
        if (!$row) {
            abort(404);
        }
        DB::table('manager_coverage')->where('id', $id)->delete();

        return back()->with('success', 'Coverage kota berhasil dihapus.');
    }

    // ============================================================
    // Member management sesuai coverage
    // ============================================================

    /**
     * Query merchant scoped ke coverage kota manager.
     */
    private static function scopedMerchants(Request $request)
    {
        $search = $request->query('search');
        $cityNames = self::coverageKotaNames(Auth::user());

        $query = DB::table('merchants')
            ->join('users', 'merchants.owner_id', '=', 'users.id')
            ->select('merchants.*', 'users.full_name as owner_name');

        if (!empty($cityNames)) {
            $query->where(function ($q) use ($cityNames) {
                foreach ($cityNames as $i => $nama) {
                    if ($i === 0) {
                        $q->where('merchants.city', 'ilike', "%{$nama}%");
                    } else {
                        $q->orWhere('merchants.city', 'ilike', "%{$nama}%");
                    }
                }
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('merchants.name', 'ilike', "%{$search}%")
                  ->orWhere('merchants.city', 'ilike', "%{$search}%")
                  ->orWhere('users.full_name', 'ilike', "%{$search}%");
            });
        }

        return $query->orderBy('merchants.created_at', 'desc')->paginate(25)->withQueryString();
    }

    private static function scopedDrivers(Request $request)
    {
        $search = $request->query('search');
        $query = DB::table('drivers')
            ->join('users', 'drivers.user_id', '=', 'users.id')
            ->select('drivers.*', 'users.full_name', 'users.email', 'users.phone');

        // Drivers tidak punya kolom kota; tampilkan semua (dengan catatan).
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.full_name', 'ilike', "%{$search}%")
                  ->orWhere('users.email', 'ilike', "%{$search}%")
                  ->orWhere('drivers.status', 'ilike', "%{$search}%");
            });
        }

        return $query->orderBy('drivers.created_at', 'desc')->paginate(25)->withQueryString();
    }

    public function membersIndex(Request $request)
    {
        $coverageNama = implode(', ', self::coverageKotaNames(Auth::user()));
        $type = $request->query('type', 'merchant');
        $search = $request->query('search');

        if ($type === 'driver') {
            $drivers = self::scopedDrivers($request);
            return view('kota.members.index', compact('type', 'search', 'drivers', 'coverageNama'));
        }

        $merchants = self::scopedMerchants($request);
        return view('kota.members.index', compact('type', 'search', 'merchants', 'coverageNama'));
    }

    public function membersMerchantEdit($id)
    {
        $merchant = DB::table('merchants')->where('id', $id)->first();
        abort_if(!$merchant, 404, 'Merchant tidak ditemukan');

        // Pastikan merchant masuk scope coverage manager
        $cityNames = self::coverageKotaNames(Auth::user());
        if (!empty($cityNames)) {
            $match = false;
            foreach ($cityNames as $nama) {
                if (stripos($merchant->city ?? '', $nama) !== false) {
                    $match = true;
                    break;
                }
            }
            abort_if(!$match, 403, 'Merchant ini di luar coverage kota Anda.');
        }

        $owners = DB::table('users')->whereIn('role', ['MEMBER'])->get();
        return view('kota.members.merchant-edit', compact('merchant', 'owners'));
    }

    public function membersMerchantUpdate(Request $request, $id)
    {
        $merchant = DB::table('merchants')->where('id', $id)->first();
        abort_if(!$merchant, 404, 'Merchant tidak ditemukan');

        $cityNames = self::coverageKotaNames(Auth::user());
        if (!empty($cityNames)) {
            $match = false;
            foreach ($cityNames as $nama) {
                if (stripos($merchant->city ?? '', $nama) !== false) {
                    $match = true;
                    break;
                }
            }
            abort_if(!$match, 403, 'Merchant ini di luar coverage kota Anda.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_id' => 'required|exists:users,id',
            'type' => 'required|string|in:FOOD,MART,SHOP',
            'address_line' => 'required|string',
            'city' => 'required|string',
            'phone' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:ACTIVE,INACTIVE,SUSPENDED',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        DB::table('merchants')->where('id', $id)->update([
            'owner_id' => $validated['owner_id'],
            'type' => $validated['type'],
            'name' => $validated['name'],
            'slug' => \Str::slug($validated['name']) . '-' . rand(100, 999),
            'description' => $validated['description'] ?? '',
            'address_line' => $validated['address_line'],
            'city' => $validated['city'],
            'phone' => $validated['phone'] ?? '',
            'status' => $validated['status'] ?? 'ACTIVE',
            'latitude' => $validated['latitude'] ?? $merchant->latitude,
            'longitude' => $validated['longitude'] ?? $merchant->longitude,
            'updated_at' => now(),
        ]);

        return redirect()->route('kota.members.index', ['type' => 'merchant'])->with('success', 'Merchant berhasil diperbarui.');
    }

    public function membersDriverEdit($id)
    {
        $driver = DB::table('drivers')
            ->join('users', 'drivers.user_id', '=', 'users.id')
            ->select('drivers.*', 'users.full_name', 'users.email', 'users.phone')
            ->where('drivers.id', $id)
            ->first();
        abort_if(!$driver, 404, 'Driver tidak ditemukan');
        return view('kota.members.driver-edit', compact('driver'));
    }

    public function membersDriverUpdate(Request $request, $id)
    {
        $driver = DB::table('drivers')->where('id', $id)->first();
        abort_if(!$driver, 404, 'Driver tidak ditemukan');

        $validated = $request->validate([
            'rating' => 'nullable|numeric|min:0|max:5',
            'is_verified' => 'nullable',
            'current_lat' => 'nullable|numeric|between:-90,90',
            'current_lng' => 'nullable|numeric|between:-180,180',
        ]);

        DB::table('drivers')->where('id', $id)->update([
            'rating' => $validated['rating'] ?? $driver->rating,
            'is_verified' => $request->has('is_verified'),
            'current_lat' => $validated['current_lat'] ?? $driver->current_lat,
            'current_lng' => $validated['current_lng'] ?? $driver->current_lng,
            'last_location_at' => isset($validated['current_lat']) ? now() : $driver->last_location_at,
            'updated_at' => now(),
        ]);

        return redirect()->route('kota.members.index', ['type' => 'driver'])->with('success', 'Driver berhasil diperbarui.');
    }

    public function membersDriverStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:ONLINE,OFFLINE',
        ]);

        DB::table('drivers')->where('id', $id)->update(['status' => $validated['status'], 'updated_at' => now()]);
        return back()->with('success', 'Status driver berhasil diperbarui.');
    }

    // ============================================================
    // Pengguna panel kota (hanya ADMIN super)
    // ============================================================

    public function usersIndex(Request $request)
    {
        $search = $request->get('search');
        $query = DB::table('users')
            ->select('id', 'email', 'full_name', 'phone', 'role', 'role_kota', 'status', 'created_at');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'ilike', "%{$search}%")
                  ->orWhere('full_name', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }
        $users = $query->orderBy('id', 'desc')->paginate(25);
        return view('kota.users.index', compact('users', 'search'));
    }

    /**
     * Ubah role_kota pengguna — HANYA ADMIN super.
     */
    public function usersRoleUpdate(Request $request, $id)
    {
        if (!self::isAdminSuper()) {
            abort(403, 'Hanya ADMIN super yang boleh mengubah role kota.');
        }

        $request->validate(['role_kota' => ['required', 'in:ADMIN,MANAGER,MEMBER']]);
        DB::table('users')->where('id', $id)->update(['role_kota' => strtoupper($request->role_kota)]);
        return back()->with('success', 'Role kota pengguna berhasil diperbarui.');
    }

    // ============================================================
    // OPERATIONS: Rides / Deliveries / Orders / Transactions
    // Semua query di-scope area via ManagerAreaService; 403 di luar area.
    // ============================================================

    private static function orderBaseQuery(string $type = null)
    {
        $query = DB::table('orders as o')
            ->leftJoin('users as cu', 'o.user_id', '=', 'cu.id')
            ->leftJoin('merchants as m', 'o.merchant_id', '=', 'm.id')
            ->leftJoin('drivers as d', 'o.driver_id', '=', 'd.id')
            ->leftJoin('users as du', 'd.user_id', '=', 'du.id')
            ->select(
                'o.*',
                'cu.full_name as customer_name',
                'm.name as merchant_name',
                'du.full_name as driver_name'
            );
        if ($type !== null) {
            $query->where('o.order_type', $type);
        }
        return $query;
    }

    public function ridesIndex(Request $request)
    {
        \App\Services\ManagerAreaService::scopeOrders($q = self::orderBaseQuery('RIDE'), Auth::user());
        $search = $request->query('search');
        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('o.order_number', 'ilike', "%{$search}%")
                   ->orWhere('cu.full_name', 'ilike', "%{$search}%")
                   ->orWhere('du.full_name', 'ilike', "%{$search}%");
            });
        }
        $rides = $q->orderBy('o.created_at', 'desc')->paginate(25)->withQueryString();
        return view('kota.rides.index', compact('rides', 'search'));
    }

    public function ridesShow($id)
    {
        \App\Services\ManagerAreaService::requireInArea('order', $id);
        $ride = DB::table('orders')->where('id', $id)->where('order_type', 'RIDE')->first();
        abort_if(!$ride, 404, 'Ride tidak ditemukan');
        $customer = DB::table('users')->where('id', $ride->user_id)->first();
        $driver = $ride->driver_id ? DB::table('users')->where('id', function ($q) use ($ride) { $q->from('drivers')->select('user_id')->where('id', $ride->driver_id); })->first() : null;
        return view('kota.rides.show', compact('ride', 'customer', 'driver'));
    }

    public function deliveriesIndex(Request $request)
    {
        $q = self::orderBaseQuery('DELIVERY');
        \App\Services\ManagerAreaService::scopeOrders($q, Auth::user());
        $search = $request->query('search');
        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('o.order_number', 'ilike', "%{$search}%")
                   ->orWhere('cu.full_name', 'ilike', "%{$search}%")
                   ->orWhere('du.full_name', 'ilike', "%{$search}%");
            });
        }
        $deliveries = $q->orderBy('o.created_at', 'desc')->paginate(25)->withQueryString();
        return view('kota.deliveries.index', compact('deliveries', 'search'));
    }

    public function deliveriesShow($id)
    {
        \App\Services\ManagerAreaService::requireInArea('order', $id);
        $delivery = DB::table('orders')->where('id', $id)->where('order_type', 'DELIVERY')->first();
        abort_if(!$delivery, 404, 'Delivery tidak ditemukan');
        $customer = DB::table('users')->where('id', $delivery->user_id)->first();
        $driver = $delivery->driver_id ? DB::table('users')->where('id', function ($q) use ($delivery) { $q->from('drivers')->select('user_id')->where('id', $delivery->driver_id); })->first() : null;
        return view('kota.deliveries.show', compact('delivery', 'customer', 'driver'));
    }

    public function ordersIndex(Request $request)
    {
        $q = self::orderBaseQuery();
        \App\Services\ManagerAreaService::scopeOrders($q, Auth::user());
        $search = $request->query('search');
        $status = $request->query('status');
        $type = $request->query('type');
        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('o.order_number', 'ilike', "%{$search}%")
                   ->orWhere('cu.full_name', 'ilike', "%{$search}%")
                   ->orWhere('du.full_name', 'ilike', "%{$search}%");
            });
        }
        if ($status) { $q->where('o.status', $status); }
        if ($type) { $q->where('o.order_type', $type); }
        $orders = $q->orderBy('o.created_at', 'desc')->paginate(25)->withQueryString();
        return view('kota.orders.index', compact('orders', 'search', 'status', 'type'));
    }

    public function ordersShow($id)
    {
        \App\Services\ManagerAreaService::requireInArea('order', $id);
        $order = DB::table('orders')->where('id', $id)->first();
        abort_if(!$order, 404, 'Order tidak ditemukan');
        $customer = DB::table('users')->where('id', $order->user_id)->first();
        $merchant = $order->merchant_id ? DB::table('merchants')->where('id', $order->merchant_id)->first() : null;
        $driver = $order->driver_id ? DB::table('users')->where('id', function ($q) use ($order) { $q->from('drivers')->select('user_id')->where('id', $order->driver_id); })->first() : null;
        return view('kota.orders.show', compact('order', 'customer', 'merchant', 'driver'));
    }

    public function transactionsIndex(Request $request)
    {
        $q = DB::table('orders as o')
            ->leftJoin('users as cu', 'o.user_id', '=', 'cu.id')
            ->leftJoin('merchants as m', 'o.merchant_id', '=', 'm.id')
            ->select('o.*', 'cu.full_name as customer_name', 'm.name as merchant_name');
        \App\Services\ManagerAreaService::scopeOrders($q, Auth::user());
        $search = $request->query('search');
        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('o.order_number', 'ilike', "%{$search}%")
                   ->orWhere('o.payment_status', 'ilike', "%{$search}%");
            });
        }
        $transactions = $q->orderBy('o.created_at', 'desc')->paginate(25)->withQueryString();
        return view('kota.transactions.index', compact('transactions', 'search'));
    }
// ============================================================
    // PEOPLE: Customers
    // ============================================================

    public function customersIndex(Request $request)
    {
        $q = DB::table('users')
            ->where('role', 'MEMBER')
            ->select('id', 'full_name', 'email', 'phone', 'status', 'home_city_id', 'created_at');
        \App\Services\ManagerAreaService::applyScope($q, '', 'customer');
        $search = $request->query('search');
        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('full_name', 'ilike', "%{$search}%")
                   ->orWhere('email', 'ilike', "%{$search}%")
                   ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }
        $customers = $q->orderBy('id', 'desc')->paginate(25)->withQueryString();
        return view('kota.customers.index', compact('customers', 'search'));
    }

    public function customersShow($id)
    {
        \App\Services\ManagerAreaService::requireInArea('customer', $id);
        $customer = DB::table('users')->where('id', $id)->first();
        abort_if(!$customer, 404, 'Customer tidak ditemukan');
        $orders = DB::table('orders')->where('user_id', $id)->orderBy('created_at', 'desc')->limit(10)->get();
        $wallet = DB::table('wallets')->where('user_id', $id)->first();
        $addresses = collect();
        $walletSummary = $wallet ? DB::table('wallet_transactions')
            ->where('wallet_id', $wallet->id)
            ->selectRaw("count(*) as total_transactions, coalesce(sum(amount) filter (where direction='CREDIT'),0) as total_in, coalesce(sum(amount) filter (where direction='DEBIT'),0) as total_out, coalesce(sum(amount) filter (where is_earning=true),0) as total_earning")
            ->first() : (object) ['total_transactions' => 0, 'total_in' => 0, 'total_out' => 0, 'total_earning' => 0];
        return view('kota.customers.show', compact('customer', 'orders', 'wallet', 'addresses', 'walletSummary'));
    }

    public function customersEdit($id)
    {
        \App\Services\ManagerAreaService::requireInArea('customer', $id);
        $customer = DB::table('users')->where('id', $id)->first();
        abort_if(!$customer, 404, 'Customer tidak ditemukan');
        return view('kota.customers.edit', compact('customer'));
    }

    public function customersUpdate(Request $request, $id)
    {
        \App\Services\ManagerAreaService::requireInArea('customer', $id);
        $customer = DB::table('users')->where('id', $id)->first();
        abort_if(!$customer, 404, 'Customer tidak ditemukan');

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|string|in:ACTIVE,INACTIVE,SUSPENDED,BANNED',
        ]);

        $before = ['full_name' => $customer->full_name, 'phone' => $customer->phone, 'status' => $customer->status];
        DB::table('users')->where('id', $id)->update(array_merge($validated, ['updated_at' => now()]));
        \App\Services\ManagerAreaService::audit('customer.update', 'customer', $id, $before, $validated);
        return redirect()->route('kota.customers.index')->with('success', 'Customer berhasil diperbarui.');
    }

    // ============================================================
    // PEOPLE: Drivers
    // ============================================================

    public function driversIndex(Request $request)
    {
        $q = DB::table('drivers as d')
            ->join('users as u', 'd.user_id', '=', 'u.id')
            ->leftJoin('kota_kabupatens as k', 'd.operating_city_id', '=', 'k.id')
            ->select('d.*', 'u.full_name', 'u.email', 'u.phone', 'k.nama as kota_nama');
        \App\Services\ManagerAreaService::applyScope($q, 'd', 'driver');
        $search = $request->query('search');
        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('u.full_name', 'ilike', "%{$search}%")
                   ->orWhere('u.email', 'ilike', "%{$search}%")
                   ->orWhere('d.status', 'ilike', "%{$search}%");
            });
        }
        $drivers = $q->orderBy('d.created_at', 'desc')->paginate(25)->withQueryString();
        return view('kota.drivers.index', compact('drivers', 'search'));
    }

    public function driversShow($id)
    {
        \App\Services\ManagerAreaService::requireInArea('driver', $id);
        $driver = DB::table('drivers')->where('id', $id)->first();
        abort_if(!$driver, 404, 'Driver tidak ditemukan');
        $user = DB::table('users')->where('id', $driver->user_id)->first();
        $vehicle = DB::table('driver_vehicles')->where('driver_id', $id)->first();
        $wallet = DB::table('wallets')->where('user_id', $driver->user_id)->first();
        $earningSummary = DB::table('wallet_transactions')
            ->where('user_id', $driver->user_id)
            ->where('is_earning', true)
            ->selectRaw("count(*) as total_transactions, coalesce(sum(amount) filter (where status='SUCCESS'),0) as total_earning")
            ->first();
        $recentTrips = DB::table('orders')
            ->where('driver_id', $id)
            ->whereIn('order_type', ['RIDE', 'DELIVERY'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        return view('kota.drivers.show', compact('driver', 'user', 'vehicle', 'wallet', 'earningSummary', 'recentTrips'));
    }

    public function driversEdit($id)
    {
        \App\Services\ManagerAreaService::requireInArea('driver', $id);
        $driver = DB::table('drivers')->where('id', $id)->first();
        abort_if(!$driver, 404, 'Driver tidak ditemukan');
        $user = DB::table('users')->where('id', $driver->user_id)->first();
        $vehicles = DB::table('driver_vehicles')->where('driver_id', $id)->get();
        $allKota = DB::table('kota_kabupatens')->orderBy('nama')->get();
        return view('kota.drivers.edit', compact('driver', 'user', 'vehicles', 'allKota'));
    }

    public function driversUpdate(Request $request, $id)
    {
        \App\Services\ManagerAreaService::requireInArea('driver', $id);
        $driver = DB::table('drivers')->where('id', $id)->first();
        abort_if(!$driver, 404, 'Driver tidak ditemukan');

        $validated = $request->validate([
            'rating' => 'nullable|numeric|min:0|max:5',
            'is_verified' => 'nullable',
            'status' => 'nullable|string|in:ONLINE,OFFLINE',
            'operating_city_id' => 'nullable|exists:kota_kabupatens,id',
            'current_lat' => 'nullable|numeric|between:-90,90',
            'current_lng' => 'nullable|numeric|between:-180,180',
        ]);

        $before = ['rating' => $driver->rating, 'is_verified' => $driver->is_verified, 'operating_city_id' => $driver->operating_city_id, 'status' => $driver->status];
        DB::table('drivers')->where('id', $id)->update([
            'rating' => $validated['rating'] ?? $driver->rating,
            'is_verified' => $request->has('is_verified'),
            'status' => $validated['status'] ?? $driver->status,
            'operating_city_id' => $validated['operating_city_id'] ?? $driver->operating_city_id,
            'current_lat' => $validated['current_lat'] ?? $driver->current_lat,
            'current_lng' => $validated['current_lng'] ?? $driver->current_lng,
            'last_location_at' => isset($validated['current_lat']) ? now() : $driver->last_location_at,
            'updated_at' => now(),
        ]);
        $after = ['rating' => $validated['rating'] ?? $driver->rating, 'is_verified' => $request->has('is_verified'), 'operating_city_id' => $validated['operating_city_id'] ?? $driver->operating_city_id, 'status' => $validated['status'] ?? $driver->status];
        \App\Services\ManagerAreaService::audit('driver.update', 'driver', $id, $before, $after);
        return redirect()->route('kota.drivers.index')->with('success', 'Driver berhasil diperbarui.');
    }

    public function driversStatus(Request $request, $id)
    {
        \App\Services\ManagerAreaService::requireInArea('driver', $id);
        $validated = $request->validate(['status' => 'required|string|in:ONLINE,OFFLINE']);
        DB::table('drivers')->where('id', $id)->update(['status' => $validated['status'], 'updated_at' => now()]);
        \App\Services\ManagerAreaService::audit('driver.status', 'driver', $id, null, $validated);
        return back()->with('success', 'Status driver berhasil diperbarui.');
    }

    public function driversWallet($id)
    {
        \App\Services\ManagerAreaService::requireInArea('driver', $id);
        $driver = DB::table('drivers')->where('id', $id)->first();
        abort_if(!$driver, 404, 'Driver tidak ditemukan');
        $wallet = DB::table('wallets')->where('user_id', $driver->user_id)->first();
        abort_if(!$wallet, 404, 'Wallet driver tidak ditemukan');
        $transactions = DB::table('wallet_transactions')
            ->where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50)->withQueryString();
        return view('kota.drivers.wallet', compact('driver', 'wallet', 'transactions'));
    }

    public function driversTrips($id)
    {
        \App\Services\ManagerAreaService::requireInArea('driver', $id);
        $driver = DB::table('drivers')->where('id', $id)->first();
        abort_if(!$driver, 404, 'Driver tidak ditemukan');
        $trips = DB::table('orders')
            ->where('driver_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(50)->withQueryString();
        return view('kota.drivers.trips', compact('driver', 'trips'));
    }

    public function driversVehicles($id)
    {
        \App\Services\ManagerAreaService::requireInArea('driver', $id);
        $driver = DB::table('drivers')->where('id', $id)->first();
        abort_if(!$driver, 404, 'Driver tidak ditemukan');
        $vehicles = DB::table('driver_vehicles')->where('driver_id', $id)->get();
        return view('kota.drivers.vehicles', compact('driver', 'vehicles'));
    }

    public function driversVehiclesStore(Request $request, $id)
    {
        \App\Services\ManagerAreaService::requireInArea('driver', $id);
        $validated = $request->validate([
            'vehicle_type' => 'required|string',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'year_kendaraan' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:50',
            'plate_number' => 'nullable|string|max:20',
            'capacity' => 'nullable|integer|min:1|max:20',
        ]);
        DB::table('driver_vehicles')->insert(array_merge($validated, [
            'driver_id' => $id,
            'is_active' => true,
            'status_verifikasi' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        \App\Services\ManagerAreaService::audit('driver.vehicle.add', 'driver', $id, null, $validated);
        return back()->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function driversVehiclesDestroy($id, $vehicleId)
    {
        \App\Services\ManagerAreaService::requireInArea('driver', $id);
        DB::table('driver_vehicles')->where('id', $vehicleId)->where('driver_id', $id)->delete();
        \App\Services\ManagerAreaService::audit('driver.vehicle.remove', 'driver', $id, null, ['vehicle_id' => $vehicleId]);
        return back()->with('success', 'Kendaraan berhasil dihapus.');
    }

    // ============================================================
    // PEOPLE: Merchants
    // ============================================================

    public function merchantsIndex(Request $request)
    {
        $q = DB::table('merchants as m')
            ->leftJoin('users as u', 'm.owner_id', '=', 'u.id')
            ->leftJoin('kota_kabupatens as k', 'm.city_id', '=', 'k.id')
            ->select('m.*', 'u.full_name as owner_name', 'k.nama as kota_nama');
        \App\Services\ManagerAreaService::applyScope($q, 'm', 'merchant');
        $search = $request->query('search');
        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('m.name', 'ilike', "%{$search}%")
                   ->orWhere('u.full_name', 'ilike', "%{$search}%");
            });
        }
        $merchants = $q->orderBy('m.created_at', 'desc')->paginate(25)->withQueryString();
        return view('kota.merchants.index', compact('merchants', 'search'));
    }

    public function merchantsShow($id)
    {
        \App\Services\ManagerAreaService::requireInArea('merchant', $id);
        $merchant = DB::table('merchants')->where('id', $id)->first();
        abort_if(!$merchant, 404, 'Merchant tidak ditemukan');
        $owner = DB::table('users')->where('id', $merchant->owner_id)->first();
        $recentOrders = DB::table('orders')
            ->where('merchant_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        $wallet = DB::table('wallets')->where('user_id', $merchant->owner_id)->first();
        $earningSummary = DB::table('wallet_transactions')
            ->where('user_id', $merchant->owner_id)
            ->where('is_earning', true)
            ->selectRaw("count(*) as total_transactions, coalesce(sum(amount) filter (where status='SUCCESS'),0) as total_earning")
            ->first();
        return view('kota.merchants.show', compact('merchant', 'owner', 'recentOrders', 'wallet', 'earningSummary'));
    }

    public function merchantsEdit($id)
    {
        \App\Services\ManagerAreaService::requireInArea('merchant', $id);
        $merchant = DB::table('merchants')->where('id', $id)->first();
        abort_if(!$merchant, 404, 'Merchant tidak ditemukan');
        $owners = DB::table('users')->whereIn('role', ['MEMBER', 'ADMIN'])->get();
        $allKota = DB::table('kota_kabupatens')->orderBy('nama')->get();
        return view('kota.merchants.edit', compact('merchant', 'owners', 'allKota'));
    }

    public function merchantsUpdate(Request $request, $id)
    {
        \App\Services\ManagerAreaService::requireInArea('merchant', $id);
        $merchant = DB::table('merchants')->where('id', $id)->first();
        abort_if(!$merchant, 404, 'Merchant tidak ditemukan');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_id' => 'required|exists:users,id',
            'type' => 'required|string|in:FOOD,MART,SHOP',
            'address_line' => 'required|string',
            'city' => 'required|string',
            'city_id' => 'nullable|exists:kota_kabupatens,id',
            'phone' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:ACTIVE,INACTIVE,SUSPENDED,CLOSED',
            'is_open' => 'nullable',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $before = ['name' => $merchant->name, 'status' => $merchant->status, 'city_id' => $merchant->city_id, 'is_open' => $merchant->is_open];
        $update = [
            'owner_id' => $validated['owner_id'],
            'type' => $validated['type'],
            'name' => $validated['name'],
            'slug' => \Str::slug($validated['name']) . '-' . rand(100, 999),
            'description' => $validated['description'] ?? '',
            'address_line' => $validated['address_line'],
            'city' => $validated['city'],
            'city_id' => $validated['city_id'] ?? $merchant->city_id,
            'phone' => $validated['phone'] ?? '',
            'status' => $validated['status'] ?? 'ACTIVE',
            'is_open' => $request->has('is_open'),
            'latitude' => $validated['latitude'] ?? $merchant->latitude,
            'longitude' => $validated['longitude'] ?? $merchant->longitude,
            'updated_at' => now(),
        ];
        DB::table('merchants')->where('id', $id)->update($update);
        \App\Services\ManagerAreaService::audit('merchant.update', 'merchant', $id, $before, $update);
        return redirect()->route('kota.merchants.index')->with('success', 'Merchant berhasil diperbarui.');
    }

    public function merchantsStatus(Request $request, $id)
    {
        \App\Services\ManagerAreaService::requireInArea('merchant', $id);
        $validated = $request->validate(['status' => 'required|string|in:ACTIVE,INACTIVE,SUSPENDED,CLOSED']);
        DB::table('merchants')->where('id', $id)->update(['status' => $validated['status'], 'updated_at' => now()]);
        \App\Services\ManagerAreaService::audit('merchant.status', 'merchant', $id, null, $validated);
        return back()->with('success', 'Status merchant berhasil diperbarui.');
    }

    public function merchantsWallet($id)
    {
        \App\Services\ManagerAreaService::requireInArea('merchant', $id);
        $merchant = DB::table('merchants')->where('id', $id)->first();
        abort_if(!$merchant, 404, 'Merchant tidak ditemukan');
        $wallet = DB::table('wallets')->where('user_id', $merchant->owner_id)->first();
        abort_if(!$wallet, 404, 'Wallet merchant tidak ditemukan');
        $transactions = DB::table('wallet_transactions')
            ->where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50)->withQueryString();
        return view('kota.merchants.wallet', compact('merchant', 'wallet', 'transactions'));
    }

    // ============================================================
    // FINANCE: Payments / Wallets / Wallet Transactions (VIEW ONLY)
    // ============================================================

    public function paymentsIndex(Request $request)
    {
        $q = DB::table('orders as o')
            ->leftJoin('users as cu', 'o.user_id', '=', 'cu.id')
            ->select('o.*', 'cu.full_name as customer_name');
        \App\Services\ManagerAreaService::scopeOrders($q, Auth::user());
        $status = $request->query('payment_status');
        if ($status) { $q->where('o.payment_status', $status); }
        $payments = $q->orderBy('o.created_at', 'desc')->paginate(25)->withQueryString();
        return view('kota.payments.index', compact('payments', 'status'));
    }

    public function paymentsShow($id)
    {
        \App\Services\ManagerAreaService::requireInArea('order', $id);
        $order = DB::table('orders')->where('id', $id)->first();
        abort_if(!$order, 404, 'Transaksi tidak ditemukan');
        $customer = DB::table('users')->where('id', $order->user_id)->first();
        return view('kota.payments.show', compact('order', 'customer'));
    }

    public function walletsIndex(Request $request)
    {
        $q = DB::table('wallets as w')
            ->join('users as u', 'w.user_id', '=', 'u.id')
            ->leftJoin('drivers as d', 'u.id', '=', 'd.user_id')
            ->select('w.*', 'u.full_name', 'u.email', 'u.role', 'd.status as driver_status', 'd.operating_city_id');
        \App\Services\ManagerAreaService::applyScope($q, 'u', 'customer');
        $search = $request->query('search');
        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('u.full_name', 'ilike', "%{$search}%")
                   ->orWhere('u.email', 'ilike', "%{$search}%");
            });
        }
        $wallets = $q->orderBy('w.created_at', 'desc')->paginate(25)->withQueryString();
        return view('kota.wallets.index', compact('wallets', 'search'));
    }

    public function walletsShow($id)
    {
        $wallet = DB::table('wallets')->where('id', $id)->first();
        abort_if(!$wallet, 404, 'Wallet tidak ditemukan');
        \App\Services\ManagerAreaService::requireInArea('customer', $wallet->user_id);
        $user = DB::table('users')->where('id', $wallet->user_id)->first();
        $transactions = DB::table('wallet_transactions')
            ->where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50)->withQueryString();
        return view('kota.wallets.show', compact('wallet', 'user', 'transactions'));
    }

    public function walletTransactionsIndex(Request $request)
    {
        $q = DB::table('wallet_transactions as wt')
            ->join('users as u', 'wt.user_id', '=', 'u.id')
            ->select('wt.*', 'u.full_name');
        \App\Services\ManagerAreaService::applyScope($q, 'u', 'customer');
        $type = $request->query('type');
        $direction = $request->query('direction');
        $search = $request->query('search');
        if ($type) { $q->where('wt.type', $type); }
        if ($direction) { $q->where('wt.direction', $direction); }
        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('wt.description', 'ilike', "%{$search}%")
                   ->orWhere('wt.reference_no', 'ilike', "%{$search}%")
                   ->orWhere('u.full_name', 'ilike', "%{$search}%");
            });
        }
        $transactions = $q->orderBy('wt.created_at', 'desc')->paginate(50)->withQueryString();
        return view('kota.wallet-transactions.index', compact('transactions', 'type', 'direction', 'search'));
    }

    // ============================================================
    // REPORTS
    // ============================================================

    public function reportsIndex(Request $request)
    {
        $from = $request->query('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->query('to', now()->format('Y-m-d'));
        $q = DB::table('orders as o');
        \App\Services\ManagerAreaService::scopeOrders($q, Auth::user());
        $q->whereBetween('o.created_at', ["{$from} 00:00:00", "{$to} 23:59:59"]);

        $stats = [
            'orders' => $q->count(),
            'completed' => (clone $q)->where('o.status', 'COMPLETED')->count(),
            'cancelled' => (clone $q)->where('o.status', 'CANCELLED')->count(),
            'revenue' => (clone $q)->where('o.status', 'COMPLETED')->sum('o.total_amount') ?? 0,
        ];
        $byType = (clone $q)->selectRaw("o.order_type, count(*) as total, coalesce(sum(total_amount) filter (where status='COMPLETED'),0) as revenue")
            ->groupBy('o.order_type')->get();
        $daily = (clone $q)->selectRaw("date_trunc('day', o.created_at) as day, count(*) as total")
            ->groupByRaw("date_trunc('day', o.created_at)")
            ->orderBy('day')
            ->limit(31)
            ->get();
        return view('kota.reports.index', compact('stats', 'byType', 'daily', 'from', 'to'));
    }

    public function reportsDaily(Request $request)
    {
        $from = $request->query('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->query('to', now()->format('Y-m-d'));
        $q = DB::table('orders as o');
        \App\Services\ManagerAreaService::scopeOrders($q, Auth::user());
        $q->whereBetween('o.created_at', ["{$from} 00:00:00", "{$to} 23:59:59"]);
        $daily = $q->selectRaw("to_char(date_trunc('day', o.created_at), 'YYYY-MM-DD') as day, count(*) as total, coalesce(sum(total_amount) filter (where status='COMPLETED'),0) as revenue")
            ->groupByRaw("date_trunc('day', o.created_at)")
            ->orderByRaw('day')
            ->get();
        return view('kota.reports.daily', compact('daily', 'from', 'to'));
    }

    // ============================================================
    // SUPPORT: Complaints
    // ============================================================

    public function complaintsIndex(Request $request)
    {
        $q = DB::table('complaints as c')
            ->leftJoin('users as r', 'c.reporter_id', '=', 'r.id')
            ->leftJoin('users as a', 'c.assigned_user_id', '=', 'a.id')
            ->select('c.*', 'r.full_name as reporter_name', 'a.full_name as assigned_name');
        $status = $request->query('status');
        if ($status) { $q->where('c.status', $status); }
        $search = $request->query('search');
        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('c.subject', 'ilike', "%{$search}%")
                   ->orWhere('r.full_name', 'ilike', "%{$search}%");
            });
        }
        $complaints = $q->orderBy('c.created_at', 'desc')->paginate(25)->withQueryString();
        return view('kota.complaints.index', compact('complaints', 'status', 'search'));
    }

    public function complaintsStore(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'category' => 'nullable|string|in:delivery_late,driver_behavior,merchant_issue,billing,other',
            'target_type' => 'nullable|string|in:customer,driver,merchant,order',
        ]);
        DB::table('complaints')->insert(array_merge($validated, [
            'reporter_id' => Auth::id(),
            'status' => 'OPEN',
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        return back()->with('success', 'Keluhan berhasil dilaporkan.');
    }

    public function complaintsStatus(Request $request, $id)
    {
        $validated = $request->validate(['status' => 'required|string|in:OPEN,IN_PROGRESS,RESOLVED,CLOSED']);
        DB::table('complaints')->where('id', $id)->update(['status' => $validated['status'], 'updated_at' => now()]);
        \App\Services\ManagerAreaService::audit('complaint.status', 'complaint', $id, null, $validated);
        return back()->with('success', 'Status keluhan berhasil diperbarui.');
    }

    // ============================================================
    // SYSTEM: Audit Logs
    // ============================================================

    public function auditIndex(Request $request)
    {
        $q = DB::table('audit_logs as a')
            ->leftJoin('users as u', 'a.user_id', '=', 'u.id')
            ->select('a.*', 'u.full_name as user_name');
        $search = $request->query('search');
        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('a.action', 'ilike', "%{$search}%")
                   ->orWhere('a.entity_type', 'ilike', "%{$search}%")
                   ->orWhere('u.full_name', 'ilike', "%{$search}%");
            });
        }
        $logs = $q->orderBy('a.created_at', 'desc')->paginate(50)->withQueryString();
        return view('kota.audit.index', compact('logs', 'search'));
    }

    // ============================================================
    // SYSTEM: My Profile
    // ============================================================

    public function profile()
    {
        $user = Auth::user();
        $coverage = DB::table('manager_coverage')
            ->join('kota_kabupatens', 'manager_coverage.id_kota', '=', 'kota_kabupatens.id')
            ->join('provinsis', 'kota_kabupatens.provinsi_id', '=', 'provinsis.id')
            ->where('manager_coverage.user_id', $user->id)
            ->select('kota_kabupatens.nama as kota_nama', 'provinsis.nama as provinsi_nama')
            ->get();
        return view('kota.profile.index', compact('user', 'coverage'));
    }

    public function profileUpdate(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);
        $before = ['full_name' => Auth::user()->full_name, 'phone' => Auth::user()->phone];
        DB::table('users')->where('id', Auth::id())->update(array_merge($validated, ['updated_at' => now()]));
        \App\Services\ManagerAreaService::audit('profile.update', 'customer', Auth::id(), $before, $validated);
        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function logout(Request $request)
    {
        return redirect()->route('kota.logout');
    }
}
