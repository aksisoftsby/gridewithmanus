<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orders: add GPS delivery (antar-jemput) columns for the GPS delivery feature.
        // Existing orders columns: user_id, merchant_id, driver_id, order_number, status,
        // subtotal, delivery_fee, discount_amount, total_amount, delivery_address,
        // recipient_name, recipient_phone.
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_type')) {
                $table->string('order_type', 20)->default('FOOD'); // FOOD, MART, SHOP, DELIVERY
            }
            if (!Schema::hasColumn('orders', 'pickup_address')) {
                $table->text('pickup_address')->nullable();
            }
            if (!Schema::hasColumn('orders', 'pickup_lat')) {
                $table->decimal('pickup_lat', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('orders', 'pickup_lng')) {
                $table->decimal('pickup_lng', 11, 8)->nullable();
            }
            if (!Schema::hasColumn('orders', 'dropoff_address')) {
                $table->text('dropoff_address')->nullable();
            }
            if (!Schema::hasColumn('orders', 'dropoff_lat')) {
                $table->decimal('dropoff_lat', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('orders', 'dropoff_lng')) {
                $table->decimal('dropoff_lng', 11, 8)->nullable();
            }
            if (!Schema::hasColumn('orders', 'note')) {
                $table->text('note')->nullable();
            }
        });

        // Drivers: add last_location_at timestamp for location check-in tracking
        Schema::table('drivers', function (Blueprint $table) {
            if (!Schema::hasColumn('drivers', 'last_location_at')) {
                $table->timestamp('last_location_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['order_type', 'pickup_address', 'pickup_lat', 'pickup_lng', 'dropoff_address', 'dropoff_lat', 'dropoff_lng', 'note'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
