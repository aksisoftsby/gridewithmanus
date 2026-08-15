<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ManagerAreaService — area scope terpusat untuk panel Manager (/admin/kota).
 *
 * Sesuai admin-revisi.md:
 * - Manager hanya boleh melihat/mengelola entity (driver, merchant, customer, order,
 *   payment, wallet) yang berada di kota coverage-nya.
 * - Scope berada di backend authorization/query layer, BUKAN hanya filter UI.
 * - Entity scoped via kolom relasi (city_id / operating_city_id); fallback string
 *   (city ILIKE nama kota) tetap dipertahankan agar data lama tetap terjangkau.
 * - Authorization harus 403 jika entity di luar coverage — bukan 200 + UI tersembunyi.
 */
class ManagerAreaService
{
    /**
     * ID kota-kota coverage user MANAGER saat ini.
     * ADMIN super (role_kota='ADMIN') dianggap "semua kota" via null/empty handling
     * di caller.
     */
    public static function coverageKotaIds($user = null): array
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return [];
        }
        return DB::table('manager_coverage')
            ->where('user_id', $user->id)
            ->pluck('id_kota')
            ->all();
    }

    /** Nama kota coverage untuk fallback filter string. */
    public static function coverageKotaNames($user = null): array
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

    /** Apakah user adalah ADMIN super panel kota. */
    public static function isAdminSuper($user = null): bool
    {
        $user = $user ?? Auth::user();
        return strtoupper((string) ($user->role_kota ?? 'MEMBER')) === 'ADMIN';
    }

    /**
     * Gabungkan scope area ke query builder untuk suatu tabel.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  string   $tablePrefix   alias tabel yang di-scope ('merchants', 'm', '')
     * @param  string   $type          'merchant' | 'driver' | 'customer'
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public static function applyScope($query, string $tablePrefix = '', string $type = 'merchant')
    {
        $user = Auth::user();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }
        if (self::isAdminSuper($user)) {
            return $query;
        }

        $kotaIds = self::coverageKotaIds($user);
        $kotaNames = self::coverageKotaNames($user);
        $tp = $tablePrefix ? $tablePrefix . '.' : '';

        if (empty($kotaIds)) {
            return $query->whereRaw('1 = 0');
        }

        switch ($type) {
            case 'merchant':
                // Relasi kuat via city_id; fallback string city ILIKE nama kota.
                $query->where(function ($q) use ($tp, $kotaIds, $kotaNames) {
                    $q->whereIn($tp . 'city_id', $kotaIds);
                    foreach ($kotaNames as $nama) {
                        $q->orWhere($tp . 'city', 'ilike', "%{$nama}%");
                    }
                });
                break;

            case 'driver':
                // Relasi kuat via drivers.operating_city_id; fallback: driver tanpa
                // operating_city_id TIDAK otomatis diizinkan (harus diassign dulu).
                $query->whereIn($tp . 'operating_city_id', $kotaIds);
                break;

            case 'customer':
                // Customer di-scope dari users.home_city_id (nullable);
                // tanpa tabel alamat terpisah, customer tanpa home_city_id
                // tidak terlihat oleh manager (data sensitif — bukan UI-only).
                $query->whereIn($tp . 'home_city_id', $kotaIds);
                break;
        }

        return $query;
    }

    /**
     * Scope orders untuk Manager: melalui merchant_id (merchant di kota coverage)
     * atau ride (service_type) yang pickup/dropoff di kota coverage (diperkirakan
     * dari merchant city fallback) atau driver di kota coverage.
     */
    public static function scopeOrders($query, $user = null)
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }
        if (self::isAdminSuper($user)) {
            return $query;
        }

        $kotaIds = self::coverageKotaIds($user);
        $kotaNames = self::coverageKotaNames($user);
        if (empty($kotaIds)) {
            return $query->whereRaw('1 = 0');
        }

        $merchantScope = function ($q) use ($kotaIds, $kotaNames) {
            $q->whereIn('m.city_id', $kotaIds);
            foreach ($kotaNames as $nama) {
                $q->orWhere('m.city', 'ilike', "%{$nama}%");
            }
        };

        // Order via merchant di kota coverage
        $query->where(function ($q) use ($merchantScope) {
            $q->whereIn('merchant_id', function ($sub) use ($merchantScope) {
                $sub->from('merchants as m')->select('m.id')->where($merchantScope);
            });
        });

        return $query;
    }

    /**
     * Pastikan entity berada dalam area Manager — 403 jika tidak.
     *
     * @param  string  $type     merchant | driver | customer | order | payment | wallet
     * @param  mixed   $entityId
     * @return void  (abort 403 jika di luar area)
     */
    public static function requireInArea(string $type, $entityId)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Akses ditolak.');
        }
        if (self::isAdminSuper($user)) {
            return;
        }

        $kotaIds = self::coverageKotaIds($user);
        $kotaNames = self::coverageKotaNames($user);
        if (empty($kotaIds)) {
            abort(403, 'Anda belum memiliki coverage kota.');
        }

        $inArea = false;

        switch ($type) {
            case 'merchant': {
                $m = DB::table('merchants')->where('id', $entityId)->first();
                if (!$m) {
                    abort(404, 'Merchant tidak ditemukan');
                }
                $inArea = in_array((int) $m->city_id, array_map('intval', $kotaIds), true)
                    || self::cityNameMatches((string) $m->city, $kotaNames);
                break;
            }
            case 'driver': {
                $d = DB::table('drivers')->where('id', $entityId)->first();
                if (!$d) {
                    abort(404, 'Driver tidak ditemukan');
                }
                $inArea = in_array((int) $d->operating_city_id, array_map('intval', $kotaIds), true);
                break;
            }
            case 'customer': {
                $u = DB::table('users')->where('id', $entityId)->first();
                if (!$u) {
                    abort(404, 'Customer tidak ditemukan');
                }
                $inArea = in_array((int) ($u->home_city_id ?? 0), array_map('intval', $kotaIds), true);
                break;
            }
            case 'order': {
                $o = DB::table('orders')->where('id', $entityId)->first();
                if (!$o) {
                    abort(404, 'Order tidak ditemukan');
                }
                if (!empty($o->merchant_id)) {
                    $m = DB::table('merchants')->where('id', $o->merchant_id)->first();
                    if ($m) {
                        $inArea = in_array((int) $m->city_id, array_map('intval', $kotaIds), true)
                            || self::cityNameMatches((string) $m->city, $kotaNames);
                    }
                }
                break;
            }
            case 'payment': {
                $p = DB::table('payments')->where('id', $entityId)->first();
                if (!$p) {
                    abort(404, 'Payment tidak ditemukan');
                }
                $order = DB::table('orders')->where('id', $p->order_id ?? 0)->first();
                if ($order) {
                    self::requireInArea('order', $order->id);
                    return; // abort 403 di requireInArea jika tidak in area
                }
                break;
            }
            case 'wallet': {
                $w = DB::table('wallets')->where('id', $entityId)->first();
                if (!$w) {
                    abort(404, 'Wallet tidak ditemukan');
                }
                self::requireInArea('customer', $w->user_id);
                return;
            }
        }

        if (!$inArea) {
            abort(403, 'Data ini berada di luar coverage kota Anda.');
        }
    }

    private static function cityNameMatches(string $city, array $kotaNames): bool
    {
        foreach ($kotaNames as $nama) {
            if (stripos($city, $nama) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Catat aksi Manager ke audit_logs.
     */
    public static function audit(string $action, string $entityType, $entityId, $before = null, $after = null)
    {
        $user = Auth::user();
        DB::table('audit_logs')->insert([
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => is_scalar($entityId) ? $entityId : null,
            'before_data' => $before !== null ? json_encode($before) : null,
            'after_data' => $after !== null ? json_encode($after) : null,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
