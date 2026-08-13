<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: hanya MANAGER (users.role_kota) yang boleh mengakses panel /admin/kota.
 * ADMIN super mengakses /admin, bukan panel kota.
 */
class RoleKota
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $roleKota = strtoupper((string) ($user->role_kota ?? 'MEMBER'));

        if ($roleKota !== 'MANAGER') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($roleKota === 'ADMIN') {
                abort(403, 'ADMIN super login di /admin/login, bukan di panel kota.');
            }
            abort(403, 'Anda tidak memiliki akses ke panel kota. Role Anda: ' . $roleKota);
        }

        return $next($request);
    }
}
