<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@superapp.com'],
            [
                'full_name' => 'Super Admin',
                'name' => 'Super Admin',
                'password_hash' => Hash::make('password'),
                'password' => Hash::make('password'),
                'role' => 'ADMIN',
                'status' => 'ACTIVE',
                'phone' => '081234567890',
            ]
        );

        // Create Merchant Owner
        $merchantOwner = User::firstOrCreate(
            ['email' => 'merchant@superapp.com'],
            [
                'full_name' => 'Budi Merchant',
                'name' => 'Budi Merchant',
                'password_hash' => Hash::make('password'),
                'password' => Hash::make('password'),
                'role' => 'MEMBER',
                'status' => 'ACTIVE',
                'phone' => '081234567891',
            ]
        );

        // Create Customer
        $customer = User::firstOrCreate(
            ['email' => 'customer@superapp.com'],
            [
                'full_name' => 'Siti Customer',
                'name' => 'Siti Customer',
                'password_hash' => Hash::make('password'),
                'password' => Hash::make('password'),
                'role' => 'MEMBER',
                'status' => 'ACTIVE',
                'phone' => '081234567892',
            ]
        );

        // Create Driver User & Driver Profile
        $driverUser = User::firstOrCreate(
            ['email' => 'driver@superapp.com'],
            [
                'full_name' => 'Joko Driver',
                'name' => 'Joko Driver',
                'password_hash' => Hash::make('password'),
                'password' => Hash::make('password'),
                'role' => 'MEMBER',
                'status' => 'ACTIVE',
                'phone' => '081234567893',
            ]
        );

        $driverId = DB::table('drivers')->insertGetId([
            'user_id' => $driverUser->id,
            'status' => 'ONLINE',
            'is_verified' => true,
            'rating' => 4.95,
            'total_trips' => 250,
            'current_lat' => -6.200000,
            'current_lng' => 106.816666,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create Categories
        $catFood = DB::table('categories')->insertGetId([
            'name' => 'Restoran & Makanan',
            'slug' => 'restoran-makanan',
            'type' => 'FOOD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $catMart = DB::table('categories')->insertGetId([
            'name' => 'Sembako & Minimarket',
            'slug' => 'sembako-minimarket',
            'type' => 'MART',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create Merchants
        $merchant1 = DB::table('merchants')->insertGetId([
            'owner_id' => $merchantOwner->id,
            'type' => 'FOOD',
            'name' => 'Restoran Padang Sederhana',
            'slug' => 'restoran-padang-sederhana',
            'description' => 'Autentik masakan Padang asli dengan rendang dan gulai pilihan.',
            'logo_url' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=500',
            'banner_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1000',
            'phone' => '0215551234',
            'address_line' => 'Jl. Sudirman No. 45, Jakarta Selatan',
            'city' => 'Jakarta',
            'latitude' => -6.220000,
            'longitude' => 106.820000,
            'status' => 'ACTIVE',
            'is_open' => true,
            'rating' => 4.9,
            'total_orders' => 1420,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $merchant2 = DB::table('merchants')->insertGetId([
            'owner_id' => $merchantOwner->id,
            'type' => 'MART',
            'name' => 'SuperMart Express 24 Jam',
            'slug' => 'supermart-express-24-jam',
            'description' => 'Kebutuhan harian, sembako, minuman, dan snack lengkap cepat sampai.',
            'logo_url' => 'https://images.unsplash.com/photo-1578916171728-46686eac8d58?w=500',
            'banner_url' => 'https://images.unsplash.com/photo-1534723452862-4c874018d66d?w=1000',
            'phone' => '0215559876',
            'address_line' => 'Jl. MH Thamrin No. 10, Jakarta Pusat',
            'city' => 'Jakarta',
            'latitude' => -6.190000,
            'longitude' => 106.822000,
            'status' => 'ACTIVE',
            'is_open' => true,
            'rating' => 4.8,
            'total_orders' => 890,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create Menu Items / Products
        DB::table('menu_items')->insert([
            [
                'merchant_id' => $merchant1,
                'name' => 'Paket Nasi Rendang Special',
                'slug' => 'paket-nasi-rendang-special',
                'description' => 'Nasi putih hangat dengan rendang sapi empuk, bumbu balado, dan daun singkong.',
                'price' => 35000.00,
                'image_url' => 'https://images.unsplash.com/photo-1569050471253-73c368d1469e?w=500',
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'merchant_id' => $merchant1,
                'name' => 'Ayam Goreng Bumbu Lengkuas',
                'slug' => 'ayam-goreng-bumbu-lengkuas',
                'description' => 'Ayam kampung goreng gurih bertabur serundeng lengkuas renyah.',
                'price' => 28000.00,
                'image_url' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?w=500',
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'merchant_id' => $merchant2,
                'name' => 'Minyak Goreng Premium 2L',
                'slug' => 'minyak-goreng-premium-2l',
                'description' => 'Minyak goreng kelapa sawit pilihan jernih dan higienis.',
                'price' => 38000.00,
                'image_url' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=500',
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'merchant_id' => $merchant2,
                'name' => 'Beras Pulen Super 5Kg',
                'slug' => 'beras-pulen-super-5kg',
                'description' => 'Beras putih kualitas premium pulen tanpa pemutih buatan.',
                'price' => 69000.00,
                'image_url' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500',
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Create Promos
        DB::table('promos')->insert([
            [
                'code' => 'GRIDEHEMAT50',
                'title' => 'Diskon 50% Makanan & Belanja',
                'discount_type' => 'PERCENTAGE',
                'discount_value' => 50.00,
                'min_purchase' => 30000.00,
                'starts_at' => now(),
                'ends_at' => now()->addDays(30),
                'expires_at' => now()->addDays(30),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ONGKIRKILAT',
                'title' => 'Potongan Ongkir Rp 15.000',
                'discount_type' => 'FIXED',
                'discount_value' => 15000.00,
                'min_purchase' => 20000.00,
                'starts_at' => now(),
                'ends_at' => now()->addDays(15),
                'expires_at' => now()->addDays(15),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Create Sample Orders
        $menuItem = DB::table('menu_items')->first();
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $customer->id,
            'merchant_id' => $merchant1,
            'driver_id' => 1,
            'order_number' => 'ORD-2026-0001',
            'status' => 'COMPLETED',
            'subtotal' => 35000.00,
            'delivery_fee' => 10000.00,
            'discount_amount' => 5000.00,
            'total_amount' => 40000.00,
            'delivery_address' => 'Jl. Menteng Raya No. 12, Jakarta Pusat',
            'recipient_name' => 'Siti Customer',
            'recipient_phone' => '081234567892',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'unit_price' => 35000.00,
            'subtotal' => 35000.00,
            'notes' => 'Kurangin pedas sedikit ya bang',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
