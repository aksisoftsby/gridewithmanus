<?php

namespace App\Http;

use Illuminate\Support\Facades\Auth;

/**
 * Akses panel admin (/admin) berdasarkan kolom users.role_kota:
 *   - ADMIN   : semua menu (termasuk Settings)
 *   - MANAGER : semua menu KECUALI Settings
 *   - MEMBER  : tidak ada akses panel admin
 *
 * Kolom users.role (CUSTOMER/DRIVER/MERCHANT/ADMIN) tidak lagi dipakai untuk akses panel.
 */
class Access
{
    public static function canAdmin(): bool
    {
        return in_array(self::roleKota(), ['ADMIN', 'MANAGER'], true);
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
        if (self::canSettings()) {
            return $all;
        }
        return collect($all)->reject(fn ($item) => str_contains($item['route'], 'settings'))->values()->all();
    }
}
