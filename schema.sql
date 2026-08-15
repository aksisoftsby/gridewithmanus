-- ============================================================================
-- MIGRASI TAMBAHAN (2026-08-12)
-- Tabel driver_wallets & driver_vehicles belum ada di production PostgreSQL.
-- Karena production menggunakan BIGINT (auto-increment) untuk kolom id,
-- tabel baru dibuat dengan BIGSERIAL agar cocok dengan FK production:
--   CREATE TABLE IF NOT EXISTS driver_wallets (
--     id BIGSERIAL PRIMARY KEY,
--     driver_id BIGINT UNIQUE NOT NULL REFERENCES drivers(id) ON DELETE CASCADE,
--     balance DECIMAL(15,2) DEFAULT 0,
--     pending_balance DECIMAL(15,2) DEFAULT 0,
--     created_at TIMESTAMPTZ DEFAULT NOW(),
--     updated_at TIMESTAMPTZ DEFAULT NOW()
--   );
--   CREATE TABLE IF NOT EXISTS driver_vehicles (
--     id BIGSERIAL PRIMARY KEY,
--     driver_id BIGINT NOT NULL REFERENCES drivers(id) ON DELETE CASCADE,
--     vehicle_type VARCHAR(20) DEFAULT 'MOTOR',
--     brand VARCHAR(100),
--     model VARCHAR(100),
--     plate_number VARCHAR(20),
--     is_active BOOLEAN DEFAULT TRUE,
--     created_at TIMESTAMPTZ DEFAULT NOW(),
--     updated_at TIMESTAMPTZ DEFAULT NOW()
--   );
-- API baru: POST /api/register-driver, GET /api/driver/me, GET /api/driver/earnings
-- ============================================================================

