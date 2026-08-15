<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Settlement wallet saat order/ride selesai.
 * Atomic DB transaction: debit customer, credit driver/merchant earning,
 * komisi platform (opsional), update payment_status.
 * Semua mutasi dicatat di wallet_transactions (single source of truth).
 */
class WalletSettlement
{
    private static function ensureTables(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('wallet_transactions')) {
            DB::statement('CREATE TABLE IF NOT EXISTS wallet_transactions (
                id BIGSERIAL PRIMARY KEY, wallet_id BIGINT NOT NULL,
                type VARCHAR(30) NOT NULL, amount NUMERIC(15,2) DEFAULT 0,
                balance_before NUMERIC(15,2) DEFAULT 0, balance_after NUMERIC(15,2) DEFAULT 0,
                status VARCHAR(30) DEFAULT \'PENDING\', method VARCHAR(50),
                reference_no VARCHAR(100), reference_id BIGINT, reference_type VARCHAR(30),
                user_id BIGINT, idempotency_key VARCHAR(100), description TEXT,
                failure_reason TEXT, expired_at TIMESTAMPTZ,
                created_at TIMESTAMPTZ DEFAULT NOW(), updated_at TIMESTAMPTZ DEFAULT NOW()
            )');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_wtx_wallet ON wallet_transactions(wallet_id)');
        }
        foreach (['direction', 'is_earning', 'user_id', 'reference_type'] as $col) {
            if (!DB::getSchemaBuilder()->hasColumn('wallet_transactions', $col)) {
                if ($col === 'direction') {
                    DB::statement('ALTER TABLE wallet_transactions ADD COLUMN IF NOT EXISTS direction VARCHAR(20) NOT NULL DEFAULT \'CREDIT\'');
                } elseif ($col === 'is_earning') {
                    DB::statement('ALTER TABLE wallet_transactions ADD COLUMN IF NOT EXISTS is_earning BOOLEAN NOT NULL DEFAULT FALSE');
                } else {
                    DB::statement('ALTER TABLE wallet_transactions ADD COLUMN IF NOT EXISTS ' . $col . ($col === 'user_id' ? ' BIGINT' : ' VARCHAR(30)'));
                }
            }
        }
    }

    /** Ambil wallet user, autocreate bila belum ada. */
    private static function getWallet(int $userId): ?object
    {
        $wallet = DB::table('wallets')->where('user_id', $userId)->first();
        if (!$wallet) {
            DB::table('wallets')->insert([
                'user_id' => $userId, 'balance' => 0, 'points' => 0,
                'status' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $wallet = DB::table('wallets')->where('user_id', $userId)->first();
        }
        return $wallet;
    }

    /**
     * Catat 1 mutasi wallet secara atomik terhadap baris wallet (lockForUpdate).
     * Idempoten via [wallet_id, type, reference_id].
     */
    private static function postTx(int $walletId, int $userId, string $type, float $amount, string $direction, bool $isEarning, ?int $referenceId, ?string $referenceType, string $description, ?string $referenceNo = null): ?object
    {
        if ($amount <= 0) {
            return null;
        }
        if ($referenceId !== null) {
            $dup = DB::table('wallet_transactions')
                ->where('wallet_id', $walletId)
                ->where('type', $type)
                ->where('reference_id', $referenceId)
                ->first();
            if ($dup) {
                return $dup;
            }
        }
        $wallet = DB::table('wallets')->where('id', $walletId)->lockForUpdate()->first();
        if (!$wallet) {
            return null;
        }
        $before = (float) $wallet->balance;
        $credit = strtoupper($direction) === 'CREDIT';
        $after = round($credit ? $before + $amount : $before - $amount, 2);
        if (!$credit && $after < -0.005) {
            return null; // saldo tidak cukup — caller handle
        }
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $walletId,
            'type' => $type,
            'direction' => $credit ? 'CREDIT' : 'DEBIT',
            'is_earning' => $isEarning,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'status' => 'SUCCESS',
            'reference_no' => $referenceNo,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'user_id' => $userId,
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('wallets')->where('id', $walletId)
            ->update(['balance' => DB::raw($credit ? 'balance + ' . $amount : 'balance - ' . $amount), 'updated_at' => now()]);
        return DB::table('wallet_transactions')->where('wallet_id', $walletId)
            ->where('type', $type)->where('reference_id', $referenceId)->first();
    }

    /**
     * Settlement order (AdminController ordersUpdateStatus → status COMPLETED).
     * - Customer: DEBIT total (RIDE_PAYMENT / ORDER_PAYMENT) bila saldo cukup
     * - Driver: CREDIT driver_net (RIDE_EARNING/DELIVERY_EARNING, is_earning=1)
     * - Merchant: CREDIT merchant_net (ORDER_EARNING, is_earning=1)
     */
    public static function settleOrder(int $orderId): void
    {
        self::ensureTables();
        $order = DB::table('orders')->where('id', $orderId)->first();
        if (!$order) {
            return;
        }
        $total = (float) ($order->total_amount ?? 0);
        if ($total <= 0) {
            $total = max((float) ($order->subtotal ?? 0) + (float) ($order->delivery_fee ?? 0) + (float) ($order->admin_commission_snapshot ?? 0) - (float) ($order->discount_amount ?? 0), 0);
        }

        DB::transaction(function () use ($order, $total) {
            // Customer: debit pembayaran (bila saldo cukup; bila tidak, payment_status tetap UNPAID/COD)
            if ($order->customer_id) {
                $cw = self::getWallet((int) $order->customer_id);
                if ($cw && (float) $cw->balance >= $total) {
                    $type = in_array($order->order_type ?? '', ['RIDE', 'DELIVERY']) ? 'RIDE_PAYMENT' : 'ORDER_PAYMENT';
                    self::postTx((int) $cw->id, (int) $order->customer_id, $type, $total, 'DEBIT', false, (int) $order->id, 'ORDER', 'Pembayaran order ' . ($order->order_number ?? ''), null);
                    DB::table('orders')->where('id', $order->id)->update(['payment_status' => 'PAID', 'updated_at' => now()]);
                }
            }
            // Driver: credit penghasilan
            if ($order->driver_id && in_array($order->order_type ?? '', ['RIDE', 'DELIVERY'])) {
                $driver = DB::table('drivers')->where('id', $order->driver_id)->first();
                if ($driver && $driver->user_id) {
                    $dw = self::getWallet((int) $driver->user_id);
                    if ($dw) {
                        $driverNet = max((float) ($order->delivery_fee ?? 0) - (float) ($order->admin_commission_snapshot ?? 0), 0);
                        if ($driverNet > 0) {
                            $type = $order->order_type === 'RIDE' ? 'RIDE_EARNING' : 'DELIVERY_EARNING';
                            self::postTx((int) $dw->id, (int) $driver->user_id, $type, $driverNet, 'CREDIT', true, (int) $order->id, 'ORDER', 'Penghasilan order ' . ($order->order_number ?? ''), null);
                        }
                    }
                }
            }
            // Merchant: credit penghasilan
            if ($order->merchant_id && in_array($order->order_type ?? '', ['FOOD', 'MART', 'SHOP'])) {
                $merchant = DB::table('merchants')->where('id', $order->merchant_id)->first();
                if ($merchant && $merchant->owner_id) {
                    $mw = self::getWallet((int) $merchant->owner_id);
                    if ($mw) {
                        $merchantNet = max((float) ($order->subtotal ?? 0) - (float) ($order->merchant_commission_snapshot ?? 0), 0);
                        if ($merchantNet > 0) {
                            self::postTx((int) $mw->id, (int) $merchant->owner_id, 'ORDER_EARNING', $merchantNet, 'CREDIT', true, (int) $order->id, 'ORDER', 'Penghasilan order ' . ($order->order_number ?? ''), null);
                        }
                    }
                }
            }
        });
    }
}
