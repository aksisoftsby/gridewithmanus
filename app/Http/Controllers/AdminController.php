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
            ['route' => 'admin.news.index', 'label' => 'News', 'icon' => 'fa-newspaper'],
            ['route' => 'admin.testimonials.index', 'label' => 'Testimonials', 'icon' => 'fa-quote-left'],
            ['route' => 'admin.iklan.index', 'label' => 'Iklan Gratis', 'icon' => 'fa-bullhorn'],
            ['route' => 'admin.chats.index', 'label' => 'Chat Sessions', 'icon' => 'fa-comments'],
            ['route' => 'admin.settings.index', 'label' => 'Settings', 'icon' => 'fa-gear'],
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
    public function usersIndex(Request $request)
    {
        $search = $request->query('search');
        $query = DB::table('users');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('role', 'like', '%' . $search . '%');
            });
        }
        $users = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        return view('admin.users.index', compact('users', 'search'));
    }

    public function usersEdit($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        abort_if(!$user, 404, 'User not found');
        return view('admin.users.edit', compact('user'));
    }

    public function usersUpdate(Request $request, $id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        abort_if(!$user, 404, 'User not found');

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|string|in:CUSTOMER,DRIVER,MERCHANT,ADMIN',
            'status' => 'required|string|in:ACTIVE,INACTIVE,SUSPENDED',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ]);

        $data = [
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $validated['status'],
            'phone' => $validated['phone'] ?? $user->phone,
            'updated_at' => now(),
        ];
        if (!empty($validated['password'])) {
            $data['password_hash'] = \Hash::make($validated['password']);
            $data['password'] = \Hash::make($validated['password']);
        }
        DB::table('users')->where('id', $id)->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function usersDestroy($id)
    {
        DB::table('users')->where('id', $id)->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    // Merchants Management
    public function merchantsIndex(Request $request)
    {
        $search = $request->query('search');
        $query = DB::table('merchants')
            ->join('users', 'merchants.owner_id', '=', 'users.id')
            ->select('merchants.*', 'users.full_name as owner_name');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('merchants.name', 'like', '%' . $search . '%')
                  ->orWhere('merchants.city', 'like', '%' . $search . '%')
                  ->orWhere('users.full_name', 'like', '%' . $search . '%');
            });
        }
        $merchants = $query->orderBy('merchants.created_at', 'desc')->paginate(10)->withQueryString();
        return view('admin.merchants.index', compact('merchants', 'search'));
    }

    public function merchantsEdit($id)
    {
        $merchant = DB::table('merchants')->where('id', $id)->first();
        abort_if(!$merchant, 404, 'Merchant not found');
        $owners = DB::table('users')->where('role', 'MERCHANT')->orWhere('role', 'ADMIN')->get();
        return view('admin.merchants.edit', compact('merchant', 'owners'));
    }

    public function merchantsUpdate(Request $request, $id)
    {
        $merchant = DB::table('merchants')->where('id', $id)->first();
        abort_if(!$merchant, 404, 'Merchant not found');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'owner_id' => 'required|exists:users,id',
            'type' => 'required|string|in:FOOD,MART,SHOP',
            'address_line' => 'required|string',
            'city' => 'required|string',
            'phone' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:ACTIVE,INACTIVE,SUSPENDED',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        DB::table('merchants')->where('id', $id)->update([
            'owner_id' => $validated['owner_id'],
            'type' => $validated['type'],
            'name' => $validated['name'],
            'slug' => \Str::slug($validated['name']) . '-' . rand(100, 999),
            'description' => $validated['description'] ?? '',
            'address_line' => $validated['address_line'],
            'city' => $validated['city'],
            'phone' => $validated['phone'] ?? '',
            'status' => $validated['status'] ?? 'ACTIVE',
            'latitude' => $validated['latitude'] ?? $merchant->latitude,
            'longitude' => $validated['longitude'] ?? $merchant->longitude,
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.merchants.index')->with('success', 'Merchant updated successfully.');
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
    public function productsIndex(Request $request)
    {
        $search = $request->query('search');
        $query = DB::table('menu_items')
            ->join('merchants', 'menu_items.merchant_id', '=', 'merchants.id')
            ->select('menu_items.*', 'merchants.name as merchant_name');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('menu_items.name', 'like', '%' . $search . '%')
                  ->orWhere('merchants.name', 'like', '%' . $search . '%');
            });
        }
        $products = $query->orderBy('menu_items.created_at', 'desc')->paginate(10)->withQueryString();
        return view('admin.products.index', compact('products', 'search'));
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
    public function ordersIndex(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $query = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('merchants', 'orders.merchant_id', '=', 'merchants.id')
            ->select('orders.*', 'users.full_name as customer_name', 'merchants.name as merchant_name');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('orders.order_number', 'like', '%' . $search . '%')
                  ->orWhere('users.full_name', 'like', '%' . $search . '%')
                  ->orWhere('orders.order_type', 'like', '%' . $search . '%');
            });
        }
        if ($status) {
            $query->where('orders.status', $status);
        }
        $orders = $query->orderBy('orders.created_at', 'desc')->paginate(10)->withQueryString();
        return view('admin.orders.index', compact('orders', 'search'));
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
    public function promosIndex(Request $request)
    {
        $search = $request->query('search');
        $query = DB::table('promos');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                  ->orWhere('title', 'like', '%' . $search . '%');
            });
        }
        $promos = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        return view('admin.promos.index', compact('promos', 'search'));
    }

    public function promosCreate()
    {
        return view('admin.promos.create', ['title' => 'Tambah Promo']);
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
    public function driversIndex(Request $request)
    {
        $search = $request->query('search');
        $query = DB::table('drivers')
            ->join('users', 'drivers.user_id', '=', 'users.id')
            ->select('drivers.*', 'users.full_name', 'users.email', 'users.phone');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.full_name', 'like', '%' . $search . '%')
                  ->orWhere('users.email', 'like', '%' . $search . '%')
                  ->orWhere('drivers.status', 'like', '%' . $search . '%');
            });
        }
        $drivers = $query->orderBy('drivers.created_at', 'desc')->paginate(10)->withQueryString();
        return view('admin.drivers.index', compact('drivers', 'search'));
    }

    public function driversEdit($id)
    {
        $driver = DB::table('drivers')
            ->join('users', 'drivers.user_id', '=', 'users.id')
            ->select('drivers.*', 'users.full_name', 'users.email', 'users.phone')
            ->where('drivers.id', $id)
            ->first();
        abort_if(!$driver, 404, 'Driver not found');
        return view('admin.drivers.edit', compact('driver'));
    }

    public function driversUpdate(Request $request, $id)
    {
        $driver = DB::table('drivers')->where('id', $id)->first();
        abort_if(!$driver, 404, 'Driver not found');

        $validated = $request->validate([
            'status' => 'nullable|string|in:ONLINE,OFFLINE',
            'rating' => 'nullable|numeric|min:0|max:5',
            'is_verified' => 'nullable',
            'current_lat' => 'nullable|numeric|between:-90,90',
            'current_lng' => 'nullable|numeric|between:-180,180',
        ]);

        DB::table('drivers')->where('id', $id)->update([
            'status' => $validated['status'] ?? $driver->status,
            'rating' => $validated['rating'] ?? $driver->rating,
            'is_verified' => $request->has('is_verified'),
            'current_lat' => $validated['current_lat'] ?? $driver->current_lat,
            'current_lng' => $validated['current_lng'] ?? $driver->current_lng,
            'last_location_at' => isset($validated['current_lat']) ? now() : $driver->last_location_at,
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.drivers.index')->with('success', 'Driver updated successfully.');
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
    public function chatsIndex(Request $request)
    {
        $search = $request->query('search');
        $query = DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.*', 'users.full_name', 'users.email', 'users.role');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.full_name', 'like', '%' . $search . '%')
                  ->orWhere('users.email', 'like', '%' . $search . '%');
            });
        }
        $sessions = $query->orderBy('sessions.last_activity', 'desc')->paginate(20)->withQueryString();
        return view('admin.chats.index', compact('sessions', 'search'));
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

    // News Management
    public function newsIndex(Request $request)
    {
        $search = $request->query('search');
        $query = DB::table('news')
            ->leftJoin('news_categories', 'news.news_category_id', '=', 'news_categories.id')
            ->select('news.*', 'news_categories.name as category_name');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('news.title', 'like', '%' . $search . '%')
                  ->orWhere('news.status', 'like', '%' . $search . '%');
            });
        }
        $news = $query->orderBy('news.created_at', 'desc')->paginate(10)->withQueryString();
        return view('admin.news.index', compact('news', 'search'));
    }

    public function newsCreate()
    {
        $categories = DB::table('news_categories')->where('is_active', true)->get();
        return view('admin.news.create', compact('categories'));
    }

    public function newsStore(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'news_category_id' => 'nullable|exists:news_categories,id',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'status' => 'required|string|in:DRAFT,PUBLISHED',
            'published_at' => 'nullable|date',
            'featured_image' => 'nullable|string',
        ]);

        DB::table('news')->insert([
            'title' => $validated['title'],
            'slug' => \Str::slug($validated['title']) . '-' . rand(100, 999),
            'news_category_id' => $validated['news_category_id'] ?? null,
            'excerpt' => $validated['excerpt'] ?? '',
            'content' => $validated['content'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'PUBLISHED' ? ($validated['published_at'] ?? now()) : null,
            'featured_image' => $validated['featured_image'] ?: 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=1000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.news.index')->with('success', 'News created successfully.');
    }

    public function newsEdit($id)
    {
        $news = DB::table('news')->where('id', $id)->first();
        abort_if(!$news, 404, 'News not found');
        $categories = DB::table('news_categories')->where('is_active', true)->get();
        return view('admin.news.edit', compact('news', 'categories'));
    }

    public function newsUpdate(Request $request, $id)
    {
        $news = DB::table('news')->where('id', $id)->first();
        abort_if(!$news, 404, 'News not found');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'news_category_id' => 'nullable|exists:news_categories,id',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'status' => 'required|string|in:DRAFT,PUBLISHED',
            'published_at' => 'nullable|date',
            'featured_image' => 'nullable|string',
        ]);

        DB::table('news')->where('id', $id)->update([
            'title' => $validated['title'],
            'slug' => \Str::slug($validated['title']) . '-' . rand(100, 999),
            'news_category_id' => $validated['news_category_id'] ?? null,
            'excerpt' => $validated['excerpt'] ?? '',
            'content' => $validated['content'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'PUBLISHED' ? ($validated['published_at'] ?? now()) : null,
            'featured_image' => $validated['featured_image'] ?: $news->featured_image,
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.news.index')->with('success', 'News updated successfully.');
    }

    public function newsDestroy($id)
    {
        DB::table('news')->where('id', $id)->delete();
        return redirect()->route('admin.news.index')->with('success', 'News deleted successfully.');
    }

    // Testimonials Management
    public function testimonialsIndex(Request $request)
    {
        $search = $request->query('search');
        $query = DB::table('testimonials');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%')
                  ->orWhere('role_title', 'like', '%' . $search . '%');
            });
        }
        $testimonials = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        return view('admin.testimonials.index', compact('testimonials', 'search'));
    }

    public function testimonialsCreate()
    {
        return view('admin.testimonials.create');
    }

    public function testimonialsStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'role_title' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'photo_url' => 'nullable|string',
            'testimonial_date' => 'required|date',
            'is_published' => 'nullable',
        ]);

        DB::table('testimonials')->insert([
            'name' => $validated['name'],
            'content' => $validated['content'],
            'rating' => $validated['rating'],
            'role_title' => $validated['role_title'] ?? '',
            'location' => $validated['location'] ?? '',
            'photo_url' => $validated['photo_url'] ?: 'https://i.pravatar.cc/150?u=' . urlencode($validated['name']),
            'testimonial_date' => $validated['testimonial_date'],
            'is_published' => $request->has('is_published'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial created successfully.');
    }

    public function testimonialsEdit($id)
    {
        $testimonial = DB::table('testimonials')->where('id', $id)->first();
        abort_if(!$testimonial, 404, 'Testimonial not found');
        return view('admin.testimonials.edit', compact('testimonial'));
    }

    public function testimonialsUpdate(Request $request, $id)
    {
        $testimonial = DB::table('testimonials')->where('id', $id)->first();
        abort_if(!$testimonial, 404, 'Testimonial not found');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'role_title' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'photo_url' => 'nullable|string',
            'testimonial_date' => 'required|date',
            'is_published' => 'nullable',
        ]);

        DB::table('testimonials')->where('id', $id)->update([
            'name' => $validated['name'],
            'content' => $validated['content'],
            'rating' => $validated['rating'],
            'role_title' => $validated['role_title'] ?? '',
            'location' => $validated['location'] ?? '',
            'photo_url' => $validated['photo_url'] ?: $testimonial->photo_url,
            'testimonial_date' => $validated['testimonial_date'],
            'is_published' => $request->has('is_published'),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial updated successfully.');
    }

    public function testimonialsDestroy($id)
    {
        DB::table('testimonials')->where('id', $id)->delete();
        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial deleted successfully.');
    }

    // Settings (GitHub Actions APK artifact download links)
    public function settingsIndex()
    {
        $links = [];
        if (DB::getSchemaBuilder()->hasTable('app_settings')) {
            $row = DB::table('app_settings')->where('setting_key', 'apk_download_links')->first();
            if ($row && $row->setting_value) {
                $links = json_decode($row->setting_value, true) ?? [];
            }
        }
        $trialEnds = '2026-12-31';
        $trialActive = now()->lte(\Carbon\Carbon::parse($trialEnds));
        $s = function (string $key, string $default = ''): string {
            if (!DB::getSchemaBuilder()->hasTable('app_settings')) {
                return $default;
            }
            return DB::table('app_settings')->where('setting_key', $key)->value('setting_value') ?? $default;
        };
        $settings = [
            'ride_cost_per_km' => $s('ride_cost_per_km', '5000'),
            'ride_base_fare' => $s('ride_base_fare', '10000'),
            'food_commission_pct' => $s('food_commission_pct', '20'),
            'admin_ride_commission_enabled' => $s('admin_ride_commission_enabled', 'OFF'),
            'admin_ride_commission_amount' => $s('admin_ride_commission_amount', '2000'),
            'admin_food_commission_enabled' => $s('admin_food_commission_enabled', 'OFF'),
            'admin_food_commission_amount' => $s('admin_food_commission_amount', '3000'),
            'admin_shop_commission_enabled' => $s('admin_shop_commission_enabled', 'OFF'),
            'admin_shop_commission_amount' => $s('admin_shop_commission_amount', '5000'),
            'apk_download_url_customer' => $s('apk_download_url_customer', 'https://gride.web.id/apk/customer.apk'),
            'apk_download_url_driver' => $s('apk_download_url_driver', 'https://gride.web.id/apk/driver.apk'),
            'apk_download_url_merchant' => $s('apk_download_url_merchant', 'https://gride.web.id/apk/merchant.apk'),
        ];
        return view('admin.settings.index', compact('links', 'trialEnds', 'trialActive', 'settings'));
    }

    /**
     * Simpan setting tarif & komisi. Hanya admin.
     * Input nominal hanya menerima angka; 'Rp 10.000' dinormalisasi menjadi 10000.
     */
    public function settingsUpdate(Request $request)
    {
        $numeric = function ($v) {
            if (!is_string($v) && !is_numeric($v)) {
                return null;
            }
            $clean = preg_replace('/[^0-9]/', '', (string) $v);
            return $clean !== '' ? (int) $clean : 0;
        };

        $numericPct = function ($v) {
            $clean = preg_replace('/[^0-9.]/', '', (string) $v);
            $n = (float) $clean;
            return is_finite($n) && $n >= 0 && $n <= 100 ? $n : 0;
        };

        $rules = [
            'ride_cost_per_km' => 'nullable|string',
            'ride_base_fare' => 'nullable|string',
            'food_commission_pct' => 'nullable|string',
            'admin_ride_commission_enabled' => 'nullable|string|in:ON,OFF',
            'admin_ride_commission_amount' => 'nullable|string',
            'admin_food_commission_enabled' => 'nullable|string|in:ON,OFF',
            'admin_food_commission_amount' => 'nullable|string',
            'admin_shop_commission_enabled' => 'nullable|string|in:ON,OFF',
            'admin_shop_commission_amount' => 'nullable|string',
            'apk_download_url_customer' => 'nullable|string|url',
            'apk_download_url_driver' => 'nullable|string|url',
            'apk_download_url_merchant' => 'nullable|string|url',
        ];

        $data = $request->validate($rules);

        $pairs = [
            'ride_cost_per_km' => (string) $numeric($data['ride_cost_per_km'] ?? 5000),
            'ride_base_fare' => (string) $numeric($data['ride_base_fare'] ?? 10000),
            'food_commission_pct' => (string) $numericPct($data['food_commission_pct'] ?? 20),
            'admin_ride_commission_enabled' => in_array($data['admin_ride_commission_enabled'] ?? 'OFF', ['ON', 'OFF']) ? ($data['admin_ride_commission_enabled'] ?? 'OFF') : 'OFF',
            'admin_ride_commission_amount' => (string) $numeric($data['admin_ride_commission_amount'] ?? 2000),
            'admin_food_commission_enabled' => in_array($data['admin_food_commission_enabled'] ?? 'OFF', ['ON', 'OFF']) ? ($data['admin_food_commission_enabled'] ?? 'OFF') : 'OFF',
            'admin_food_commission_amount' => (string) $numeric($data['admin_food_commission_amount'] ?? 3000),
            'admin_shop_commission_enabled' => in_array($data['admin_shop_commission_enabled'] ?? 'OFF', ['ON', 'OFF']) ? ($data['admin_shop_commission_enabled'] ?? 'OFF') : 'OFF',
            'admin_shop_commission_amount' => (string) $numeric($data['admin_shop_commission_amount'] ?? 5000),
            'apk_download_url_customer' => trim($data['apk_download_url_customer'] ?? 'https://gride.web.id/apk/customer.apk'),
            'apk_download_url_driver' => trim($data['apk_download_url_driver'] ?? 'https://gride.web.id/apk/driver.apk'),
            'apk_download_url_merchant' => trim($data['apk_download_url_merchant'] ?? 'https://gride.web.id/apk/merchant.apk'),
        ];

        foreach ($pairs as $key => $value) {
            DB::table('app_settings')->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => $value, 'updated_at' => now()]
            );
        }

        return back()->with('success', 'Pengaturan tarif & komisi berhasil disimpan.');
    }

    public function settingsRefreshLinks()
    {
        $links = $this->fetchGitHubApkArtifacts();
        if (DB::getSchemaBuilder()->hasTable('app_settings')) {
            DB::table('app_settings')->updateOrInsert(
                ['setting_key' => 'apk_download_links'],
                ['setting_value' => json_encode($links), 'updated_at' => now()]
            );
        }
        // APK download sudah di-host di gride.web.id/apk/; tautan GitHub dipakai
        // hanya sebagai catatan historis build.
        return back()->with('success', $links ? 'Build links dari GitHub Actions diperbarui.' : 'Belum ada build terbaru.');
    }

    private function fetchGitHubApkArtifacts()
    {
        try {
            $ch = curl_init('https://api.github.com/repos/aksisoftsby/gridewithmanus/actions/artifacts?per_page=30');
            $headers = ['Accept: application/vnd.github+json', 'User-Agent: Gride-SuperApp'];
            $token = env('GITHUB_TOKEN');
            if ($token) {
                $headers[] = 'Authorization: Bearer ' . $token;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 10,
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($body, true);
            if (!isset($data['artifacts'])) {
                return [];
            }
            $apps = ['customer-app-apk' => 'Customer', 'driver-app-apk' => 'Driver', 'merchant-app-apk' => 'Merchant'];
            $latest = [];
            foreach ($apps as $name => $label) {
                $match = null;
                foreach ($data['artifacts'] as $art) {
                    if ($art['name'] === $name && ($match === null || $art['created_at'] > $match['created_at'])) {
                        $match = $art;
                    }
                }
                if ($match) {
                    $latest[] = [
                        'app' => $label,
                        'name' => $name,
                        'url' => $match['archive_download_url'],
                        'created_at' => $match['created_at'],
                        'size_mb' => round($match['size_in_bytes'] / 1024 / 1024, 1),
                        'workflow_run_id' => $match['workflow_run_id'] ?? null,
                    ];
                }
            }
            return $latest;
        } catch (\Throwable $e) {
            \Log::error('fetchGitHubApkArtifacts failed: '.$e->getMessage());
            return [];
        }
    }

    // API Documentation (admin-only)
    public function apiDocs()
    {
        return view('api.docs');
    }

    // =====================================================================
    // IKLAN GRATIS — Manajemen penuh di admin: list, tambah, edit, hapus,
    // serta tambah kategori iklan gratis.
    // =====================================================================

    public function iklanGratisIndex(Request $request)
    {
        $search = $request->query('search');
        $cat = $request->query('category_id');
        $status = $request->query('status');
        $query = DB::table('iklan_gratis')
            ->leftJoin('iklan_gratis_categories', 'iklan_gratis.category_id', '=', 'iklan_gratis_categories.id')
            ->select('iklan_gratis.*', 'iklan_gratis_categories.name as category_name');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('iklan_gratis.title', 'like', '%' . $search . '%')
                  ->orWhere('iklan_gratis.description', 'like', '%' . $search . '%');
            });
        }
        if ($cat) {
            $query->where('iklan_gratis.category_id', $cat);
        }
        if ($status) {
            $query->where('iklan_gratis.status', $status);
        }
        $iklan = $query->orderBy('iklan_gratis.created_at', 'desc')->paginate(10)->withQueryString();
        $categories = DB::table('iklan_gratis_categories')->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.iklan.index', compact('iklan', 'categories', 'search', 'cat', 'status'));
    }

    public function iklanGratisCreate()
    {
        $categories = DB::table('iklan_gratis_categories')->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.iklan.create', compact('categories'));
    }

    public function iklanGratisStore(Request $request)
    {
        $photos = array_values(array_filter(array_slice((array) ($request->input('photos', []) ?? []), 0, 10)));
        $expiredAt = $request->input('expired_months')
            ? now()->addMonths(max(1, min(12, (int) $request->input('expired_months'))))->format('Y-m-d H:i:s')
            : now()->addMonths(1)->format('Y-m-d H:i:s');

        DB::table('iklan_gratis')->insert([
            'user_id' => $request->input('user_id') ?? 0,
            'category_id' => $request->filled('category_id') ? (int) $request->input('category_id') : null,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'price' => (float) ($request->input('price', 0) ?? 0),
            'photos' => json_encode($photos),
            'contact_name' => $request->input('contact_name'),
            'contact_phone' => $request->input('contact_phone'),
            'city' => $request->input('city'),
            'status' => $request->input('status', 'ACTIVE'),
            'expired_at' => $expiredAt,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('admin.iklan.index')->with('success', 'Iklan berhasil ditambahkan.');
    }

    public function iklanGratisEdit($id)
    {
        $iklan = DB::table('iklan_gratis')->where('id', $id)->first();
        abort_if(!$iklan, 404, 'Iklan tidak ditemukan');
        $categories = DB::table('iklan_gratis_categories')->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.iklan.edit', compact('iklan', 'categories'));
    }

    public function iklanGratisUpdate(Request $request, $id)
    {
        $iklan = DB::table('iklan_gratis')->where('id', $id)->first();
        abort_if(!$iklan, 404, 'Iklan tidak ditemukan');

        $photos = $request->input('photos');
        if ($photos === null) {
            $photos = json_decode($iklan->photos ?? '[]', true) ?? [];
        } else {
            $photos = array_values(array_filter(array_slice((array) $photos, 0, 10)));
        }
        $expiredAt = $request->input('expired_months')
            ? now()->addMonths(max(1, min(12, (int) $request->input('expired_months'))))->format('Y-m-d H:i:s')
            : $iklan->expired_at;

        DB::table('iklan_gratis')->where('id', $id)->update([
            'user_id' => $request->input('user_id', $iklan->user_id),
            'category_id' => $request->filled('category_id') ? (int) $request->input('category_id') : $iklan->category_id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'price' => (float) ($request->input('price', 0) ?? 0),
            'photos' => json_encode($photos),
            'contact_name' => $request->input('contact_name'),
            'contact_phone' => $request->input('contact_phone'),
            'city' => $request->input('city'),
            'status' => $request->input('status', $iklan->status),
            'expired_at' => $expiredAt,
            'updated_at' => now(),
        ]);
        return redirect()->route('admin.iklan.index')->with('success', 'Iklan berhasil diperbarui.');
    }

    public function iklanGratisDestroy($id)
    {
        DB::table('iklan_gratis')->where('id', $id)->delete();
        return redirect()->route('admin.iklan.index')->with('success', 'Iklan berhasil dihapus.');
    }

    // ---- Kategori Iklan Gratis ----
    public function iklanKategoriIndex()
    {
        $categories = DB::table('iklan_gratis_categories')->orderBy('sort_order')->orderBy('name')->paginate(20);
        return view('admin.iklan.kategori', compact('categories'));
    }

    public function iklanKategoriStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);
        $slug = \Str::slug($validated['name']);
        $exists = DB::table('iklan_gratis_categories')->where('slug', $slug)->exists();
        if ($exists) {
            $slug .= '-' . substr(md5(time()), 0, 4);
        }
        DB::table('iklan_gratis_categories')->insert([
            'name' => $validated['name'],
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => (int) DB::table('iklan_gratis_categories')->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->route('admin.iklan.kategori')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function iklanKategoriUpdate(Request $request, $id)
    {
        $cat = DB::table('iklan_gratis_categories')->where('id', $id)->first();
        abort_if(!$cat, 404, 'Kategori tidak ditemukan');
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'is_active' => 'nullable',
        ]);
        DB::table('iklan_gratis_categories')->where('id', $id)->update([
            'name' => $validated['name'],
            'slug' => \Str::slug($validated['name']) ?: $cat->slug,
            'is_active' => $request->has('is_active'),
            'updated_at' => now(),
        ]);
        return redirect()->route('admin.iklan.kategori')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function iklanKategoriDestroy($id)
    {
        DB::table('iklan_gratis_categories')->where('id', $id)->delete();
        return redirect()->route('admin.iklan.kategori')->with('success', 'Kategori berhasil dihapus.');
    }

}
