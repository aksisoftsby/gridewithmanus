<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Share the admin top navigation with all admin views.
     */
    public static function adminNav()
    {
        return collect([
            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge'],
            ['route' => 'admin.users.index', 'label' => 'Users', 'icon' => 'fa-users'],
            ['route' => 'admin.merchants.index', 'label' => 'Merchants', 'icon' => 'fa-store'],
            ['route' => 'admin.products.index', 'label' => 'Products', 'icon' => 'fa-utensils'],
            ['route' => 'admin.orders.index', 'label' => 'Orders', 'icon' => 'fa-receipt'],
            ['route' => 'admin.drivers.index', 'label' => 'Drivers', 'icon' => 'fa-motorcycle'],
            ['route' => 'admin.promos.index', 'label' => 'Promos', 'icon' => 'fa-tags'],
            ['route' => 'admin.chats.index', 'label' => 'Chat Sessions', 'icon' => 'fa-comments'],
        ]);
    }
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

    public function productsEdit($id)
    {
        $product = DB::table('menu_items')->where('id', $id)->first();
        abort_if(!$product, 404, 'Product not found');
        $merchants = DB::table('merchants')->get();
        return view('admin.products.edit', compact('product', 'merchants'));
    }

    public function productsUpdate(Request $request, $id)
    {
        $product = DB::table('menu_items')->where('id', $id)->first();
        abort_if(!$product, 404, 'Product not found');

        $validated = $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_available' => 'nullable',
        ]);

        DB::table('menu_items')->where('id', $id)->update([
            'merchant_id' => $validated['merchant_id'],
            'name' => $validated['name'],
            'slug' => \Str::slug($validated['name']) . '-' . rand(100, 999),
            'description' => $validated['description'] ?? '',
            'price' => $validated['price'],
            'is_available' => $request->has('is_available'),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Product/Menu item updated successfully.');
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
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'is_active' => 'nullable',
        ]);

        DB::table('promos')->insert([
            'code' => strtoupper($validated['code']),
            'title' => $validated['title'],
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'min_purchase' => $validated['min_purchase'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'expires_at' => $validated['ends_at'],
            'is_active' => $request->has('is_active'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.promos.index')->with('success', 'Promo created successfully.');
    }

    public function promosEdit($id)
    {
        $promo = DB::table('promos')->where('id', $id)->first();
        abort_if(!$promo, 404, 'Promo not found');
        return view('admin.promos.edit', compact('promo'));
    }

    public function promosUpdate(Request $request, $id)
    {
        $promo = DB::table('promos')->where('id', $id)->first();
        abort_if(!$promo, 404, 'Promo not found');

        $validated = $request->validate([
            'code' => 'required|string|unique:promos,code,' . $id,
            'title' => 'required|string',
            'discount_type' => 'required|string|in:PERCENTAGE,FIXED',
            'discount_value' => 'required|numeric|min:0',
            'min_purchase' => 'required|numeric|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'is_active' => 'nullable',
        ]);

        DB::table('promos')->where('id', $id)->update([
            'code' => strtoupper($validated['code']),
            'title' => $validated['title'],
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'min_purchase' => $validated['min_purchase'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'expires_at' => $validated['ends_at'],
            'is_active' => $request->has('is_active'),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.promos.index')->with('success', 'Promo updated successfully.');
    }

    // Drivers Management
    public function driversIndex()
    {
        $drivers = DB::table('drivers')
            ->join('users', 'drivers.user_id', '=', 'users.id')
            ->select('drivers.*', 'users.full_name', 'users.email', 'users.phone')
            ->orderBy('drivers.created_at', 'desc')
            ->paginate(10)
            ->through(function ($d) {
                if ($d->created_at) {
                    $d->created_at = Carbon::parse($d->created_at)->format('d M Y');
                }
                return $d;
            });
        return view('admin.drivers.index', compact('drivers'));
    }

    public function driversCreate()
    {
        $users = DB::table('users')->get();
        return view('admin.drivers.create', compact('users'));
    }

    public function driversStore(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'nullable|string|in:ONLINE,OFFLINE',
            'rating' => 'nullable|numeric|min:0|max:5',
        ]);

        DB::table('drivers')->insert([
            'user_id' => $validated['user_id'],
            'status' => $validated['status'] ?? 'ONLINE',
            'is_verified' => true,
            'rating' => $validated['rating'] ?? 5.00,
            'total_trips' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.drivers.index')->with('success', 'Driver registered successfully.');
    }

    public function driversUpdateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:ONLINE,OFFLINE',
        ]);

        DB::table('drivers')->where('id', $id)->update(['status' => $validated['status'], 'updated_at' => now()]);
        return back()->with('success', 'Driver status updated successfully.');
    }

    public function driversDestroy($id)
    {
        DB::table('drivers')->where('id', $id)->delete();
        return redirect()->route('admin.drivers.index')->with('success', 'Driver removed successfully.');
    }

    // Chat Sessions Management (admin can view all chat sessions)
    public function chatsIndex()
    {
        $sessions = DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.*', 'users.full_name', 'users.email', 'users.role')
            ->orderBy('sessions.last_activity', 'desc')
            ->paginate(20);
        return view('admin.chats.index', compact('sessions'));
    }

    public function chatsDestroy($id)
    {
        DB::table('sessions')->where('id', $id)->delete();
        return redirect()->route('admin.chats.index')->with('success', 'Session deleted successfully.');
    }

    public function chatsFlush()
    {
        DB::table('sessions')->truncate();
        return redirect()->route('admin.chats.index')->with('success', 'All sessions cleared.');
    }
}
