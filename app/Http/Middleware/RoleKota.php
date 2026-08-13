<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: hanya ADMIN / MANAGER (users.role_kota) yang boleh mengakses panel /admin/kota.
 */
class RoleKota
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $roleKota = strtoupper((string) ($user->role_kota ?? 'MEMBER'));

        if (!in_array($roleKota, ['ADMIN', 'MANAGER'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'Anda tidak memiliki akses ke panel kota. Role Anda: ' . $roleKota);
        }

        return $next($request);
    }
}
