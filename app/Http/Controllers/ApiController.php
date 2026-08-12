<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function merchants(Request $request)
    {
        $type = $request->query('type');
        $query = DB::table('merchants')->where('status', 'ACTIVE');
        if ($type) {
            $query->where('type', $type);
        }
        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }

    public function merchantMenu($id)
    {
        $merchant = DB::table('merchants')->where('id', $id)->first();
        if (!$merchant) {
            return response()->json(['status' => 'error', 'message' => 'Merchant not found'], 404);
        }
        $menu = DB::table('menu_items')->where('merchant_id', $id)->get();
        return response()->json([
            'status' => 'success',
            'merchant' => $merchant,
            'menu' => $menu
        ]);
    }

    public function products()
    {
        $products = DB::table('menu_items')->join('merchants', 'menu_items.merchant_id', '=', 'merchants.id')
            ->select('menu_items.*', 'merchants.name as merchant_name')
            ->get();
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    public function orders(Request $request)
    {
        $orders = DB::table('orders')->orderBy('created_at', 'desc')->limit(50)->get();
        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    public function promos()
    {
        $promos = DB::table('promos')->where('is_active', true)->get();
        return response()->json([
            'status' => 'success',
            'data' => $promos
        ]);
    }
}
