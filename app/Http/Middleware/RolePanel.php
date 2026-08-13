<?php

namespace App\Http\Middleware;

use App\Http\Access;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware panel admin utama (/admin): hanya role_kota ADMIN / MANAGER.
 * Dipasang di middleware group 'admin.panel'.
 */
class RolePanel
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Access::canAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'Anda tidak memiliki akses ke panel admin. Role Anda: ' . Access::roleKota());
        }
        return $next($request);
    }
}
