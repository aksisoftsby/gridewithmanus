<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller untuk panel /admin/kota — login khusus ROLE MANAGER.
 * (Terpisah dari /admin/login yang khusus ADMIN super.)
 *   - MANAGER : login panel /admin/kota, kelola member (merchant/driver) sesuai coverage kota
 *   - ADMIN   : harus login via /admin/login (ditolak di sini)
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

            if ($roleKota === 'ADMIN') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Akun Anda adalah ADMIN super. Silakan login melalui halaman admin biasa: /admin/login',
                ]);
            }

            if ($roleKota !== 'MANAGER') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Akses ditolak. Akun Anda belum memiliki role MANAGER untuk panel kota. Hubungi administrator.',
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
