<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Login khusus halaman webview iklan baris.
 * Tetap memakai tabel users + session web yang sama dengan aplikasi,
 * sehingga sesi login member yang didapat dari app (cookie domain ridesip.my.id)
 * juga berlaku di halaman ini.
 */
class WebviewAuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        $intended = $request->query('intended', route('iklanwebview.index'));
        return view('iklanwebview.login', compact('intended'));
    }

    public function login(Request $request)
    {
        $intended = $request->query('intended', route('iklanwebview.index'));
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->to($intended);
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
        return redirect()->route('iklanwebview.index');
    }
}
