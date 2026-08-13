<?php

namespace App\Http\Middleware;

use App\Http\Access;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: hanya role_kota ADMIN yang boleh mengakses Settings panel admin.
 * MANAGER ditolak dengan 403.
 */
class RoleSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Access::canSettings()) {
            abort(403, 'Settings hanya dapat diakses oleh role ADMIN.');
        }
        return $next($request);
    }
}
