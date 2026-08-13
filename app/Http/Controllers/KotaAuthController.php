<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller untuk panel /admin/kota (login & logout terpisah dari /admin/login).
 * Role panel kota (kolom users.role_kota):
 *   - ADMIN   : akses penuh panel /admin/kota
 *   - MANAGER : akses penuh panel /admin/kota
 *   - MEMBER  : tidak bisa login ke panel kota (default saat register)
 */
class KotaAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('kota.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            $roleKota = strtoupper((string) ($user->role_kota ?? 'MEMBER'));

            if (!in_array($roleKota, ['ADMIN', 'MANAGER'], true)) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Akses ditolak. Akun Anda belum memiliki role ADMIN/MANAGER untuk panel kota. Hubungi administrator.',
                ]);
            }

            $request->session()->regenerate();
            return redirect()->intended(route('kota.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Email atau password tidak cocok.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('kota.login');
    }
}
