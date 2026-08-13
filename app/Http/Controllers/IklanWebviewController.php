<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Halaman landing iklan baris untuk WebView aplikasi (tanpa header/footer).
 * URL: /iklan-webview
 * Session login member di aplikasi (domain yang sama: gride.web.id) ikut terbaca
 * melalui cookie, sehingga member bisa posting dan melihat iklan miliknya.
 */
class IklanWebviewController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $cat = $request->query('category_id');

        if (!DB::getSchemaBuilder()->hasTable('iklan_gratis')) {
            return view('iklanwebview.index', [
                'iklan' => collect(),
                'categories' => collect(),
                'search' => $search,
                'cat' => $cat,
            ]);
        }

        $query = DB::table('iklan_gratis')
            ->leftJoin('iklan_gratis_categories', 'iklan_gratis.category_id', '=', 'iklan_gratis_categories.id')
            ->select('iklan_gratis.*', 'iklan_gratis_categories.name as category_name');

        $query->where('iklan_gratis.status', 'ACTIVE');
        $query->where(function ($q) {
            $q->where('iklan_gratis.expired_at', '>=', now())->orWhereNull('iklan_gratis.expired_at');
        });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('iklan_gratis.title', 'like', '%' . $search . '%')
                  ->orWhere('iklan_gratis.description', 'like', '%' . $search . '%')
                  ->orWhere('iklan_gratis.city', 'like', '%' . $search . '%');
            });
        }
        if ($cat) {
            $query->where('iklan_gratis.category_id', $cat);
        }

        $iklan = $query->orderBy('iklan_gratis.created_at', 'desc')->paginate(12)->withQueryString();
        $categories = DB::table('iklan_gratis_categories')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        $me = Auth::user();
        $myIklanCount = $me ? DB::table('iklan_gratis')->where('user_id', $me->id)->count() : 0;

        return view('iklanwebview.index', compact('iklan', 'categories', 'search', 'cat', 'me', 'myIklanCount'));
    }

    public function detail($id)
    {
        if (!DB::getSchemaBuilder()->hasTable('iklan_gratis')) {
            abort(404);
        }

        $item = DB::table('iklan_gratis')
            ->leftJoin('iklan_gratis_categories', 'iklan_gratis.category_id', '=', 'iklan_gratis_categories.id')
            ->select('iklan_gratis.*', 'iklan_gratis_categories.name as category_name')
            ->where('iklan_gratis.id', $id)
            ->where('iklan_gratis.status', 'ACTIVE')
            ->where(function ($q) {
                $q->where('iklan_gratis.expired_at', '>=', now())->orWhereNull('iklan_gratis.expired_at');
            })
            ->first();

        if (!$item) {
            abort(404);
        }

        $me = Auth::user();
        $isOwner = $me && (int) $me->id === (int) $item->user_id;

        $photos = [];
        if ($item->photos) {
            $decoded = json_decode($item->photos, true);
            $photos = is_array($decoded) ? array_values($decoded) : [];
        }

        return view('iklanwebview.detail', compact('item', 'me', 'isOwner', 'photos'));
    }

    public function create(Request $request)
    {
        $me = Auth::user();
        if (!$me) {
            // Belum login: arahkan ke halaman login web (session web sama dengan app)
            return redirect()->route('webview.login');
        }

        $count = DB::table('iklan_gratis')->where('user_id', $me->id)->count();
        if ($count >= 50) {
            return redirect()->route('iklanwebview.index')->with('error', 'Batas iklan aktif Anda sudah tercapai.');
        }

        $categories = DB::table('iklan_gratis_categories')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        return view('iklanwebview.form', ['me' => $me, 'categories' => $categories, 'item' => null]);
    }

    public function store(Request $request)
    {
        $me = Auth::user();
        if (!$me) {
            return redirect()->route('webview.login');
        }

        $data = $request->validate([
            'category_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'expired_months' => ['required', 'integer', 'min:1', 'max:12'],
            'photo_urls' => ['nullable', 'string', 'max:10000'],
        ]);

        $count = DB::table('iklan_gratis')->where('user_id', $me->id)->count();
        if ($count >= 50) {
            return back()->withErrors(['error' => 'Batas iklan aktif Anda sudah tercapai.']);
        }

        $photosRaw = $data['photo_urls'] ?? '';
        $photos = array_values(array_filter(array_map('trim', explode("\n", $photosRaw))));
        if (count($photos) > 10) {
            $photos = array_slice($photos, 0, 10);
        }

        DB::table('iklan_gratis')->insert([
            'user_id' => $me->id,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'price' => $data['price'] ?? 0,
            'photos' => json_encode($photos),
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'city' => $data['city'] ?? null,
            'status' => 'ACTIVE',
            'expired_at' => now()->addMonths((int) $data['expired_months']),
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('iklanwebview.index')->with('success', 'Iklan baris berhasil dipasang.');
    }

    public function myIklan(Request $request)
    {
        $me = Auth::user();
        if (!$me) {
            return redirect()->route('webview.login');
        }

        $iklan = DB::table('iklan_gratis')
            ->leftJoin('iklan_gratis_categories', 'iklan_gratis.category_id', '=', 'iklan_gratis_categories.id')
            ->select('iklan_gratis.*', 'iklan_gratis_categories.name as category_name')
            ->where('iklan_gratis.user_id', $me->id)
            ->orderBy('iklan_gratis.created_at', 'desc')
            ->paginate(12);

        return view('iklanwebview.my', compact('me', 'iklan'));
    }
}