-- ============================================================================
-- SUPER APP DATABASE SCHEMA (Grab-Like SuperApp Ecosystem)
-- Project     : Laravel SuperApp (Customer, Driver, Merchant, Admin)
-- Framework   : Laravel 10 / 11
-- Database    : PostgreSQL 15+ with PostGIS & pgcrypto
--
-- Dokumentasi schema ini disusun berdasarkan:
--   1. Laravel migrations (database/migrations/*)
--   2. Eloquent Models (app/Models/*)
--   3. Query database aktual (DB::table, DB::raw, dll.)
--   4. Seeders & Factories (database/seeders/*, database/factories/*)
--
-- Struktur Modul:
--   1. Authentication & Users   : users, otp_verifications, user_addresses, sessions, personal_access_tokens
--   2. Driver & Kurir           : drivers, driver_vehicles, driver_documents, driver_locations, driver_wallets
--   3. Merchant & Katalog       : categories, merchants, merchant_categories, merchant_hours, merchant_photos
--   4. GrabFood (Menu Resto)    : menu_categories, menu_items, menu_options, menu_option_values, menu_addons
--   5. GrabMart/Shop (Produk)   : product_categories, products, product_variants, stock_movements
--   6. Transaksi & Pesanan      : payment_methods, orders, order_items, order_item_options, order_status_history
--   7. Pengiriman (Delivery)    : deliveries, delivery_assignments, delivery_tracking_events, delivery_proofs
--   8. Dompet & Pembayaran      : user_payment_methods, payments, wallets, wallet_transactions, refunds
--   9. Promo, Ulasan, Chat      : promos, promo_usages, reviews, review_photos, notifications_custom, conversations, conversation_participants, messages
--  10. System & CMS             : service_zones, app_settings, audit_logs, testimonials, news_categories, news
-- ============================================================================

-- ============================================================================
-- EXTENSIONS
-- ============================================================================
CREATE EXTENSION IF NOT EXISTS pgcrypto;      -- Untuk gen_random_uuid()
CREATE EXTENSION IF NOT EXISTS cube;          -- Dependency earthdistance
CREATE EXTENSION IF NOT EXISTS earthdistance; -- Untuk ll_to_earth() hitung jarak
CREATE EXTENSION IF NOT EXISTS postgis;       -- Operasi spasial / polygon wilayah

-- ============================================================================
-- 1. MODUL AUTHENTICATION & USERS
-- ============================================================================

-- ----------------------------------------------------------------------------
-- ENUM TYPES
-- ----------------------------------------------------------------------------
CREATE TYPE user_role AS ENUM ('MEMBER', 'ADMIN', 'MANAGER');
CREATE TYPE user_status AS ENUM ('ACTIVE', 'INACTIVE', 'SUSPENDED', 'BANNED');

-- ----------------------------------------------------------------------------
-- USERS (Pelanggan, Driver, Pemilik Merchant, Administrator)
-- ----------------------------------------------------------------------------
CREATE TABLE users (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    phone           VARCHAR(20) UNIQUE NOT NULL,
    email           VARCHAR(255) UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    full_name       VARCHAR(255) NOT NULL,
    role            user_role NOT NULL DEFAULT 'MEMBER',
    status          user_status NOT NULL DEFAULT 'ACTIVE',
    avatar_url      TEXT,
    referral_code   VARCHAR(20) UNIQUE,
    referred_by     UUID REFERENCES users(id),
    email_verified  BOOLEAN DEFAULT FALSE,
    phone_verified  BOOLEAN DEFAULT FALSE,
    last_login_at   TIMESTAMPTZ,
    remember_token  VARCHAR(100),
    -- WALLET / GridePay (ditambah 2026-08-13): PIN 6 digit ter-hash bcrypt.
    -- Salah 5x berturut-turut → wallet terkunci 5 menit (wallet_locked_until).
    wallet_pin          VARCHAR(255),
    wallet_pin_attempts INTEGER DEFAULT 0,
    wallet_locked_until TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ
);

CREATE INDEX idx_users_phone ON users(phone);
CREATE INDEX idx_users_role ON users(role);

-- ----------------------------------------------------------------------------
-- OTP / VERIFIKASI (Verifikasi nomor telepon via SMS/WhatsApp)
-- ----------------------------------------------------------------------------
CREATE TABLE otp_verifications (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID REFERENCES users(id) ON DELETE CASCADE,
    phone       VARCHAR(20) NOT NULL,
    otp_code    VARCHAR(6) NOT NULL,
    purpose     VARCHAR(50) NOT NULL, -- REGISTER, LOGIN, RESET_PASSWORD
    expires_at  TIMESTAMPTZ NOT NULL,
    used        BOOLEAN DEFAULT FALSE,
    created_at  TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_otp_phone_purpose ON otp_verifications(phone, purpose);

-- ----------------------------------------------------------------------------
-- USER ADDRESSES (Daftar alamat pengiriman milik pengguna)
-- ----------------------------------------------------------------------------
CREATE TABLE user_addresses (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    label           VARCHAR(50) NOT NULL, -- RUMAH, KANTOR, LAINNYA
    recipient_name  VARCHAR(255),
    recipient_phone VARCHAR(20),
    address_line    TEXT NOT NULL,
    city            VARCHAR(100) NOT NULL,
    province        VARCHAR(100),
    postal_code     VARCHAR(10),
    latitude        DECIMAL(10, 8) NOT NULL,
    longitude       DECIMAL(11, 8) NOT NULL,
    is_default      BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_user_addresses_user ON user_addresses(user_id);
CREATE INDEX idx_user_addresses_geo ON user_addresses USING GIST(
    ll_to_earth(latitude, longitude)
);

-- ----------------------------------------------------------------------------
-- SESSIONS (Driver session Laravel untuk autentikasi berbasis database)
-- ----------------------------------------------------------------------------
CREATE TABLE sessions (
    id              VARCHAR(255) PRIMARY KEY,
    user_id         UUID REFERENCES users(id),
    ip_address      VARCHAR(45),
    user_agent      TEXT,
    payload         TEXT NOT NULL,
    last_activity   INTEGER NOT NULL
);

CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);

-- ----------------------------------------------------------------------------
-- PERSONAL ACCESS TOKENS (Sanctum Tokens untuk API Mobile & Third-party)
-- ----------------------------------------------------------------------------
CREATE TABLE personal_access_tokens (
    id              BIGSERIAL PRIMARY KEY,
    tokenable_type  VARCHAR(255) NOT NULL,
    tokenable_id    UUID NOT NULL,
    name            TEXT NOT NULL,
    token           VARCHAR(64) UNIQUE NOT NULL,
    abilities       TEXT,
    last_used_at    TIMESTAMPTZ,
    expires_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_pat_tokenable ON personal_access_tokens(tokenable_type, tokenable_id);

-- ============================================================================
-- 2. MODUL DRIVER / KURIR
-- ============================================================================

-- ----------------------------------------------------------------------------
-- ENUM TYPES DRIVER
-- ----------------------------------------------------------------------------
CREATE TYPE driver_status AS ENUM (
    'OFFLINE', 'ONLINE', 'BUSY',
    'WAITING_ASSIGNMENT', 'ON_DELIVERY'
);

CREATE TYPE vehicle_type AS ENUM ('MOTORCYCLE', 'CAR', 'BICYCLE', 'TRUCK');

-- ----------------------------------------------------------------------------
-- DRIVERS (Profil operasional driver/kurir)
-- ----------------------------------------------------------------------------
CREATE TABLE drivers (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id             UUID UNIQUE NOT NULL REFERENCES users(id),
    status              driver_status DEFAULT 'OFFLINE',
    is_verified         BOOLEAN DEFAULT FALSE,
    rating              DECIMAL(3,2) DEFAULT 5.00,
    total_trips         INTEGER DEFAULT 0,
    current_lat         DECIMAL(10, 8),
    current_lng         DECIMAL(11, 8),
    last_location_at    TIMESTAMPTZ,
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_drivers_status ON drivers(status);
CREATE INDEX idx_drivers_location ON drivers(current_lat, current_lng);

-- ----------------------------------------------------------------------------
-- DRIVER VEHICLES (Data kendaraan aktif & riwayat kendaraan driver)
-- ----------------------------------------------------------------------------
-- MULTI-VEHICLE (2026-08-13): 1 driver bisa punya banyak kendaraan. Aturan bisnis:
--   - vehicle_type sekarang: MOTOR, MOBIL, BAJAJ, TRUK, PICKUP_TERBUKA, PICKUP_BOX
--   - is_active melekat pada kendaraan (per jenis max 1 aktif untuk bid); user OFFLINE/ONLINE tetap di tabel drivers
--   - status_verifikasi: pending/approved/rejected (admin bisa approve; default 'approved')
--   - deleted_at: soft delete (tidak boleh hapus saat ada order berjalan)
CREATE TABLE driver_vehicles (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    driver_id       UUID NOT NULL REFERENCES drivers(id) ON DELETE CASCADE,
    vehicle_type    vehicle_type NOT NULL,
    brand           VARCHAR(100),
    model           VARCHAR(100),
    year            INTEGER,
    plate_number    VARCHAR(20) UNIQUE NOT NULL,
    color           VARCHAR(50),
    is_active       BOOLEAN DEFAULT TRUE,
    is_default      BOOLEAN NOT NULL DEFAULT FALSE,
    status_verifikasi VARCHAR(20) NOT NULL DEFAULT 'approved',
    foto_kendaraan  VARCHAR(255),
    foto_stnk       VARCHAR(255),
    deleted_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Migrasi data existing: kendaraan lama di tabel user/driver dipindah ke baris pertama dengan is_active = TRUE.

-- ----------------------------------------------------------------------------
-- DRIVER DOCUMENTS (Dokumen verifikasi driver: KTP, SIM, STNK, SKCK)
-- ----------------------------------------------------------------------------
CREATE TABLE driver_documents (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    driver_id       UUID NOT NULL REFERENCES drivers(id) ON DELETE CASCADE,
    doc_type        VARCHAR(50) NOT NULL, -- KTP, SIM, STNK, SKCK
    doc_number      VARCHAR(100),
    file_url        TEXT NOT NULL,
    expiry_date     DATE,
    is_verified     BOOLEAN DEFAULT FALSE,
    verified_at     TIMESTAMPTZ,
    verified_by     UUID REFERENCES users(id),
    rejection_reason TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- DRIVER LOCATIONS (Riwayat lokasi real-time GPS driver)
-- ----------------------------------------------------------------------------
CREATE TABLE driver_locations (
    id          BIGSERIAL PRIMARY KEY,
    driver_id   UUID NOT NULL REFERENCES drivers(id),
    latitude    DECIMAL(10, 8) NOT NULL,
    longitude   DECIMAL(11, 8) NOT NULL,
    speed       DECIMAL(5,2), -- km/h
    heading     DECIMAL(5,2), -- derajat arah kompas
    recorded_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_driver_locations_driver_time ON driver_locations(driver_id, recorded_at DESC);

-- ----------------------------------------------------------------------------
-- DRIVER WALLETS (Saldo dan penampungan komisi driver)
-- ----------------------------------------------------------------------------
CREATE TABLE driver_wallets (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    driver_id       UUID UNIQUE NOT NULL REFERENCES drivers(id),
    balance         DECIMAL(15,2) DEFAULT 0,
    pending_balance DECIMAL(15,2) DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================================
-- 3. MODUL MERCHANT & KATALOG
-- ============================================================================

CREATE TYPE merchant_type AS ENUM ('FOOD', 'MART', 'SHOP');
CREATE TYPE merchant_status AS ENUM ('PENDING', 'ACTIVE', 'INACTIVE', 'SUSPENDED', 'CLOSED');

-- ----------------------------------------------------------------------------
-- CATEGORIES (Kategori bisnis global: Makanan, Minuman, Sembako, Elektronik)
-- ----------------------------------------------------------------------------
CREATE TABLE categories (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    parent_id   UUID REFERENCES categories(id), -- Sub-kategori hirarkis
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) UNIQUE NOT NULL,
    icon_url    TEXT,
    type        merchant_type NOT NULL, -- FOOD, MART, SHOP
    sort_order  INTEGER DEFAULT 0,
    is_active   BOOLEAN DEFAULT TRUE,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- MERCHANTS (Toko, Restoran, atau Merchant Mitra)
-- ----------------------------------------------------------------------------
CREATE TABLE merchants (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    owner_id            UUID NOT NULL REFERENCES users(id),
    type                merchant_type NOT NULL,
    name                VARCHAR(255) NOT NULL,
    slug                VARCHAR(255) UNIQUE,
    description         TEXT,
    logo_url            TEXT,
    banner_url          TEXT,
    phone               VARCHAR(20),
    email               VARCHAR(255),
    address_line        TEXT NOT NULL,
    city                VARCHAR(100) NOT NULL,
    province            VARCHAR(100),
    latitude            DECIMAL(10, 8) NOT NULL,
    longitude           DECIMAL(11, 8) NOT NULL,
    status              merchant_status DEFAULT 'PENDING',
    is_open             BOOLEAN DEFAULT FALSE,
    opening_time        TIME DEFAULT '08:00',
    closing_time        TIME DEFAULT '22:00',
    min_order_amount    DECIMAL(12,2) DEFAULT 0,
    delivery_fee_base   DECIMAL(10,2) DEFAULT 0,
    delivery_fee_per_km DECIMAL(10,2) DEFAULT 2000,
    estimated_prep_time INTEGER DEFAULT 15,
    rating              DECIMAL(3,2) DEFAULT 0,
    total_orders        INTEGER DEFAULT 0,
    commission_rate     DECIMAL(5,2) DEFAULT 20.00,
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ
);

CREATE INDEX idx_merchants_type ON merchants(type);
CREATE INDEX idx_merchants_city ON merchants(city);
CREATE INDEX idx_merchants_status ON merchants(status);
CREATE INDEX idx_merchants_geo ON merchants(latitude, longitude);

-- ----------------------------------------------------------------------------
-- MERCHANT CATEGORIES (Pivot kategori global yang ditangani oleh merchant)
-- ----------------------------------------------------------------------------
CREATE TABLE merchant_categories (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    category_id UUID NOT NULL REFERENCES categories(id)
);

-- ----------------------------------------------------------------------------
-- MERCHANT HOURS (Jam buka & tutup operasional merchant per hari)
-- ----------------------------------------------------------------------------
CREATE TABLE merchant_hours (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    day_of_week INTEGER NOT NULL, -- 0=Minggu, 1=Senin ... 6=Sabtu
    open_time   TIME NOT NULL,
    close_time  TIME NOT NULL,
    is_closed   BOOLEAN DEFAULT FALSE,
    UNIQUE(merchant_id, day_of_week)
);

-- ----------------------------------------------------------------------------
-- MERCHANT PHOTOS (Galeri foto merchant)
-- ----------------------------------------------------------------------------
CREATE TABLE merchant_photos (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    photo_url   TEXT NOT NULL,
    caption     VARCHAR(255),
    sort_order  INTEGER DEFAULT 0
);

-- ============================================================================
-- 4. MODUL GRABFOOD (Katalog Restoran & Menu)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- MENU CATEGORIES (Kategori internal menu di restoran: Makanan Utama, Paket, dll.)
-- ----------------------------------------------------------------------------
CREATE TABLE menu_categories (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    name        VARCHAR(100) NOT NULL,
    sort_order  INTEGER DEFAULT 0,
    is_active   BOOLEAN DEFAULT TRUE
);

-- ----------------------------------------------------------------------------
-- MENU ITEMS (Item makanan/minuman yang dijual restoran)
-- ----------------------------------------------------------------------------
CREATE TABLE menu_items (
    id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id      UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    category_id      UUID REFERENCES menu_categories(id),
    name             VARCHAR(255) NOT NULL,
    slug             VARCHAR(255),
    description      TEXT,
    price            DECIMAL(12,2) NOT NULL,
    promo_price      DECIMAL(12,2),
    image_url        TEXT,
    is_available     BOOLEAN DEFAULT TRUE,
    is_recommended   BOOLEAN DEFAULT FALSE,
    is_spicy         BOOLEAN DEFAULT FALSE,
    is_halal         BOOLEAN DEFAULT TRUE,
    preparation_time INTEGER DEFAULT 10,
    stock_available  INTEGER,
    sort_order       INTEGER DEFAULT 0,
    created_at       TIMESTAMPTZ DEFAULT NOW(),
    updated_at       TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- MENU OPTIONS (Kelompok varian pilihan menu: Level Pedas, Ukuran Cup)
-- ----------------------------------------------------------------------------
CREATE TABLE menu_options (
    id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    menu_item_id UUID NOT NULL REFERENCES menu_items(id) ON DELETE CASCADE,
    name         VARCHAR(100) NOT NULL,
    is_required  BOOLEAN DEFAULT FALSE,
    max_select   INTEGER DEFAULT 1,
    sort_order   INTEGER DEFAULT 0
);

-- ----------------------------------------------------------------------------
-- MENU OPTION VALUES (Nilai spesifik pilihan varian: Sedang, Pedas, Extra Hot)
-- ----------------------------------------------------------------------------
CREATE TABLE menu_option_values (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    option_id   UUID NOT NULL REFERENCES menu_options(id) ON DELETE CASCADE,
    label       VARCHAR(100) NOT NULL,
    price_addon DECIMAL(10,2) DEFAULT 0,
    sort_order  INTEGER DEFAULT 0
);

-- ----------------------------------------------------------------------------
-- MENU ADDONS (Tambahan topping/side dish: Keju, Telur Tambahan)
-- ----------------------------------------------------------------------------
CREATE TABLE menu_addons (
    id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    menu_item_id UUID NOT NULL REFERENCES menu_items(id) ON DELETE CASCADE,
    name         VARCHAR(100) NOT NULL,
    price        DECIMAL(10,2) NOT NULL,
    is_available BOOLEAN DEFAULT TRUE
);

-- ============================================================================
-- 5. MODUL GRABMART & SHOP (Katalog Toko & Produk)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- PRODUCT CATEGORIES (Kategori barang toko)
-- ----------------------------------------------------------------------------
CREATE TABLE product_categories (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    name        VARCHAR(100) NOT NULL,
    sort_order  INTEGER DEFAULT 0,
    is_active   BOOLEAN DEFAULT TRUE
);

-- ----------------------------------------------------------------------------
-- PRODUCTS (Barang dagangan toko retail/mart)
-- ----------------------------------------------------------------------------
CREATE TABLE products (
    id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id  UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    category_id  UUID REFERENCES product_categories(id),
    sku          VARCHAR(100),
    barcode      VARCHAR(100),
    name         VARCHAR(255) NOT NULL,
    slug         VARCHAR(255),
    description  TEXT,
    price        DECIMAL(12,2) NOT NULL,
    promo_price  DECIMAL(12,2),
    cost_price   DECIMAL(12,2),
    stock        INTEGER DEFAULT 0,
    min_stock    INTEGER DEFAULT 5,
    unit         VARCHAR(20), -- pcs, kg, pack, botol
    weight_gram  INTEGER,
    image_url    TEXT,
    is_available BOOLEAN DEFAULT TRUE,
    is_featured  BOOLEAN DEFAULT FALSE,
    expiry_date  DATE,
    created_at   TIMESTAMPTZ DEFAULT NOW(),
    updated_at   TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- PRODUCT VARIANTS (Varian barang retail: Warna, Ukuran)
-- ----------------------------------------------------------------------------
CREATE TABLE product_variants (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    product_id  UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    name        VARCHAR(100) NOT NULL,
    value       VARCHAR(100) NOT NULL,
    price_addon DECIMAL(10,2) DEFAULT 0,
    stock       INTEGER DEFAULT 0,
    sku_variant VARCHAR(100)
);

-- ----------------------------------------------------------------------------
-- STOCK MOVEMENTS (Mutasi / riwayat pergerakan stok barang)
-- ----------------------------------------------------------------------------
CREATE TABLE stock_movements (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    product_id  UUID NOT NULL REFERENCES products(id),
    type        VARCHAR(20) NOT NULL, -- IN, OUT, ADJUSTMENT
    quantity    INTEGER NOT NULL,
    reason      TEXT,
    created_by  UUID,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================================
-- 6. MODUL TRANSAKSI & PESANAN
-- ============================================================================

-- ----------------------------------------------------------------------------
-- PAYMENT METHODS (Metode Pembayaran Sistem: QRIS, Cash, Wallet, Bank Transfer)
-- ----------------------------------------------------------------------------
CREATE TABLE payment_methods (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name        VARCHAR(100) NOT NULL,
    type        VARCHAR(50) NOT NULL, -- EWALLET, BANK, CASH, CARD
    icon_url    TEXT,
    is_active   BOOLEAN DEFAULT TRUE,
    fee_percent DECIMAL(5,2) DEFAULT 0,
    fee_flat    DECIMAL(10,2) DEFAULT 0
);

-- ----------------------------------------------------------------------------
-- ORDERS (Header Pesanan Layanan: FOOD, MART, SHOP, DELIVERY, RIDE)
-- ----------------------------------------------------------------------------
CREATE TABLE orders (
    id                   UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_number         VARCHAR(50) UNIQUE NOT NULL,
    order_type           VARCHAR(20) NOT NULL, -- FOOD, MART, SHOP, DELIVERY, RIDE
    status               VARCHAR(30) NOT NULL DEFAULT 'DRAFT',
    customer_id          UUID NOT NULL REFERENCES users(id),
    merchant_id          UUID REFERENCES merchants(id),
    driver_id            UUID REFERENCES drivers(id),
    pickup_address       TEXT NOT NULL,
    pickup_lat           DECIMAL(10, 8),
    pickup_lng           DECIMAL(11, 8),
    dropoff_address      TEXT NOT NULL,
    dropoff_lat          DECIMAL(10, 8),
    dropoff_lng          DECIMAL(11, 8),
    scheduled_at         TIMESTAMPTZ,
    confirmed_at         TIMESTAMPTZ,
    preparing_started_at TIMESTAMPTZ,
    picked_up_at         TIMESTAMPTZ,
    delivered_at         TIMESTAMPTZ,
    completed_at         TIMESTAMPTZ,
    cancelled_at         TIMESTAMPTZ,
    subtotal             DECIMAL(15,2) DEFAULT 0,
    discount_amount      DECIMAL(15,2) DEFAULT 0,
    delivery_fee         DECIMAL(12,2) DEFAULT 0,
    platform_fee         DECIMAL(12,2) DEFAULT 0,
    tax_amount           DECIMAL(12,2) DEFAULT 0,
    tip_amount           DECIMAL(12,2) DEFAULT 0,
    total_amount         DECIMAL(15,2) DEFAULT 0,
    payment_method_id    UUID REFERENCES payment_methods(id),
    payment_status       VARCHAR(30) DEFAULT 'UNPAID',
    is_cod               BOOLEAN DEFAULT FALSE,
    promo_id             UUID,
    note                 TEXT,
    cancel_reason        TEXT,
    cancelled_by         UUID,
    rating               DECIMAL(3,2),
    -- Round 3: snapshot tarif & komisi saat transaksi (perubahan settings tidak mempengaruhi transaksi lama)
    ride_distance_km           DECIMAL(10,2) DEFAULT NULL, -- Jarak tempuh RIDE/DELIVERY saat order dibuat
    cost_per_km_snapshot       DECIMAL(10,2) DEFAULT NULL, -- Biaya per KM yang berlaku saat order dibuat
    admin_commission_snapshot  DECIMAL(12,2) DEFAULT NULL, -- Potongan admin yang berlaku saat order dibuat
    merchant_commission_snapshot   DECIMAL(12,2) DEFAULT NULL, -- Komisi merchant yang berlaku saat order dibuat
    merchant_commission_pct_snapshot DECIMAL(5,2) DEFAULT NULL, -- Persen komisi merchant saat order dibuat
    -- GrAntar (ride-hailing, order_type='RIDE')
    service_type           VARCHAR(20) DEFAULT NULL, -- MOTOR / MOBIL
    distance_km            DECIMAL(10,2) DEFAULT 0,
    vehicle_id             VARCHAR(100) DEFAULT NULL, -- snapshot brand model • plate saat accept
    passenger_type         VARCHAR(20) DEFAULT 'SELF', -- SELF / OTHER
    passenger_name         VARCHAR(100) DEFAULT NULL,
    passenger_phone        VARCHAR(25) DEFAULT NULL,
    passenger_notes        TEXT DEFAULT NULL,
    created_at             TIMESTAMPTZ DEFAULT NOW(),
    updated_at           TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_orders_merchant ON orders(merchant_id);
CREATE INDEX idx_orders_driver ON orders(driver_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at DESC);

-- ----------------------------------------------------------------------------
-- ORDER ITEMS (Detail item yang dipesan dalam order)
-- ----------------------------------------------------------------------------
CREATE TABLE order_items (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id    UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    item_type   VARCHAR(20) NOT NULL, -- MENU_ITEM atau PRODUCT
    item_id     UUID NOT NULL,
    item_name   VARCHAR(255) NOT NULL,
    item_price  DECIMAL(12,2) NOT NULL,
    quantity    INTEGER DEFAULT 1,
    subtotal    DECIMAL(12,2) NOT NULL,
    note        TEXT,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- ORDER ITEM OPTIONS (Detail opsi varian menu/produk pada item yang dipesan)
-- ----------------------------------------------------------------------------
CREATE TABLE order_item_options (
    id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_item_id UUID NOT NULL REFERENCES order_items(id) ON DELETE CASCADE,
    option_name   VARCHAR(100) NOT NULL,
    option_value  VARCHAR(100) NOT NULL,
    price_addon   DECIMAL(10,2) DEFAULT 0
);

-- ----------------------------------------------------------------------------
-- ORDER STATUS HISTORY (Audit log perubahan status order)
-- ----------------------------------------------------------------------------
CREATE TABLE order_status_history (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id    UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    from_status VARCHAR(30),
    to_status   VARCHAR(30) NOT NULL,
    changed_by  UUID,
    note        TEXT,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================================
-- 7. MODUL PENGIRIMAN (DELIVERY)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- DELIVERIES (Status operasional pengiriman order oleh kurir/driver)
-- ----------------------------------------------------------------------------
CREATE TABLE deliveries (
    id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id          UUID UNIQUE NOT NULL REFERENCES orders(id),
    driver_id         UUID REFERENCES drivers(id),
    status            VARCHAR(50) DEFAULT 'PENDING',
    distance_km       DECIMAL(8,2),
    estimated_time    INTEGER, -- estimasi menit
    delivery_fee      DECIMAL(12,2),
    driver_fee        DECIMAL(12,2),
    assigned_at       TIMESTAMPTZ,
    accepted_at       TIMESTAMPTZ,
    arrived_pickup_at TIMESTAMPTZ,
    picked_up_at      TIMESTAMPTZ,
    delivered_at      TIMESTAMPTZ,
    cancelled_at      TIMESTAMPTZ,
    cancel_reason     TEXT,
    created_at        TIMESTAMPTZ DEFAULT NOW(),
    updated_at        TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- DELIVERY ASSIGNMENTS (Penawaran order ke driver terdekat)
-- ----------------------------------------------------------------------------
CREATE TABLE delivery_assignments (
    id                 UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    delivery_id        UUID NOT NULL REFERENCES deliveries(id),
    driver_id          UUID NOT NULL REFERENCES drivers(id),
    status             VARCHAR(30) NOT NULL, -- OFFERED, ACCEPTED, REJECTED, TIMEOUT
    offered_at         TIMESTAMPTZ DEFAULT NOW(),
    responded_at       TIMESTAMPTZ,
    response_note      TEXT,
    distance_to_pickup DECIMAL(8,2)
);

-- ----------------------------------------------------------------------------
-- DELIVERY TRACKING EVENTS (Event tracking perjalanan driver per lokasi GPS)
-- ----------------------------------------------------------------------------
CREATE TABLE delivery_tracking_events (
    id          BIGSERIAL PRIMARY KEY,
    delivery_id UUID NOT NULL REFERENCES deliveries(id),
    event_type  VARCHAR(50) NOT NULL,
    latitude    DECIMAL(10, 8),
    longitude   DECIMAL(11, 8),
    description TEXT,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- DELIVERY PROOFS (Bukti penerimaan barang: foto & tanda tangan digital)
-- ----------------------------------------------------------------------------
CREATE TABLE delivery_proofs (
    id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id       UUID UNIQUE NOT NULL REFERENCES orders(id),
    photo_url      TEXT NOT NULL,
    recipient_name VARCHAR(255),
    signature_url  TEXT,
    latitude       DECIMAL(10, 8),
    longitude      DECIMAL(11, 8),
    delivered_at   TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================================
-- 8. MODUL DOMPET & PEMBAYARAN
-- ============================================================================

-- ----------------------------------------------------------------------------
-- USER PAYMENT METHODS (Metode pembayaran tersimpan milik pengguna)
-- ----------------------------------------------------------------------------
CREATE TABLE user_payment_methods (
    id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id           UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    payment_method_id UUID NOT NULL REFERENCES payment_methods(id),
    provider          VARCHAR(50),
    account_number    VARCHAR(100),
    card_holder       VARCHAR(255),
    expiry_month      INTEGER,
    expiry_year       INTEGER,
    is_default        BOOLEAN DEFAULT FALSE,
    created_at        TIMESTAMPTZ DEFAULT NOW(),
    updated_at        TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- PAYMENTS (Catatan pembayaran pesanan via gateway/cash)
-- ----------------------------------------------------------------------------
CREATE TABLE payments (
    id                 UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id           UUID NOT NULL REFERENCES orders(id),
    payment_method_id  UUID REFERENCES payment_methods(id),
    amount             DECIMAL(15,2) NOT NULL,
    platform_fee       DECIMAL(12,2) DEFAULT 0,
    merchant_amount    DECIMAL(15,2),
    driver_amount      DECIMAL(15,2),
    status             VARCHAR(30) DEFAULT 'PENDING',
    payment_gateway_ref VARCHAR(255),
    paid_at            TIMESTAMPTZ,
    expired_at         TIMESTAMPTZ,
    created_at         TIMESTAMPTZ DEFAULT NOW(),
    updated_at         TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- WALLETS (Dompet digital pengguna / pelanggan — GridePay)
-- Catatan: production PostgreSQL memakai BIGINT untuk users.id, maka versi
-- production dibuat via API (CREATE TABLE IF NOT EXISTS dengan BIGSERIAL).
-- ----------------------------------------------------------------------------
CREATE TABLE wallets (
    id         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id    UUID UNIQUE NOT NULL REFERENCES users(id),
    balance    DECIMAL(15,2) DEFAULT 0,
    points     INTEGER DEFAULT 0,
    status     VARCHAR(20) DEFAULT 'ACTIVE',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- API baru: GET /api/wallets?user_id=X (autocreate tabel BIGINT di production)

-- ----------------------------------------------------------------------------
-- WALLET TRANSACTIONS (Mutasi saldo dompet digital: Topup, Bayar, Withdraw)
-- ----------------------------------------------------------------------------
CREATE TABLE wallet_transactions (
    id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    wallet_id      UUID NOT NULL REFERENCES wallets(id),
    type           VARCHAR(20) NOT NULL, -- TOPUP, PAYMENT, REFUND, WITHDRAW, BONUS, FEE
    amount         DECIMAL(15,2) NOT NULL,
    balance_before DECIMAL(15,2) NOT NULL,
    balance_after  DECIMAL(15,2) NOT NULL,
    reference_id   UUID,
    reference_type VARCHAR(50),
    description    TEXT,
    created_at     TIMESTAMPTZ DEFAULT NOW(),
    updated_at     TIMESTAMPTZ DEFAULT NOW()
);

-- Kolom ledger tambahan (runtime ALTER, produksi): direction, is_earning, user_id
ALTER TABLE wallet_transactions ADD COLUMN IF NOT EXISTS direction    VARCHAR(20) NOT NULL DEFAULT 'CREDIT'; -- CREDIT / DEBIT
ALTER TABLE wallet_transactions ADD COLUMN IF NOT EXISTS is_earning   BOOLEAN NOT NULL DEFAULT FALSE;      -- true = penghasilan
ALTER TABLE wallet_transactions ADD COLUMN IF NOT EXISTS user_id      BIGINT;                              -- owner wallet saat transaksi

-- ----------------------------------------------------------------------------
-- REFUNDS (Pengajuan & proses pengembalian dana pesanan)
-- ----------------------------------------------------------------------------
CREATE TABLE refunds (
    id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    payment_id   UUID NOT NULL REFERENCES payments(id),
    order_id     UUID NOT NULL REFERENCES orders(id),
    amount       DECIMAL(15,2) NOT NULL,
    reason       TEXT NOT NULL,
    status       VARCHAR(30) DEFAULT 'PENDING',
    approved_by  UUID,
    processed_at TIMESTAMPTZ,
    created_at   TIMESTAMPTZ DEFAULT NOW(),
    updated_at   TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================================
-- 9. MODUL PROMO, ULASAN & CHAT
-- ============================================================================

-- ----------------------------------------------------------------------------
-- PROMOS (Kode diskon & voucher promo aplikasi/merchant)
-- ----------------------------------------------------------------------------
CREATE TABLE promos (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code                VARCHAR(50) UNIQUE NOT NULL,
    name                VARCHAR(255) NOT NULL,
    description         TEXT,
    type                VARCHAR(30) NOT NULL, -- PERCENTAGE, FIXED, FREE_DELIVERY, BUY_1_GET_1
    scope               VARCHAR(30) DEFAULT 'ALL', -- ALL, FOOD, MART, MERCHANT_SPECIFIC
    discount_percent    DECIMAL(5,2),
    discount_amount     DECIMAL(12,2),
    max_discount        DECIMAL(12,2),
    min_order_amount    DECIMAL(12,2) DEFAULT 0,
    max_usage_total     INTEGER,
    max_usage_per_user  INTEGER DEFAULT 1,
    merchant_id         UUID REFERENCES merchants(id),
    category_id         UUID REFERENCES categories(id),
    start_date          TIMESTAMPTZ NOT NULL,
    end_date            TIMESTAMPTZ NOT NULL,
    is_active           BOOLEAN DEFAULT TRUE,
    created_by          UUID,
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- PROMO USAGES (Riwayat penggunaan promo oleh pelanggan)
-- ----------------------------------------------------------------------------
CREATE TABLE promo_usages (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    promo_id        UUID NOT NULL REFERENCES promos(id),
    order_id        UUID NOT NULL REFERENCES orders(id),
    user_id         UUID NOT NULL REFERENCES users(id),
    discount_amount DECIMAL(12,2) NOT NULL,
    used_at         TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- REVIEWS (Ulasan & rating pelanggan terhadap merchant & driver)
-- ----------------------------------------------------------------------------
CREATE TABLE reviews (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id        UUID UNIQUE NOT NULL REFERENCES orders(id),
    reviewer_id     UUID NOT NULL REFERENCES users(id),
    merchant_id     UUID REFERENCES merchants(id),
    merchant_rating DECIMAL(3,2),
    merchant_review TEXT,
    driver_id       UUID REFERENCES drivers(id),
    driver_rating   DECIMAL(3,2),
    driver_review   TEXT,
    is_anonymous    BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- REVIEW PHOTOS (Foto pendukung ulasan pesanan)
-- ----------------------------------------------------------------------------
CREATE TABLE review_photos (
    id        UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    review_id UUID NOT NULL REFERENCES reviews(id) ON DELETE CASCADE,
    photo_url TEXT NOT NULL
);

-- ----------------------------------------------------------------------------
-- NOTIFICATIONS_CUSTOM (Notifikasi in-app pengguna)
-- ----------------------------------------------------------------------------
CREATE TABLE notifications_custom (
    id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id      UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title        VARCHAR(255) NOT NULL,
    body         TEXT NOT NULL,
    type         VARCHAR(50) NOT NULL,
    reference_id UUID,
    is_read      BOOLEAN DEFAULT FALSE,
    created_at   TIMESTAMPTZ DEFAULT NOW(),
    updated_at   TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- CONVERSATIONS (Sesi percakapan / chat antar pengguna)
-- ----------------------------------------------------------------------------
CREATE TABLE conversations (
    id         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id   UUID REFERENCES orders(id),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- CONVERSATION PARTICIPANTS (Peserta sesi chat)
-- ----------------------------------------------------------------------------
CREATE TABLE conversation_participants (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    conversation_id UUID NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
    user_id         UUID NOT NULL REFERENCES users(id),
    joined_at       TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(conversation_id, user_id)
);

-- ----------------------------------------------------------------------------
-- MESSAGES (Pesan teks/media dalam percakapan chat)
-- ----------------------------------------------------------------------------
CREATE TABLE messages (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    conversation_id UUID NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
    sender_id       UUID NOT NULL REFERENCES users(id),
    message_text    TEXT,
    message_type    VARCHAR(20) DEFAULT 'TEXT', -- TEXT, IMAGE, LOCATION
    is_read         BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================================
-- 10. SYSTEM, CMS & MONITORING
-- ============================================================================

-- ----------------------------------------------------------------------------
-- SERVICE ZONES (Zona wilayah operasional layanan & multiplier tarif)
-- ----------------------------------------------------------------------------
CREATE TABLE service_zones (
    id                      UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name                    VARCHAR(100) NOT NULL,
    city                    VARCHAR(100) NOT NULL,
    polygon                 JSON, -- Koordinat area polygon PostGIS/JSON
    is_active               BOOLEAN DEFAULT TRUE,
    delivery_fee_multiplier DECIMAL(5,2) DEFAULT 1.0
);

-- ----------------------------------------------------------------------------
-- APP SETTINGS (Pengaturan global platform: nama app, kontak, tarif dasar)
-- ----------------------------------------------------------------------------
CREATE TABLE app_settings (
    key         VARCHAR(100) PRIMARY KEY,
    value       TEXT NOT NULL,
    description TEXT,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- Round 3: kunci tarif & komisi yang dikelola admin di halaman /admin/settings
-- Nilai disimpan sebagai angka murni (tanpa prefix "Rp") agar bisa dihitung backend.
-- Nilai ini di-snapshot ke kolom snapshot pada tabel orders saat transaksi dibuat,
-- sehingga perubahan settings TIDAK mempengaruhi transaksi lama.
--
-- ride_cost_per_km              = Biaya per KM untuk RIDE/DELIVERY (default 5000)
-- ride_base_fare                = Tarif dasar RIDE (default 10000)
-- food_commission_pct           = Persen komisi merchant/resto FOOD (default 20)
-- shop_commission_pct           = Persen komisi merchant TOKO (default 20)
-- admin_ride_commission_enabled = ON/OFF potongan admin untuk RIDE
-- admin_ride_commission_amount  = Nominal potongan admin RIDE (default 2000)
-- admin_food_commission_enabled = ON/OFF potongan admin untuk FOOD
-- admin_food_commission_amount  = Nominal potongan admin FOOD (default 3000)
-- admin_shop_commission_enabled = ON/OFF potongan admin untuk TOKO
-- admin_shop_commission_amount  = Nominal potongan admin TOKO (default 5000)
-- apk_download_url_customer     = URL publik APK Customer (default https://gride.web.id/apk/customer.apk)
-- apk_download_url_driver       = URL publik APK Driver  (default https://gride.web.id/apk/driver.apk)
-- apk_download_url_merchant     = URL publik APK Merchant (default https://gride.web.id/apk/merchant.apk)

-- ----------------------------------------------------------------------------
-- AUDIT LOGS (Log aktivitas & keamanan sistem admin)
-- ----------------------------------------------------------------------------
CREATE TABLE audit_logs (
    id          BIGSERIAL PRIMARY KEY,
    user_id     UUID REFERENCES users(id),
    action      VARCHAR(100) NOT NULL,
    table_name  VARCHAR(100),
    record_id   UUID,
    old_values  JSON,
    new_values  JSON,
    ip_address  VARCHAR(45),
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- TESTIMONIALS (Testimoni pengguna untuk halaman branding publik)
-- ----------------------------------------------------------------------------
CREATE TABLE testimonials (
    id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name             VARCHAR(255) NOT NULL,
    photo_url        TEXT,
    content          TEXT NOT NULL,
    rating           SMALLINT DEFAULT 5,
    role_title       VARCHAR(100),
    location         VARCHAR(100),
    testimonial_date DATE NOT NULL,
    is_published     BOOLEAN DEFAULT TRUE,
    created_at       TIMESTAMPTZ DEFAULT NOW(),
    updated_at       TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- NEWS CATEGORIES (Kategori berita & pengumuman publik)
-- ----------------------------------------------------------------------------
CREATE TABLE news_categories (
    id          BIGSERIAL PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    is_active   BOOLEAN DEFAULT TRUE,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- ----------------------------------------------------------------------------
-- NEWS (Artikel berita, blog & pengumuman publik)
-- ----------------------------------------------------------------------------
CREATE TABLE news (
    id               BIGSERIAL PRIMARY KEY,
    news_category_id BIGINT REFERENCES news_categories(id) ON DELETE SET NULL,
    title            VARCHAR(255) NOT NULL,
    slug             VARCHAR(255) UNIQUE NOT NULL,
    excerpt          TEXT,
    content          TEXT NOT NULL,
    featured_image   TEXT,
    status           VARCHAR(20) DEFAULT 'DRAFT', -- DRAFT, PUBLISHED
    published_at     TIMESTAMPTZ,
    created_at       TIMESTAMPTZ DEFAULT NOW(),
    updated_at       TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================================
-- SQL COMMENTS (DOKUMENTASI LENGKAP PADA TABEL DAN KOLOM DENGAN DESKRIPSI BISNIS)
-- ============================================================================

-- Modul 1: Authentication & Users
COMMENT ON TABLE users IS 'Menyimpan profil utama seluruh pengguna sistem (Customer, Driver, Merchant Owner, dan Admin)';
COMMENT ON COLUMN users.role IS 'Peran pengguna dalam sistem: MEMBER (Customer/Driver/Merchant gabungan), ADMIN (Pengelola Sistem), MANAGER (Panel Kota)';
COMMENT ON COLUMN users.status IS 'Status akun pengguna: ACTIVE (Aktif), INACTIVE (Tidak Aktif), SUSPENDED (Ditangguhkan sementara), BANNED (Diblokir permanen)';
COMMENT ON COLUMN users.remember_token IS 'Token autentikasi persisten untuk fitur Remember Me Laravel';

COMMENT ON TABLE otp_verifications IS 'Menyimpan kode OTP sementara untuk verifikasi nomor ponsel pengguna';
COMMENT ON TABLE user_addresses IS 'Menyimpan daftar lokasi/alamat favorit pengguna untuk tujuan penjemputan dan pengantaran';
COMMENT ON TABLE sessions IS 'Menyimpan sesi login aktif pengguna pada aplikasi web Laravel';
COMMENT ON TABLE personal_access_tokens IS 'Menyimpan token API Sanctum untuk autentikasi aplikasi mobile (Flutter Customer/Driver/Merchant)';

-- Modul 2: Driver & Kurir
COMMENT ON TABLE drivers IS 'Menyimpan profil operasional, status kehadiran, dan rating dari pengemudi/kurir';
COMMENT ON COLUMN drivers.status IS 'Status operasional driver: OFFLINE, ONLINE (Siap terima order), BUSY (Sedang bertugas), WAITING_ASSIGNMENT (Menunggu alokasi), ON_DELIVERY (Sedang mengantar)';
COMMENT ON TABLE driver_vehicles IS 'Menyimpan informasi kendaraan operasional yang terdaftar milik driver';
COMMENT ON TABLE driver_documents IS 'Menyimpan dokumen legalitas driver seperti KTP, SIM, STNK, dan SKCK untuk proses verifikasi admin';
COMMENT ON TABLE driver_locations IS 'Menyimpan riwayat koordinat GPS real-time milik driver saat dalam posisi ONLINE/ON_DELIVERY';
COMMENT ON TABLE driver_wallets IS 'Menyimpan saldo penghasilan driver dan saldo tertahan sebelum di-withdraw';

-- Modul 3: Merchant & Katalog
COMMENT ON TABLE categories IS 'Menyimpan kategori produk/layanan tingkat global pada platform (makanan, bahan pokok, dll)';
COMMENT ON TABLE merchants IS 'Menyimpan profil utama toko atau restoran mitra yang bergabung dalam platform';
COMMENT ON COLUMN merchants.type IS 'Tipe bisnis merchant: FOOD (Restoran/Kuliner), MART (Supermarket/Bahan Pokok), SHOP (Toko Retail/Elektronik)';
COMMENT ON TABLE merchant_categories IS 'Pivot table yang menghubungkan merchant dengan kategori bisnis global';
COMMENT ON TABLE merchant_hours IS 'Menyimpan jam operasional buka dan tutup merchant per hari dalam sepekan';
COMMENT ON TABLE merchant_photos IS 'Menyimpan foto etalase atau interior/eksterior merchant';

-- Modul 4: GrabFood
COMMENT ON TABLE menu_categories IS 'Menyimpan kategori internal menu yang ada di dalam restoran (contoh: Makanan Utama, Minuman, Paket Hemat)';
COMMENT ON TABLE menu_items IS 'Menyimpan daftar item makanan/minuman yang dijual oleh merchant kuliner';
COMMENT ON TABLE menu_options IS 'Menyimpan kelompok pilihan varian menu (contoh: Level Pedas, Pilihan Ukuran)';
COMMENT ON TABLE menu_option_values IS 'Menyimpan opsi spesifik dari kelompok varian menu beserta harga tambahannya';
COMMENT ON TABLE menu_addons IS 'Menyimpan topping atau makanan tambahan pendukung menu utama';

-- Modul 5: GrabMart & Shop
COMMENT ON TABLE product_categories IS 'Menyimpan kategori internal barang di toko retail/mart';
COMMENT ON TABLE products IS 'Menyimpan barang dagangan fisik retail/mart lengkap dengan stok dan harga modal';
COMMENT ON TABLE product_variants IS 'Menyimpan varian produk fisik seperti perbedaan warna atau ukuran barang';
COMMENT ON TABLE stock_movements IS 'Menyimpan log mutasi penambahan, pengurangan, dan penyesuaian stok produk toko';

-- Modul 6: Transaksi & Pesanan
COMMENT ON TABLE payment_methods IS 'Menyimpan pilihan metode pembayaran yang didukung sistem (QRIS, E-Wallet, Cash, Transfer Bank)';
COMMENT ON TABLE orders IS 'Menyimpan pesanan induk (header order) untuk seluruh jenis layanan (Food, Mart, Shop, Delivery)';
COMMENT ON COLUMN orders.status IS 'Status alur pesanan: DRAFT, PENDING, CONFIRMED, PREPARING, PICKED_UP, DELIVERED, COMPLETED, CANCELLED';
COMMENT ON TABLE order_items IS 'Menyimpan rincian item barang/menu yang dibeli dalam satu pesanan';
COMMENT ON TABLE order_item_options IS 'Menyimpan rincian pilihan varian dari item pesanan';
COMMENT ON TABLE order_status_history IS 'Menyimpan riwayat kronologis perubahan status pesanan';

-- Modul 7: Pengiriman
COMMENT ON TABLE deliveries IS 'Menyimpan rincian operasional pengiriman barang/pesanan oleh driver';
COMMENT ON TABLE delivery_assignments IS 'Menyimpan tawaran pengiriman pesanan yang dikirimkan ke driver terdekat';
COMMENT ON TABLE delivery_tracking_events IS 'Menyimpan titik pelacakan lokasi dan status transit pengiriman secara berkala';
COMMENT ON TABLE delivery_proofs IS 'Menyimpan foto serah terima dan tanda tangan digital bukti pesanan sampai ke penerima';

-- Modul 8: Dompet & Pembayaran
COMMENT ON TABLE user_payment_methods IS 'Menyimpan informasi metode pembayaran/rekening tersimpan milik pelanggan';
COMMENT ON TABLE payments IS 'Menyimpan catatan transaksi pembayaran finansial dari setiap pesanan';
COMMENT ON TABLE wallets IS 'Menyimpan saldo e-wallet dan poin hadiah (loyalty points) milik pelanggan';
COMMENT ON TABLE wallet_transactions IS 'Menyimpan mutasi rincian keluar/masuk saldo pada e-wallet pelanggan';
COMMENT ON TABLE refunds IS 'Menyimpan data pengajuan dan pemrosesan pengembalian dana pesanan yang dibatalkan';

-- Modul 9: Promo, Ulasan & Chat
COMMENT ON TABLE promos IS 'Menyimpan kode voucher dan diskon promo platform atau promo merchant';
COMMENT ON TABLE promo_usages IS 'Menyimpan riwayat penggunaan voucher promo oleh pelanggan pada transaksi tertentu';
COMMENT ON TABLE reviews IS 'Menyimpan ulasan bintang dan penilaian tertulis pelanggan untuk merchant dan driver';
COMMENT ON TABLE review_photos IS 'Menyimpan dokumentasi foto yang diunggah pelanggan bersama ulasan';
COMMENT ON TABLE notifications_custom IS 'Menyimpan pesan notifikasi in-app untuk pengguna';
COMMENT ON TABLE conversations IS 'Menyimpan sesi ruang percakapan (chat) antar pelanggan, driver, atau merchant';
COMMENT ON TABLE conversation_participants IS 'Menyimpan daftar peserta dalam suatu ruang percakapan chat';
COMMENT ON TABLE messages IS 'Menyimpan isi pesan teks/media pada sesi chat';

-- Modul 10: System, CMS & Monitoring
COMMENT ON TABLE service_zones IS 'Menyimpan pemetaan area geografis jangkauan layanan platform dan penyesuaian tarif lokal';
COMMENT ON TABLE app_settings IS 'Menyimpan pasangan kunci-nilai konfigurasi global aplikasi';
COMMENT ON TABLE audit_logs IS 'Menyimpan rekam jejak aktivitas sensitif admin dan sistem untuk kebutuhan audit';
COMMENT ON TABLE testimonials IS 'Menyimpan ulasan/testimoni terpilih untuk ditampilkan pada website branding publik';
COMMENT ON TABLE news_categories IS 'Menyimpan kategori pengumuman dan berita publik';
COMMENT ON TABLE news IS 'Menyimpan postingan berita, pengumuman, dan artikel informasi pada website publik';
-- ============================================================================
-- MIGRASI TAMBAHAN (di atas basis schema di atas) — diterapkan incremental:
--  2026_08_12_020000 : tabel news, news_categories, testimonials, driver_locations
--  2026_08_12_020001 : orders   -> order_type, pickup_address/lat/lng, dropoff_address/lat/lng, note (kolom GPS antar-jemput)
--                      drivers  -> last_location_at (timestamp terakhir update lokasi)
--  2026_08_12_020002 : tabel app_settings (setting_key, setting_value) untuk konfigurasi global
-- ============================================================================

-- ============================================================================
-- IKLAN GRATIS (Iklan baris gratis yang dipasang pengguna via aplikasi)
-- Kolom production PostgreSQL (BIGINT / BIGSERIAL). Foto iklan disimpan
-- sebagai JSON array URL (maksimal 10 foto).
-- API: GET/POST/PUT/DELETE /api/iklan-gratis, GET /api/iklan-gratis/categories
-- ============================================================================
CREATE TABLE IF NOT EXISTS iklan_gratis_categories (
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    slug       VARCHAR(100) UNIQUE NOT NULL,
    is_active  BOOLEAN DEFAULT TRUE,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS iklan_gratis (
    id             BIGSERIAL PRIMARY KEY,
    user_id        BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    category_id    BIGINT REFERENCES iklan_gratis_categories(id) ON DELETE SET NULL,
    title          VARCHAR(255) NOT NULL,
    description    TEXT,
    price          NUMERIC(15,2) DEFAULT 0,
    photos         TEXT, -- JSON array URL foto, maksimal 10 foto
    contact_name   VARCHAR(255),
    contact_phone  VARCHAR(20),
    city           VARCHAR(100),
    status         VARCHAR(20) DEFAULT 'ACTIVE', -- ACTIVE, INACTIVE, EXPIRED, BLOCKED
    expired_at     TIMESTAMPTZ,
    posted_at      TIMESTAMPTZ DEFAULT NOW(),
    created_at     TIMESTAMPTZ DEFAULT NOW(),
    updated_at     TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_iklan_gratis_user ON iklan_gratis(user_id);
CREATE INDEX idx_iklan_gratis_category ON iklan_gratis(category_id);
CREATE INDEX idx_iklan_gratis_status ON iklan_gratis(status);
CREATE INDEX idx_iklan_gratis_expired ON iklan_gratis(expired_at);

-- 15 kategori default iklan gratis Indonesia
INSERT INTO iklan_gratis_categories (name, slug, sort_order) VALUES
    ('Properti',            'properti',             1),
    ('Kendaraan',           'kendaraan',            2),
    ('Elektronik',          'elektronik',           3),
    ('Fashion',             'fashion',              4),
    ('Kesehatan & Kecantikan','kesehatan-kecantikan',5),
    ('Hobi & Olahraga',     'hobi-olahraga',        6),
    ('Lowongan Kerja',      'lowongan-kerja',       7),
    ('Jasa',                'jasa',                 8),
    ('Makanan & Minuman',   'makanan-minuman',      9),
    ('Peralatan Rumah Tangga','peralatan-rumah-tangga', 10),
    ('Hewan Peliharaan',    'hewan-peliharaan',     11),
    ('Pertanian & Perkebunan','pertanian-perkebunan', 12),
    ('Bisnis & Industri',   'bisnis-industri',      13),
    ('Komunitas & Event',   'komunitas-event',      14),
    ('Lain-lain',           'lain-lain',            15)
ON CONFLICT (slug) DO NOTHING;

-- ============================================================================
-- MIGRASI TAMBAHAN (2026-08-14)
-- 1. Dropdown Wilayah Indonesia: Provinsi -> Kota/Kabupaten
--    Sumber: skema_dan_insert_dropdown_wilayah_mysql.sql (konversi MySQL -> PostgreSQL)
--    Struktur only (tanpa INSERT). Data wilayah di-insert terpisah via wilayah_insert.sql
-- 2. Kolom role_kota pada tabel users: ROLE untuk panel /admin/kota
--    Nilai: 'ADMIN', 'MANAGER', 'MEMBER' — default 'MEMBER'
-- ============================================================================
CREATE TABLE IF NOT EXISTS provinsis (
  id SMALLINT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  pulau VARCHAR(50) NOT NULL,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_provinsis_nama ON provinsis (nama);
CREATE INDEX IF NOT EXISTS idx_provinsis_pulau ON provinsis (pulau);

CREATE TABLE IF NOT EXISTS kota_kabupatens (
  id SMALLINT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  tipe VARCHAR(10) NOT NULL CHECK (tipe IN ('Kabupaten', 'Kota')),
  provinsi_id SMALLINT NOT NULL REFERENCES provinsis (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_kota_kabupatens_provinsi ON kota_kabupatens (provinsi_id, nama, tipe);
CREATE INDEX IF NOT EXISTS idx_kota_kabupatens_dropdown ON kota_kabupatens (provinsi_id, nama);

-- Role panel /admin/kota: ADMIN (super admin panel), MANAGER (kelola wilayah & user), MEMBER (user biasa)
ALTER TABLE users ADD COLUMN IF NOT EXISTS role_kota VARCHAR(20) NOT NULL DEFAULT 'MEMBER' CHECK (role_kota IN ('ADMIN', 'MANAGER', 'MEMBER'));
CREATE INDEX IF NOT EXISTS idx_users_role_kota ON users (role_kota);
COMMENT ON COLUMN users.role_kota IS 'Peran pada panel /admin/kota: ADMIN, MANAGER, MEMBER (default MEMBER)';

-- ============================================================================
-- COVERAGE KOTA MANAGER (2026-08-14)
-- Mapping N:M antara user MANAGER panel /admin/kota dan kota/kabupaten yang
-- menjadi tanggung jawab pengelolaannya. 1 user MANAGER dapat mengelola
-- lebih dari satu kota.
-- ============================================================================
CREATE TABLE IF NOT EXISTS manager_coverage (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
  id_kota BIGINT NOT NULL REFERENCES kota_kabupatens (id) ON DELETE CASCADE,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW(),
  UNIQUE (user_id, id_kota)
);
CREATE INDEX IF NOT EXISTS idx_manager_coverage_user ON manager_coverage (user_id);
CREATE INDEX IF NOT EXISTS idx_manager_coverage_kota ON manager_coverage (id_kota);

-- ============================================================================
-- KOLOM TAMBAHAN: password_plain di users (2026-08-14)
-- Password asli dalam bentuk PLAIN-TEXT yang disimpan khusus agar admin dapat
-- melihat/mengelola password user (termasuk panel manager /admin/managers).
-- Password login tetap divalidasi dari kolom password (bcrypt).
-- ============================================================================
ALTER TABLE users ADD COLUMN IF NOT EXISTS password_plain VARCHAR(255);

-- ============================================================
-- MIGRATION 2026-08-14: Merge role CUSTOMER/DRIVER/MERCHANT → MEMBER
-- ============================================================
-- UPDATE users SET role = 'MEMBER' WHERE role IN ('CUSTOMER', 'DRIVER', 'MERCHANT');
-- DROP TYPE user_role CASCADE;  -- (jika perlu re-create)
-- CREATE TYPE user_role AS ENUM ('MEMBER', 'ADMIN', 'MANAGER');
-- ALTER TABLE users ALTER COLUMN role TYPE user_role USING role::text::user_role;
-- ALTER TABLE users ALTER COLUMN role SET DEFAULT 'MEMBER';

-- FIX ROLE MANAGER (2026-08-14): merge role CUSTOMER/DRIVER/MERCHANT -> MEMBER
-- menyebabkan akun admin kota (role_kota='MANAGER') ikut jadi MEMBER.
-- Rollback: akun dengan role_kota='MANAGER' WAJIB tetap role='MANAGER'.
UPDATE users SET role='MANAGER' WHERE role_kota='MANAGER';

-- Hasil akhir: role='ADMIN' (1 user super admin), role='MANAGER' (39 user panel kota),
-- role='MEMBER' (semua user biasa: customer/driver/merchant gabungan).
-- API: semua endpoint dengan user_id hanya boleh diakses role MEMBER
-- (ADMIN & MANAGER ditolak 403; login API juga ditolak untuk ADMIN/MANAGER).

-- ============================================================
-- MIGRATION 2026-08-15: Manager Area Scope (admin-revisi.md Phase 1)
-- Standarisasi area operasional Manager: dari filter string ILIKE
-- menjadi relasi kota_id yang kuat (FK ke kota_kabupatens).
-- Semua kolom baru NULLABLE; backfill dilakukan dari data string.
-- Kolom string lama (merchants.city) dipertahankan untuk compatibility.
-- ============================================================

-- drivers: kota operasi (resource operasional wilayah)
ALTER TABLE drivers ADD COLUMN IF NOT EXISTS operating_city_id BIGINT NULL;
COMMENT ON COLUMN drivers.operating_city_id IS 'Kota operasi driver (FK ke kota_kabupatens). Manager hanya mengelola driver di kotanya.';

-- merchants: relasi kota_id (di samping city string)
ALTER TABLE merchants ADD COLUMN IF NOT EXISTS city_id BIGINT NULL;
COMMENT ON COLUMN merchants.city_id IS 'Relasi kota (FK ke kota_kabupatens). city string tetap dipertahankan untuk backward compatibility.';

-- users: kota domisili default (nullable, belum dipakai untuk authorization)
ALTER TABLE users ADD COLUMN IF NOT EXISTS home_city_id BIGINT NULL;
COMMENT ON COLUMN users.home_city_id IS 'Kota domisili user (FK ke kota_kabupatens), nullable. Untuk fase 1 belum dipakai authorization.';

-- complaints: handling keluhan area oleh Manager
CREATE TABLE IF NOT EXISTS complaints (
  id BIGSERIAL PRIMARY KEY,
  reporter_id BIGINT NULL,
  target_type VARCHAR(30) NULL,      -- customer, driver, merchant, order
  target_id BIGINT NULL,
  order_id BIGINT NULL,
  category VARCHAR(50) NULL,         -- delivery_late, driver_behavior, merchant_issue, billing, other
  subject VARCHAR(255) NULL,
  message TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'OPEN',  -- OPEN, IN_PROGRESS, RESOLVED, CLOSED
  assigned_user_id BIGINT NULL,      -- manager yang menangani
  resolution TEXT NULL,
  created_at TIMESTAMPTZ NULL,
  updated_at TIMESTAMPTZ NULL
);
CREATE INDEX IF NOT EXISTS idx_complaints_status ON complaints (status);
COMMENT ON TABLE complaints IS 'Keluhan/complaint yang ditangani Manager per area.';

-- audit_logs: pencatatan aksi Manager (bukan hanya Laravel log)
CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50) NULL,      -- driver, merchant, customer, order, payment, wallet
  entity_id BIGINT NULL,
  before_data JSONB NULL,
  after_data JSONB NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMPTZ NULL
);
CREATE INDEX IF NOT EXISTS idx_audit_logs_user ON audit_logs (user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_entity ON audit_logs (entity_type, entity_id);
COMMENT ON TABLE audit_logs IS 'Audit trail aksi Manager: who, what, before, after, when, IP.';

-- service_zones: disiapkan untuk layer subdivisi area (Provinsi → Kota → Zone → Coverage).
-- Belum diimplementasikan; schema disisakan untuk perkembangan bisnis.
CREATE TABLE IF NOT EXISTS service_zones (
  id BIGSERIAL PRIMARY KEY,
  kota_id BIGINT NULL REFERENCES kota_kabupatens (id) ON DELETE CASCADE,
  name VARCHAR(100) NOT NULL,        -- e.g. Surabaya Barat, Surabaya Pusat
  description TEXT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ NULL,
  updated_at TIMESTAMPTZ NULL
);
COMMENT ON TABLE service_zones IS 'Subdivisi area dalam kota (layer berikutnya); belum dipakai authorization.';

-- Backfill: merchants.city_id dari merchants.city (best-match ke kota_kabupatens.nama)
UPDATE merchants SET city_id = k.id
FROM kota_kabupatens k
WHERE merchants.city_id IS NULL
  AND (lower(merchants.city) = lower(k.nama)
       OR lower(merchants.city) LIKE '%' || lower(k.nama) || '%'
       OR replace(lower(merchants.city), 'kota ', '') = replace(lower(k.nama), 'kota ', '')
       OR replace(lower(merchants.city), 'kabupaten ', '') = replace(lower(k.nama), 'kabupaten ', ''));

-- Backfill: drivers.operating_city_id — driver dengan user MANAGER mengikuti coverage pertamanya;
-- sisanya NULL (Manager assign manual lewat halaman Edit Driver).
UPDATE drivers SET operating_city_id = mc.id_kota
FROM (SELECT DISTINCT ON (mc.user_id) mc.user_id, mc.id_kota FROM manager_coverage mc) mc
WHERE drivers.user_id = mc.user_id AND drivers.operating_city_id IS NULL;
