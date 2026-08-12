<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'users' => DB::table('users')->count(),
            'merchants' => DB::table('merchants')->count(),
            'orders' => DB::table('orders')->count(),
            'revenue' => DB::table('orders')->where('status', 'COMPLETED')->sum('total_amount'),
            'drivers' => DB::table('drivers')->count(),
            'promos' => DB::table('promos')->count(),
        ];
        $recentOrders = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('merchants', 'orders.merchant_id', '=', 'merchants.id')
            ->select('orders.*', 'users.full_name as customer_name', 'merchants.name as merchant_name')
            ->orderBy('orders.created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders'));
    }

    // Users Management
    public function usersIndex()
    {
        $users = DB::table('users')->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function usersDestroy($id)
    {
        DB::table('users')->where('id', $id)->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    // Merchants Management
    public function merchantsIndex()
    {
        $merchants = DB::table('merchants')
            ->join('users', 'merchants.owner_id', '=', 'users.id')
            ->select('merchants.*', 'users.full_name as owner_name')
            ->orderBy('merchants.created_at', 'desc')
            ->paginate(10);
        return view('admin.merchants.index', compact('merchants'));
    }

    public function merchantsCreate()
    {
        $owners = DB::table('users')->where('role', 'MERCHANT')->orWhere('role', 'ADMIN')->get();
        return view('admin.merchants.create', compact('owners'));
    }

    public function merchantsStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_id' => 'required|exists:users,id',
            'type' => 'required|string|in:FOOD,MART,SHOP',
            'address_line' => 'required|string',
            'city' => 'required|string',
            'phone' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        DB::table('merchants')->insert([
            'owner_id' => $validated['owner_id'],
            'type' => $validated['type'],
            'name' => $validated['name'],
            'slug' => \Str::slug($validated['name']) . '-' . rand(100, 999),
            'description' => $validated['description'] ?? '',
            'address_line' => $validated['address_line'],
            'city' => $validated['city'],
            'phone' => $validated['phone'] ?? '',
            'logo_url' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=500',
            'banner_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1000',
            'status' => 'ACTIVE',
            'is_open' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.merchants.index')->with('success', 'Merchant created successfully.');
    }

    public function merchantsDestroy($id)
    {
        DB::table('merchants')->where('id', $id)->delete();
        return redirect()->route('admin.merchants.index')->with('success', 'Merchant deleted successfully.');
    }

    // Products / Menu Items Management
    public function productsIndex()
    {
        $products = DB::table('menu_items')
            ->join('merchants', 'menu_items.merchant_id', '=', 'merchants.id')
            ->select('menu_items.*', 'merchants.name as merchant_name')
            ->orderBy('menu_items.created_at', 'desc')
            ->paginate(10);
        return view('admin.products.index', compact('products'));
    }

    public function productsCreate()
    {
        $merchants = DB::table('merchants')->get();
        return view('admin.products.create', compact('merchants'));
    }

    public function productsStore(Request $request)
    {
        $validated = $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        DB::table('menu_items')->insert([
            'merchant_id' => $validated['merchant_id'],
            'name' => $validated['name'],
            'slug' => \Str::slug($validated['name']) . '-' . rand(100, 999),
            'description' => $validated['description'] ?? '',
            'price' => $validated['price'],
            'image_url' => 'https://images.unsplash.com/photo-1569050471253-73c368d1469e?w=500',
            'is_available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Product/Menu item created successfully.');
    }

    public function productsDestroy($id)
    {
        DB::table('menu_items')->where('id', $id)->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }

    // Orders Management
    public function ordersIndex()
    {
        $orders = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('merchants', 'orders.merchant_id', '=', 'merchants.id')
            ->select('orders.*', 'users.full_name as customer_name', 'merchants.name as merchant_name')
            ->orderBy('orders.created_at', 'desc')
            ->paginate(10);
        return view('admin.orders.index', compact('orders'));
    }

    public function ordersShow($id)
    {
        $order = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('merchants', 'orders.merchant_id', '=', 'merchants.id')
            ->leftJoin('drivers', 'orders.driver_id', '=', 'drivers.id')
            ->leftJoin('users as driver_user', 'drivers.user_id', '=', 'driver_user.id')
            ->select('orders.*', 'users.full_name as customer_name', 'users.phone as customer_phone', 'merchants.name as merchant_name', 'driver_user.full_name as driver_name')
            ->where('orders.id', $id)
            ->first();

        $items = DB::table('order_items')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->select('order_items.*', 'menu_items.name as product_name')
            ->where('order_items.order_id', $id)
            ->get();

        return view('admin.orders.show', compact('order', 'items'));
    }

    public function ordersUpdateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:PENDING,PROCESSING,COMPLETED,CANCELLED',
        ]);

        DB::table('orders')->where('id', $id)->update(['status' => $validated['status'], 'updated_at' => now()]);
        return back()->with('success', 'Order status updated successfully.');
    }

    // Promos Management
    public function promosIndex()
    {
        $promos = DB::table('promos')->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.promos.index', compact('promos'));
    }

    public function promosStore(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:promos,code',
            'title' => 'required|string',
            'discount_type' => 'required|string|in:PERCENTAGE,FIXED',
            'discount_value' => 'required|numeric|min:0',
            'min_purchase' => 'required|numeric|min:0',
        ]);

        DB::table('promos')->insert([
            'code' => strtoupper($validated['code']),
            'title' => $validated['title'],
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'min_purchase' => $validated['min_purchase'],
            'expires_at' => now()->addDays(30),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.promos.index')->with('success', 'Promo created successfully.');
    }

    public function promosDestroy($id)
    {
        DB::table('promos')->where('id', $id)->delete();
        return redirect()->route('admin.promos.index')->with('success', 'Promo deleted successfully.');
    }
}
