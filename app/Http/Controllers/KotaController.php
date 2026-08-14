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
            ['label' => 'Dashboard', 'route' => 'kota.dashboard', 'icon' => 'fa-gauge'],
            ['label' => 'Members', 'route' => 'kota.members.index', 'icon' => 'fa-users'],
            ['label' => 'Coverage', 'route' => 'kota.coverage.index', 'icon' => 'fa-map-pin'],
            ['label' => 'Wilayah', 'route' => 'kota.wilayah.index', 'icon' => 'fa-map-location-dot'],
            ['label' => 'Website', 'route' => 'home', 'icon' => 'fa-globe', 'external' => true],
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
            ->all();
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
            ->all();
    }

    public function dashboard()
    {
        $user = Auth::user();
        $kotaIds = self::coverageKotaIds($user);

        $stats = [
            'provinsi' => DB::table('provinsis')->count(),
            'kota' => DB::table('kota_kabupatens')->count(),
            'user' => DB::table('users')->count(),
            'coverage_kota' => count($kotaIds),
        ];

        if (!empty($kotaIds)) {
            $cityNames = DB::table('kota_kabupatens')->whereIn('id', $kotaIds)->pluck('nama')->all();
            $stats['merchant'] = DB::table('merchants')
                ->where(function ($q) use ($cityNames) {
                    foreach ($cityNames as $i => $nama) {
                        if ($i === 0) {
                            $q->where('city', 'ilike', "%{$nama}%");
                        } else {
                            $q->orWhere('city', 'ilike', "%{$nama}%");
                        }
                    }
                })->count();
            $stats['driver'] = DB::table('drivers')->count();
            $stats['driver_catatan'] = true;
        } else {
            $stats['merchant'] = 0;
            $stats['driver'] = DB::table('drivers')->count();
            $stats['driver_catatan'] = true;
        }

        return view('kota.dashboard', compact('user', 'stats', 'kotaIds'));
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
        $type = $request->query('type', 'merchant');
        $search = $request->query('search');

        if ($type === 'driver') {
            $drivers = self::scopedDrivers($request);
            return view('kota.members.index', compact('type', 'search', 'drivers'));
        }

        $merchants = self::scopedMerchants($request);
        return view('kota.members.index', compact('type', 'search', 'merchants'));
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

    public function logout(Request $request)
    {
        return redirect()->route('kota.logout');
    }
}
