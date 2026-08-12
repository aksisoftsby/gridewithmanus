<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot kolom tarif & komisi per transaksi.
     * Nilai diambil saat order dibuat, sehingga perubahan setting admin
     * tidak mengubah nilai transaksi lama.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('ride_distance_km', 10, 2)->nullable()->after('dropoff_lng');
            $table->decimal('cost_per_km_snapshot', 10, 2)->nullable()->after('ride_distance_km');
            $table->decimal('admin_commission_snapshot', 12, 2)->nullable()->after('cost_per_km_snapshot');
            $table->decimal('merchant_commission_snapshot', 12, 2)->nullable()->after('admin_commission_snapshot');
            $table->decimal('merchant_commission_pct_snapshot', 5, 2)->nullable()->after('merchant_commission_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'ride_distance_km',
                'cost_per_km_snapshot',
                'admin_commission_snapshot',
                'merchant_commission_snapshot',
                'merchant_commission_pct_snapshot',
            ]);
        });
    }
};
