<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            $platformRole = strtoupper((string) ($user->role ?? ''));
            $roleKota = strtoupper((string) ($user->role_kota ?? 'MEMBER'));

            if ($roleKota === 'MANAGER') {
                Auth::logout();
                return back()->withErrors(['email' => 'MANAGER login di /admin/kota, bukan di panel admin.']);
            }
            if ($platformRole !== 'ADMIN') {
                Auth::logout();
                return back()->withErrors(['email' => 'Akses ditolak. Hanya role ADMIN super yang dapat masuk ke panel admin.']);
            }
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
