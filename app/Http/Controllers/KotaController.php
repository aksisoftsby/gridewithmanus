<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Controller panel /admin/kota — pengelolaan wilayah (Provinsi → Kota/Kabupaten)
 * dan pengguna panel kota. Hanya bisa diakses role ADMIN / MANAGER.
 */
class KotaController extends Controller
{
    public static function kotaNav(): array
    {
        return [
            ['label' => 'Dashboard', 'route' => 'kota.dashboard', 'icon' => 'fa-gauge'],
            ['label' => 'Wilayah', 'route' => 'kota.wilayah.index', 'icon' => 'fa-map-location-dot'],
            ['label' => 'Pengguna', 'route' => 'kota.users.index', 'icon' => 'fa-users'],
            ['label' => 'Website', 'route' => 'home', 'icon' => 'fa-globe', 'external' => true],
        ];
    }

    public function dashboard()
    {
        $user = Auth::user();
        $stats = [
            'provinsi' => DB::table('provinsis')->count(),
            'kota'     => DB::table('kota_kabupatens')->count(),
            'user'     => DB::table('users')->count(),
            'admin_kota' => DB::table('users')->whereIn('role_kota', ['ADMIN', 'MANAGER'])->count(),
        ];
        return view('kota.dashboard', compact('user', 'stats'));
    }

    public function wilayahIndex(Request $request)
    {
        $searchProvinsi = $request->get('provinsi');
        $provinsis = DB::table('provinsis')->orderBy('nama')->get();
        $kota = DB::table('kota_kabupatens')
            ->join('provinsis', 'kota_kabupatens.provinsi_id', '=', 'provinsis.id')
            ->select('kota_kabupatens.*', 'provinsis.nama as provinsi_nama')
            ->when($searchProvinsi, fn ($q) => $q->where('kota_kabupatens.provinsi_id', $searchProvinsi))
            ->orderBy('provinsis.nama')
            ->orderBy('kota_kabupatens.nama')
            ->paginate(50);
        return view('kota.wilayah.index', compact('provinsis', 'kota', 'searchProvinsi'));
    }

    public function wilayahDetail($id)
    {
        $kota = DB::table('kota_kabupatens')
            ->join('provinsis', 'kota_kabupatens.provinsi_id', '=', 'provinsis.id')
            ->select('kota_kabupatens.*', 'provinsis.nama as provinsi_nama')
            ->where('kota_kabupatens.id', $id)
            ->first();
        if (!$kota) {
            abort(404);
        }
        return view('kota.wilayah.detail', compact('kota'));
    }

    public function usersIndex(Request $request)
    {
        $search = $request->get('search');
        $query = DB::table('users')
            ->select('id', 'email', 'full_name', 'phone', 'role', 'role_kota', 'status', 'created_at');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'ilike', "%{$search}%")
                  ->orWhere('full_name', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }
        $users = $query->orderBy('id', 'desc')->paginate(25);
        return view('kota.users.index', compact('users', 'search'));
    }

    /**
     * Ubah role_kota pengguna (ADMIN / MANAGER / MEMBER).
     */
    public function usersRoleUpdate(Request $request, $id)
    {
        $request->validate(['role_kota' => ['required', 'in:ADMIN,MANAGER,MEMBER']]);
        DB::table('users')->where('id', $id)->update(['role_kota' => strtoupper($request->role_kota)]);
        return back()->with('success', 'Role kota pengguna berhasil diperbarui.');
    }

    public function logout(Request $request)
    {
        return redirect()->route('kota.logout');
    }
}
