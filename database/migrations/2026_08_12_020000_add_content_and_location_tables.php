<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // News categories & news (may already exist from full schema migration)
        if (!Schema::hasTable('news_categories')) {
            Schema::create('news_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('news_category_id')->nullable();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('excerpt')->nullable();
                $table->text('content');
                $table->text('featured_image')->nullable();
                $table->string('status')->default('DRAFT');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('testimonials')) {
            Schema::create('testimonials', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->text('photo_url')->nullable();
                $table->text('content');
                $table->smallInteger('rating')->default(5);
                $table->string('role_title')->nullable();
                $table->string('location')->nullable();
                $table->date('testimonial_date');
                $table->boolean('is_published')->default(true);
                $table->timestamps();
            });
        }

        // Driver location history (for check-in / tracking)
        if (!Schema::hasTable('driver_locations')) {
            Schema::create('driver_locations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('driver_id');
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->decimal('speed', 5, 2)->nullable();
                $table->decimal('heading', 5, 2)->nullable();
                $table->timestamp('recorded_at')->useCurrent();
            });
        }

        // Merchant location (latitude/longitude may already exist; add if missing)
        Schema::table('merchants', function (Blueprint $table) {
            if (!Schema::hasColumn('merchants', 'latitude')) {
                $table->decimal('latitude', 10, 8)->default(0);
            }
            if (!Schema::hasColumn('merchants', 'longitude')) {
                $table->decimal('longitude', 11, 8)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('news');
        Schema::dropIfExists('news_categories');
    }
};
