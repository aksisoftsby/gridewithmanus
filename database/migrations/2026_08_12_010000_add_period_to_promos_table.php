<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable()->after('min_purchase');
            $table->dateTime('ends_at')->nullable()->after('starts_at');
        });

        // Backfill existing promos: period = now (or created_at) until expires_at
        DB::table('promos')->whereNull('starts_at')->update([
            'starts_at' => DB::raw('COALESCE(created_at, NOW())'),
            'ends_at' => DB::raw('COALESCE(expires_at, NOW() + INTERVAL \'30 days\')'),
        ]);
    }

    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};
