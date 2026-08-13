<?php

namespace App\Http;

use Illuminate\Support\Facades\Auth;

/**
 * Akses panel admin (/admin):
 *   - role_kota ADMIN : akses penuh (termasuk Settings)
 *   - role_kota MANAGER : TIDAK punya akses panel admin (login di /admin/kota)
 *   - MEMBER : tidak ada akses
 */
class Access
{
    public static function canAdmin(): bool
    {
        // Panel admin /admin khusus ADMIN super (platform role).
        $user = Auth::user();
        return strtoupper((string) ($user->role ?? '')) === 'ADMIN';
    }

    public static function canSettings(): bool
    {
        return self::roleKota() === 'ADMIN';
    }

    public static function roleKota(): string
    {
        $user = Auth::user();
        return strtoupper((string) ($user->role_kota ?? 'MEMBER'));
    }

    /**
     * Menu admin yang boleh dilihat sesuai role_kota (Settings hanya ADMIN).
     */
    public static function adminNav(): array
    {
        $all = \App\Http\Controllers\AdminController::adminNav();
        if ($all instanceof \Illuminate\Support\Collection) {
            $all = $all->all();
        }
        if (self::canSettings()) {
            return $all;
        }
        return collect($all)->reject(fn ($item) => str_contains($item['route'], 'settings'))->values()->all();
    }
}
