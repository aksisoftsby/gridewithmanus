<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type'); // FOOD, MART, SHOP
        $search = $request->query('search');

        $query = DB::table('merchants')->where('status', 'ACTIVE');

        if ($type) {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $merchants = $query->orderBy('rating', 'desc')->paginate(12);
        $promos = DB::table('promos')->where('is_active', true)->get();

        return view('public.index', compact('merchants', 'promos', 'type', 'search'));
    }

    public function merchantDetail($slug)
    {
        $merchant = DB::table('merchants')->where('slug', $slug)->first();
        if (!$merchant) {
            abort(404);
        }

        $menuItems = DB::table('menu_items')->where('merchant_id', $merchant->id)->get();

        return view('public.merchant', compact('merchant', 'menuItems'));
    }

    public function apiDocs()
    {
        return view('api.docs');
    }
}
