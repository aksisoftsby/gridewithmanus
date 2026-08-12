<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 50)->default('CUSTOMER');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status', 50)->default('ACTIVE');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable();
            }
            if (!Schema::hasColumn('users', 'avatar_url')) {
                $table->text('avatar_url')->nullable();
            }
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('FOOD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('type')->default('FOOD');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('logo_url')->nullable();
            $table->text('banner_url')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address_line');
            $table->string('city', 100);
            $table->decimal('latitude', 10, 8)->default(-6.200000);
            $table->decimal('longitude', 11, 8)->default(106.816666);
            $table->string('status', 50)->default('ACTIVE');
            $table->boolean('is_open')->default(true);
            $table->decimal('rating', 3, 2)->default(4.80);
            $table->integer('total_orders')->default(0);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->text('image_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('status', 50)->default('ONLINE');
            $table->boolean('is_verified')->default(true);
            $table->decimal('rating', 3, 2)->default(4.90);
            $table->integer('total_trips')->default(120);
            $table->decimal('current_lat', 10, 8)->default(-6.200000);
            $table->decimal('current_lng', 11, 8)->default(106.816666);
            $table->timestamps();
        });

        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->string('discount_type')->default('PERCENTAGE');
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_purchase', 12, 2)->default(0);
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained('merchants')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            $table->string('order_number')->unique();
            $table->string('status', 50)->default('PENDING');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('delivery_fee', 10, 2)->default(10000);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->text('delivery_address');
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('menu_item_id')->constrained('menu_items')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('promos');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('merchants');
        Schema::dropIfExists('categories');
    }
};
