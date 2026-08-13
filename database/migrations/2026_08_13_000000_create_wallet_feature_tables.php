<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modul Wallet GridePay (Customer/Driver/Merchant — satu wallet per user).
     * Menambah tabel wallet_transactions, user_payment_methods (rekening tersimpan),
     * dan kolom wallet_pin pada users. Aman dijalankan berulang (guard via hasTable/hasColumn).
     */
    public function up(): void
    {
        // --- user_payment_methods: rekening bank / metode pembayaran tersimpan milik user ---
        if (!Schema::hasTable('user_payment_methods')) {
            Schema::create('user_payment_methods', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('provider', 50)->default('BANK'); // BANK, EWALLET
                $table->string('bank_name', 100)->nullable();
                $table->string('account_number', 100)->nullable();
                $table->string('account_holder', 255)->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        // --- wallet_transactions: mutasi saldo GridePay ---
        if (!Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('wallet_id');
                $table->string('type', 30); // TOPUP, WITHDRAW, PAYMENT, REFUND, BONUS
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('balance_before', 15, 2)->default(0);
                $table->decimal('balance_after', 15, 2)->default(0);
                $table->string('status', 30)->default('PENDING'); // PENDING, SUCCESS, FAILED, EXPIRED
                $table->string('method', 50)->nullable(); // VA_BANK, EWALLET, QRIS, CARD, BANK_TRANSFER
                $table->string('reference_no', 100)->nullable(); // nomor VA / kode pembayaran
                $table->unsignedBigInteger('reference_id')->nullable(); // rekening_id utk withdraw
                $table->string('idempotency_key', 100)->nullable();
                $table->text('description')->nullable();
                $table->text('failure_reason')->nullable();
                $table->timestamp('expired_at')->nullable();
                $table->timestamps();
            });
        }

        // --- kolom wallet PIN pada users ---
        if (!Schema::hasColumn('users', 'wallet_pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('wallet_pin', 255)->nullable();
                $table->integer('wallet_pin_attempts')->default(0);
                $table->timestamp('wallet_locked_until')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('user_payment_methods');
        if (Schema::hasColumn('users', 'wallet_pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['wallet_pin', 'wallet_pin_attempts', 'wallet_locked_until']);
            });
        }
    }
};
