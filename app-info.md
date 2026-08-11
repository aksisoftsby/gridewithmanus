# 🏗️ Desain Database Aplikasi Super App (Grab-Like)

Berikut desain database lengkap untuk aplikasi **Super App** dengan fitur **Grab Food, Grab Toko/Mart, dan Kurir Antar**. Menggunakan **PostgreSQL** sebagai rekomendasi.

---

## 📊 Arsitektur Modul Database

```
┌─────────────────────────────────────────────────────────────┐
│                    CORE / SHARED LAYER                       │
│  users, addresses, auth, wallets, payments, notifications   │
├─────────────────────────────────────────────────────────────┤
│                    MERCHANT LAYER                            │
│  merchants (restaurant/store), categories, products/menu    │
├─────────────────────────────────────────────────────────────┤
│                    ORDER LAYER                               │
│  orders, order_items, order_status, cart                    │
├─────────────────────────────────────────────────────────────┤
│                    DELIVERY LAYER                            │
│  drivers, driver_location, delivery, assignment, tracking   │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔗 ERD High-Level

```
┌──────────┐       ┌──────────────┐       ┌──────────────┐
│  users   │──1:N──│ user_addresses│      │user_payments │
└──────────┘       └──────────────┘       └──────────────┘
     │
     ├──1:1──┐
     │       ▼
     │  ┌─────────┐     ┌────────────────┐
     │  │ drivers │──1:N│driver_vehicles │
     │  └─────────┘     └────────────────┘
     │       │
     │       └──1:N──┐
     │               ▼
     │        ┌────────────┐
     │        │ deliveries │
     │        └────────────┘
     │               ▲
     └──1:N──┐       │
             ▼       │
        ┌────────┐   │
        │ orders │───┘
        └────────┘
             │
             ├──N:1──┐
             │       ▼
             │  ┌───────────┐
             │  │ merchants │── (FOOD / MART / SHOP)
             │  └───────────┘
             │       │
             │       ├──1:N── menu_items / products
             │       │
             └──1:N── order_items

        ┌────────┐   ┌──────────────┐   ┌────────────┐
        │ wallets│   │wallet_txns   │   │  promos    │
        └────────┘   └──────────────┘   └────────────┘
```

---

## 1️⃣ MODUL USER & AUTH

```sql
-- ============================================
-- USERS (Customer, Driver, Merchant Owner)
-- ============================================
CREATE TYPE user_role AS ENUM ('CUSTOMER', 'DRIVER', 'MERCHANT', 'ADMIN');
CREATE TYPE user_status AS ENUM ('ACTIVE', 'INACTIVE', 'SUSPENDED', 'BANNED');

CREATE TABLE users (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    phone           VARCHAR(20) UNIQUE NOT NULL,
    email           VARCHAR(255) UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    full_name       VARCHAR(255) NOT NULL,
    role            user_role NOT NULL DEFAULT 'CUSTOMER',
    status          user_status NOT NULL DEFAULT 'ACTIVE',
    avatar_url      TEXT,
    referral_code   VARCHAR(20) UNIQUE,
    referred_by     UUID REFERENCES users(id),
    email_verified  BOOLEAN DEFAULT FALSE,
    phone_verified  BOOLEAN DEFAULT FALSE,
    last_login_at   TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ -- soft delete
);

CREATE INDEX idx_users_phone ON users(phone);
CREATE INDEX idx_users_role ON users(role);

-- ============================================
-- OTP / VERIFIKASI
-- ============================================
CREATE TABLE otp_verifications (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID REFERENCES users(id) ON DELETE CASCADE,
    phone       VARCHAR(20) NOT NULL,
    otp_code    VARCHAR(6) NOT NULL,
    purpose     VARCHAR(50) NOT NULL, -- LOGIN, REGISTER, RESET_PASSWORD
    expires_at  TIMESTAMPTZ NOT NULL,
    used        BOOLEAN DEFAULT FALSE,
    created_at  TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================
-- ALAMAT PENGGUNA
-- ============================================
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
```

---

## 2️⃣ MODUL DRIVER / KURIR

```sql
-- ============================================
-- PROFIL DRIVER
-- ============================================
CREATE TYPE driver_status AS ENUM (
    'OFFLINE', 'ONLINE', 'BUSY', 
    'WAITING_ASSIGNMENT', 'ON_DELIVERY'
);

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
    wallet_id           UUID, -- FK ke wallets
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_drivers_status ON drivers(status);
CREATE INDEX idx_drivers_location ON drivers(current_lat, current_lng);

-- ============================================
-- KENDARAAN DRIVER
-- ============================================
CREATE TYPE vehicle_type AS ENUM ('MOTORCYCLE', 'CAR', 'BICYCLE', 'TRUCK');

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
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================
-- DOKUMEN DRIVER (KTP, SIM, STNK, SKCK)
-- ============================================
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
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================
-- RIWAYAT LOKASI DRIVER (untuk tracking real-time)
-- ============================================
CREATE TABLE driver_locations (
    id          BIGSERIAL PRIMARY KEY,
    driver_id   UUID NOT NULL REFERENCES drivers(id),
    latitude    DECIMAL(10, 8) NOT NULL,
    longitude   DECIMAL(11, 8) NOT NULL,
    speed       DECIMAL(5,2), -- km/h
    heading     DECIMAL(5,2), -- arah
    recorded_at TIMESTAMPTZ DEFAULT NOW()
);

-- Partitioning untuk data besar (opsional)
CREATE INDEX idx_driver_locations_driver_time 
ON driver_locations(driver_id, recorded_at DESC);

-- ============================================
-- SALDO / DOMPET DRIVER
-- ============================================
CREATE TABLE driver_wallets (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    driver_id       UUID UNIQUE NOT NULL REFERENCES drivers(id),
    balance         DECIMAL(15,2) DEFAULT 0,
    pending_balance DECIMAL(15,2) DEFAULT 0,
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
```

---

## 3️⃣ MODUL MERCHANT (Unified: Resto & Toko)

> 💡 **Design Decision:** Restoran (Grab Food) dan Toko (Grab Mart/Toko) digabung dalam satu tabel `merchants` dengan field `type` untuk memudahkan query unified dan reporting.

```sql
-- ============================================
-- MERCHANT (RESTAURANT / STORE)
-- ============================================
CREATE TYPE merchant_type AS ENUM ('FOOD', 'MART', 'SHOP');
CREATE TYPE merchant_status AS ENUM ('PENDING', 'ACTIVE', 'INACTIVE', 'SUSPENDED', 'CLOSED');

CREATE TABLE merchants (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    owner_id            UUID NOT NULL REFERENCES users(id), -- merchant owner
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
    estimated_prep_time INTEGER DEFAULT 15, -- menit
    rating              DECIMAL(3,2) DEFAULT 0,
    total_orders        INTEGER DEFAULT 0,
    commission_rate     DECIMAL(5,2) DEFAULT 20.00, -- % komisi platform
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ
);

CREATE INDEX idx_merchants_type ON merchants(type);
CREATE INDEX idx_merchants_city ON merchants(city);
CREATE INDEX idx_merchants_status ON merchants(status);
CREATE INDEX idx_merchants_geo ON merchants(latitude, longitude);

-- ============================================
-- KATEGORI MERCHANT
-- ============================================
CREATE TABLE merchant_categories (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    category_id UUID NOT NULL REFERENCES categories(id)
);

-- Kategori global (Makanan, Minuman, Elektronik, dll)
CREATE TABLE categories (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    parent_id   UUID REFERENCES categories(id), -- sub-kategori
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) UNIQUE NOT NULL,
    icon_url    TEXT,
    type        merchant_type NOT NULL, -- FOOD/MART/SHOP
    sort_order  INTEGER DEFAULT 0,
    is_active   BOOLEAN DEFAULT TRUE
);

-- ============================================
-- JAM OPERASIONAL PER HARI
-- ============================================
CREATE TABLE merchant_hours (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    day_of_week INTEGER NOT NULL, -- 0=Minggu, 1=Senin...
    open_time   TIME NOT NULL,
    close_time  TIME NOT NULL,
    is_closed   BOOLEAN DEFAULT FALSE,
    UNIQUE(merchant_id, day_of_week)
);

-- ============================================
-- FOTO MERCHANT
-- ============================================
CREATE TABLE merchant_photos (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    photo_url   TEXT NOT NULL,
    caption     VARCHAR(255),
    sort_order  INTEGER DEFAULT 0
);
```

---

## 4️⃣ MODUL GRAB FOOD (Menu Restoran)

```sql
-- ============================================
-- KATEGORI MENU (dalam 1 restoran)
-- ============================================
CREATE TABLE menu_categories (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    name        VARCHAR(100) NOT NULL, -- "Makanan Utama", "Minuman"
    sort_order  INTEGER DEFAULT 0,
    is_active   BOOLEAN DEFAULT TRUE
);

-- ============================================
-- MENU ITEM
-- ============================================
CREATE TABLE menu_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id     UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    category_id     UUID REFERENCES menu_categories(id),
    name            VARCHAR(255) NOT NULL,
    slug            VARCHAR(255),
    description     TEXT,
    price           DECIMAL(12,2) NOT NULL,
    promo_price     DECIMAL(12,2),
    image_url       TEXT,
    is_available    BOOLEAN DEFAULT TRUE,
    is_recommended  BOOLEAN DEFAULT FALSE,
    is_spicy        BOOLEAN DEFAULT FALSE,
    is_halal        BOOLEAN DEFAULT TRUE,
    preparation_time INTEGER DEFAULT 10, -- menit
    stock_available INTEGER, -- NULL = unlimited
    sort_order      INTEGER DEFAULT 0,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_menu_items_merchant ON menu_items(merchant_id);

-- ============================================
-- OPSI MENU (ukuran, level pedas, dll)
-- ============================================
CREATE TABLE menu_options (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    menu_item_id UUID NOT NULL REFERENCES menu_items(id) ON DELETE CASCADE,
    name        VARCHAR(100) NOT NULL, -- "Ukuran", "Level Pedas"
    is_required BOOLEAN DEFAULT FALSE,
    max_select  INTEGER DEFAULT 1, -- berapa opsi bisa dipilih
    sort_order  INTEGER DEFAULT 0
);

CREATE TABLE menu_option_values (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    option_id   UUID NOT NULL REFERENCES menu_options(id) ON DELETE CASCADE,
    label       VARCHAR(100) NOT NULL, -- "Small", "Medium", "Large"
    price_addon DECIMAL(10,2) DEFAULT 0,
    sort_order  INTEGER DEFAULT 0
);

-- ============================================
-- ADD-ON / TOPPING
-- ============================================
CREATE TABLE menu_addons (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    menu_item_id UUID NOT NULL REFERENCES menu_items(id) ON DELETE CASCADE,
    name        VARCHAR(100) NOT NULL, -- "Extra Cheese", "Nasi Tambahan"
    price       DECIMAL(10,2) NOT NULL,
    is_available BOOLEAN DEFAULT TRUE
);
```

---

## 5️⃣ MODUL GRAB TOKO / MART (Produk Toko)

```sql
-- ============================================
-- KATEGORI PRODUK TOKO
-- ============================================
CREATE TABLE product_categories (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    name        VARCHAR(100) NOT NULL, -- "Sembako", "Snack", "Minuman"
    sort_order  INTEGER DEFAULT 0,
    is_active   BOOLEAN DEFAULT TRUE
);

-- ============================================
-- PRODUK
-- ============================================
CREATE TABLE products (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id     UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    category_id     UUID REFERENCES product_categories(id),
    sku             VARCHAR(100),
    barcode         VARCHAR(100),
    name            VARCHAR(255) NOT NULL,
    slug            VARCHAR(255),
    description     TEXT,
    price           DECIMAL(12,2) NOT NULL,
    promo_price     DECIMAL(12,2),
    cost_price      DECIMAL(12,2), -- harga modal
    stock           INTEGER NOT NULL DEFAULT 0,
    min_stock       INTEGER DEFAULT 5, -- alert stock rendah
    unit            VARCHAR(20), -- pcs, kg, liter, pack
    weight_gram     INTEGER, -- untuk hitung ongkir
    image_url       TEXT,
    is_available    BOOLEAN DEFAULT TRUE,
    is_featured     BOOLEAN DEFAULT FALSE,
    expiry_date     DATE,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_products_merchant ON products(merchant_id);
CREATE INDEX idx_products_barcode ON products(barcode);
CREATE INDEX idx_products_sku ON products(sku);

-- ============================================
-- VARIAN PRODUK (warna, ukuran, dll)
-- ============================================
CREATE TABLE product_variants (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    product_id  UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    name        VARCHAR(100) NOT NULL, -- "Ukuran", "Warna"
    value       VARCHAR(100) NOT NULL, -- "M", "Merah"
    price_addon DECIMAL(10,2) DEFAULT 0,
    stock       INTEGER DEFAULT 0,
    sku_variant VARCHAR(100)
);

-- ============================================
-- STOK MOVEMENT (audit trail)
-- ============================================
CREATE TABLE stock_movements (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    product_id  UUID NOT NULL REFERENCES products(id),
    type        VARCHAR(20) NOT NULL, -- IN, OUT, ADJUSTMENT
    quantity    INTEGER NOT NULL,
    reason      TEXT,
    created_by  UUID REFERENCES users(id),
    created_at  TIMESTAMPTZ DEFAULT NOW()
);
```

---

## 6️⃣ MODUL ORDER (Unified)

> 💡 Order dibuat **unified** dengan field `order_type` agar mudah tracking dan reporting.

```sql
-- ============================================
-- ORDER HEADER
-- ============================================
CREATE TYPE order_type AS ENUM ('FOOD', 'MART', 'SHOP', 'DELIVERY');
CREATE TYPE order_status AS ENUM (
    'DRAFT',              -- masih di cart
    'PENDING',            -- menunggu konfirmasi merchant
    'CONFIRMED',          -- merchant konfirmasi
    'PREPARING',          -- sedang disiapkan
    'READY_FOR_PICKUP',   -- siap diambil kurir
    'ASSIGNING_DRIVER',   -- mencari driver
    'DRIVER_ASSIGNED',    -- driver assigned
    'PICKED_UP',          -- driver ambil barang
    'ON_DELIVERY',        -- dalam perjalanan
    'DELIVERED',          -- sampai tujuan
    'COMPLETED',          -- selesai + paid
    'CANCELLED',          -- dibatalkan
    'REFUNDED'            -- refund
);

CREATE TABLE orders (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_number        VARCHAR(50) UNIQUE NOT NULL, -- GRB-20240101-0001
    order_type          order_type NOT NULL,
    status              order_status NOT NULL DEFAULT 'DRAFT',
    
    -- Relasi
    customer_id         UUID NOT NULL REFERENCES users(id),
    merchant_id         UUID REFERENCES merchants(id),
    driver_id           UUID REFERENCES drivers(id),
    
    -- Alamat
    pickup_address      TEXT NOT NULL,
    pickup_lat          DECIMAL(10, 8),
    pickup_lng          DECIMAL(11, 8),
    dropoff_address     TEXT NOT NULL,
    dropoff_lat         DECIMAL(10, 8),
    dropoff_lng         DECIMAL(11, 8),
    
    -- Waktu
    scheduled_at        TIMESTAMPTZ, -- jika jadwal, NULL = ASAP
    confirmed_at        TIMESTAMPTZ,
    preparing_started_at TIMESTAMPTZ,
    picked_up_at        TIMESTAMPTZ,
    delivered_at        TIMESTAMPTZ,
    completed_at        TIMESTAMPTZ,
    cancelled_at        TIMESTAMPTZ,
    
    -- Harga
    subtotal            DECIMAL(15,2) NOT NULL DEFAULT 0,
    discount_amount     DECIMAL(15,2) DEFAULT 0,
    delivery_fee        DECIMAL(12,2) DEFAULT 0,
    platform_fee        DECIMAL(12,2) DEFAULT 0,
    tax_amount          DECIMAL(12,2) DEFAULT 0,
    tip_amount          DECIMAL(12,2) DEFAULT 0,
    total_amount        DECIMAL(15,2) NOT NULL DEFAULT 0,
    
    -- Pembayaran
    payment_method_id   UUID,
    payment_status      VARCHAR(30) DEFAULT 'UNPAID', -- UNPAID, PAID, REFUNDED
    is_cod              BOOLEAN DEFAULT FALSE,
    
    -- Lainnya
    promo_id            UUID,
    note                TEXT, -- catatan customer
    cancel_reason       TEXT,
    cancelled_by        UUID REFERENCES users(id),
    rating              DECIMAL(3,2),
    
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_orders_merchant ON orders(merchant_id);
CREATE INDEX idx_orders_driver ON orders(driver_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at DESC);
CREATE INDEX idx_orders_number ON orders(order_number);

-- ============================================
-- ORDER ITEMS
-- ============================================
CREATE TABLE order_items (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id        UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    item_type       VARCHAR(20) NOT NULL, -- MENU_ITEM atau PRODUCT
    item_id         UUID NOT NULL, -- referensi ke menu_items atau products
    item_name       VARCHAR(255) NOT NULL, -- snapshot nama
    item_price      DECIMAL(12,2) NOT NULL, -- snapshot harga saat order
    quantity        INTEGER NOT NULL DEFAULT 1,
    subtotal        DECIMAL(12,2) NOT NULL,
    note            TEXT, -- "tanpa bawang", dll
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_order_items_order ON order_items(order_id);

-- ============================================
-- ORDER ITEM OPTIONS (snapshot opsi yang dipilih)
-- ============================================
CREATE TABLE order_item_options (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_item_id   UUID NOT NULL REFERENCES order_items(id) ON DELETE CASCADE,
    option_name     VARCHAR(100) NOT NULL, -- "Ukuran"
    option_value    VARCHAR(100) NOT NULL, -- "Large"
    price_addon     DECIMAL(10,2) DEFAULT 0
);

-- ============================================
-- RIWAYAT STATUS ORDER
-- ============================================
CREATE TABLE order_status_history (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id    UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    from_status order_status,
    to_status   order_status NOT NULL,
    changed_by  UUID REFERENCES users(id),
    note        TEXT,
    created_at  TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_order_history_order ON order_status_history(order_id);

-- ============================================
-- BUKTI PENYERAHAN (POD - Proof of Delivery)
-- ============================================
CREATE TABLE delivery_proofs (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id    UUID UNIQUE NOT NULL REFERENCES orders(id),
    photo_url   TEXT NOT NULL,
    recipient_name VARCHAR(255),
    signature_url TEXT,
    latitude    DECIMAL(10, 8),
    longitude   DECIMAL(11, 8),
    delivered_at TIMESTAMPTZ DEFAULT NOW()
);
```

---

## 7️⃣ MODUL DELIVERY / KURIR

```sql
-- ============================================
-- DELIVERY (entitas pengiriman)
-- ============================================
CREATE TABLE deliveries (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id        UUID UNIQUE NOT NULL REFERENCES orders(id),
    driver_id       UUID REFERENCES drivers(id),
    
    status          VARCHAR(50) DEFAULT 'PENDING', 
    -- PENDING, ASSIGNING, ASSIGNED, PICKED_UP, ON_WAY, DELIVERED, FAILED
    
    -- Jarak & biaya
    distance_km     DECIMAL(8,2),
    estimated_time  INTEGER, -- menit
    delivery_fee    DECIMAL(12,2),
    driver_fee      DECIMAL(12,2), -- yang diterima driver
    
    -- Waktu
    assigned_at     TIMESTAMPTZ,
    accepted_at     TIMESTAMPTZ,
    arrived_pickup_at TIMESTAMPTZ,
    picked_up_at    TIMESTAMPTZ,
    delivered_at    TIMESTAMPTZ,
    
    -- Cancel
    cancelled_at    TIMESTAMPTZ,
    cancel_reason   TEXT,
    
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_deliveries_driver ON deliveries(driver_id);
CREATE INDEX idx_deliveries_status ON deliveries(status);

-- ============================================
-- ASSIGNMENT LOG (riwayat tawaran ke driver)
-- ============================================
CREATE TABLE delivery_assignments (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    delivery_id     UUID NOT NULL REFERENCES deliveries(id),
    driver_id       UUID NOT NULL REFERENCES drivers(id),
    status          VARCHAR(30) NOT NULL, 
    -- OFFERED, ACCEPTED, REJECTED, EXPIRED, CANCELLED
    
    offered_at      TIMESTAMPTZ DEFAULT NOW(),
    responded_at    TIMESTAMPTZ,
    response_note   TEXT,
    distance_to_pickup DECIMAL(8,2) -- km
);

CREATE INDEX idx_assignments_driver ON delivery_assignments(driver_id);

-- ============================================
-- TRACKING EVENTS (untuk live tracking)
-- ============================================
CREATE TABLE delivery_tracking_events (
    id          BIGSERIAL PRIMARY KEY,
    delivery_id UUID NOT NULL REFERENCES deliveries(id),
    event_type  VARCHAR(50) NOT NULL,
    -- DRIVER_ASSIGNED, ARRIVED_MERCHANT, PICKED_UP, ON_WAY, ARRIVED_DESTINATION, DELIVERED
    
    latitude    DECIMAL(10, 8),
    longitude   DECIMAL(11, 8),
    description TEXT,
    created_at  TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_tracking_delivery ON delivery_tracking_events(delivery_id, created_at);
```

---

## 8️⃣ MODUL PAYMENT & WALLET

```sql
-- ============================================
-- METODE PEMBAYARAN
-- ============================================
CREATE TABLE payment_methods (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name        VARCHAR(100) NOT NULL, -- OVO, GOPAY, CASH, CREDIT_CARD
    type        VARCHAR(50) NOT NULL, -- EWALLET, BANK, CASH, CARD
    icon_url    TEXT,
    is_active   BOOLEAN DEFAULT TRUE,
    fee_percent DECIMAL(5,2) DEFAULT 0,
    fee_flat    DECIMAL(10,2) DEFAULT 0
);

-- ============================================
-- USER PAYMENT METHODS (kartu tersimpan, dll)
-- ============================================
CREATE TABLE user_payment_methods (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    payment_method_id UUID NOT NULL REFERENCES payment_methods(id),
    provider        VARCHAR(50), -- VISA, MASTERCARD, OVO
    account_number  VARCHAR(100), -- masked
    card_holder     VARCHAR(255),
    expiry_month    INTEGER,
    expiry_year     INTEGER,
    is_default      BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================
-- TRANSAKSI PEMBAYARAN
-- ============================================
CREATE TABLE payments (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id            UUID NOT NULL REFERENCES orders(id),
    payment_method_id   UUID REFERENCES payment_methods(id),
    
    amount              DECIMAL(15,2) NOT NULL,
    platform_fee        DECIMAL(12,2) DEFAULT 0,
    merchant_amount     DECIMAL(15,2), -- yang diterima merchant
    driver_amount       DECIMAL(15,2), -- yang diterima driver
    
    status              VARCHAR(30) DEFAULT 'PENDING',
    -- PENDING, SUCCESS, FAILED, EXPIRED, REFUNDED
    
    payment_gateway_ref VARCHAR(255), -- referensi dari gateway (Midtrans/Xendit)
    paid_at             TIMESTAMPTZ,
    expired_at          TIMESTAMPTZ,
    
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_payments_order ON payments(order_id);

-- ============================================
-- DOMPET (WALLET) USER & DRIVER
-- ============================================
CREATE TABLE wallets (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID UNIQUE NOT NULL REFERENCES users(id),
    balance     DECIMAL(15,2) DEFAULT 0,
    points      INTEGER DEFAULT 0,
    status      VARCHAR(20) DEFAULT 'ACTIVE',
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================
-- TRANSAKSI WALLET
-- ============================================
CREATE TYPE wallet_tx_type AS ENUM ('TOPUP', 'PAYMENT', 'REFUND', 'WITHDRAW', 'BONUS', 'FEE');

CREATE TABLE wallet_transactions (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    wallet_id       UUID NOT NULL REFERENCES wallets(id),
    type            wallet_tx_type NOT NULL,
    amount          DECIMAL(15,2) NOT NULL,
    balance_before  DECIMAL(15,2) NOT NULL,
    balance_after   DECIMAL(15,2) NOT NULL,
    reference_id    UUID, -- bisa order_id atau payment_id
    reference_type  VARCHAR(50),
    description     TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_wallet_tx_wallet ON wallet_transactions(wallet_id, created_at DESC);

-- ============================================
-- REFUND
-- ============================================
CREATE TABLE refunds (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    payment_id      UUID NOT NULL REFERENCES payments(id),
    order_id        UUID NOT NULL REFERENCES orders(id),
    amount          DECIMAL(15,2) NOT NULL,
    reason          TEXT NOT NULL,
    status          VARCHAR(30) DEFAULT 'PENDING', -- PENDING, APPROVED, REJECTED, COMPLETED
    approved_by     UUID REFERENCES users(id),
    processed_at    TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

---

## 9️⃣ MODUL PROMO & VOUCHER

```sql
-- ============================================
-- PROMO
-- ============================================
CREATE TYPE promo_type AS ENUM ('PERCENTAGE', 'FIXED', 'FREE_DELIVERY', 'BUY_1_GET_1');
CREATE TYPE promo_scope AS ENUM ('ALL', 'FOOD', 'MART', 'MERCHANT_SPECIFIC');

CREATE TABLE promos (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code                VARCHAR(50) UNIQUE NOT NULL,
    name                VARCHAR(255) NOT NULL,
    description         TEXT,
    type                promo_type NOT NULL,
    scope               promo_scope DEFAULT 'ALL',
    
    -- Nilai diskon
    discount_percent    DECIMAL(5,2),
    discount_amount     DECIMAL(12,2),
    max_discount        DECIMAL(12,2), -- max diskon untuk percentage
    
    -- Syarat
    min_order_amount    DECIMAL(12,2) DEFAULT 0,
    max_usage_total     INTEGER,
    max_usage_per_user  INTEGER DEFAULT 1,
    
    -- Target
    merchant_id         UUID REFERENCES merchants(id), -- jika scope MERCHANT_SPECIFIC
    category_id         UUID REFERENCES categories(id),
    
    start_date          TIMESTAMPTZ NOT NULL,
    end_date            TIMESTAMPTZ NOT NULL,
    is_active           BOOLEAN DEFAULT TRUE,
    
    created_by          UUID REFERENCES users(id),
    created_at          TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_promos_code ON promos(code);

-- ============================================
-- PENGGUNAAN PROMO
-- ============================================
CREATE TABLE promo_usages (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    promo_id    UUID NOT NULL REFERENCES promos(id),
    order_id    UUID NOT NULL REFERENCES orders(id),
    user_id     UUID NOT NULL REFERENCES users(id),
    discount_amount DECIMAL(12,2) NOT NULL,
    used_at     TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_promo_usage_user ON promo_usages(user_id, promo_id);
```

---

## 🔟 MODUL RATING & REVIEW

```sql
-- ============================================
-- RATING & REVIEW
-- ============================================
CREATE TABLE reviews (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id        UUID UNIQUE NOT NULL REFERENCES orders(id),
    reviewer_id     UUID NOT NULL REFERENCES users(id),
    
    -- Rating untuk merchant
    merchant_id     UUID REFERENCES merchants(id),
    merchant_rating DECIMAL(3,2), -- 1-5
    merchant_review TEXT,
    
    -- Rating untuk driver
    driver_id       UUID REFERENCES drivers(id),
    driver_rating   DECIMAL(3,2), -- 1-5
    driver_review   TEXT,
    
    is_anonymous    BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_reviews_merchant ON reviews(merchant_id);
CREATE INDEX idx_reviews_driver ON reviews(driver_id);

-- ============================================
-- FOTO REVIEW
-- ============================================
CREATE TABLE review_photos (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    review_id   UUID NOT NULL REFERENCES reviews(id) ON DELETE CASCADE,
    photo_url   TEXT NOT NULL
);
```

---

## 1️⃣1️⃣ MODUL NOTIFIKASI & CHAT

```sql
-- ============================================
-- NOTIFIKASI
-- ============================================
CREATE TABLE notifications (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title       VARCHAR(255) NOT NULL,
    body        TEXT NOT NULL,
    type        VARCHAR(50) NOT NULL, -- ORDER_STATUS, PROMO, SYSTEM
    reference_id UUID, -- order_id atau lainnya
    is_read     BOOLEAN DEFAULT FALSE,
    created_at  TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);

-- ============================================
-- CHAT (Customer <-> Driver <-> Merchant)
-- ============================================
CREATE TABLE conversations (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id    UUID REFERENCES orders(id),
    created_at  TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE conversation_participants (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    conversation_id UUID NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
    user_id         UUID NOT NULL REFERENCES users(id),
    joined_at       TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(conversation_id, user_id)
);

CREATE TABLE messages (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    conversation_id UUID NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
    sender_id       UUID NOT NULL REFERENCES users(id),
    message_text    TEXT,
    message_type    VARCHAR(20) DEFAULT 'TEXT', -- TEXT, IMAGE, LOCATION
    is_read         BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

---

## 1️⃣2️⃣ MODUL MASTER & KONFIGURASI

```sql
-- ============================================
-- ZONA / AREA LAYANAN
-- ============================================
CREATE TABLE service_zones (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name        VARCHAR(100) NOT NULL,
    city        VARCHAR(100) NOT NULL,
    polygon     JSONB, -- koordinat polygon area
    is_active   BOOLEAN DEFAULT TRUE,
    delivery_fee_multiplier DECIMAL(5,2) DEFAULT 1.0
);

-- ============================================
-- KONFIGURASI APLIKASI
-- ============================================
CREATE TABLE app_settings (
    key         VARCHAR(100) PRIMARY KEY,
    value       TEXT NOT NULL,
    description TEXT,
    updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- Contoh settings:
-- 'platform_fee_percent' = '5'
-- 'delivery_fee_per_km' = '2500'
-- 'max_order_distance_km' = '25'
-- 'driver_commission_percent' = '80'

-- ============================================
-- AUDIT LOG
-- ============================================
CREATE TABLE audit_logs (
    id          BIGSERIAL PRIMARY KEY,
    user_id     UUID REFERENCES users(id),
    action      VARCHAR(100) NOT NULL,
    table_name  VARCHAR(100),
    record_id   UUID,
    old_values  JSONB,
    new_values  JSONB,
    ip_address  INET,
    created_at  TIMESTAMPTZ DEFAULT NOW()
);
```

---

## 📱 Flow Order & Relasi Status

```
┌─────────────────────────────────────────────────────────────────┐
│                    CUSTOMER ORDER FLOW                          │
└─────────────────────────────────────────────────────────────────┘

1. CUSTOMER browse merchant
   └──> SELECT * FROM merchants WHERE type='FOOD' AND is_open=true

2. CUSTOMER tambah ke cart
   └──> orders (status=DRAFT) + order_items

3. CUSTOMER checkout
   └──> orders.status = 'PENDING'
   └──> payments (status=PENDING)

4. MERCHANT konfirmasi
   └──> orders.status = 'CONFIRMED' → 'PREPARING'

5. SYSTEM assign driver
   └──> deliveries (status=PENDING)
   └──> delivery_assignments (broadcast ke driver terdekat)

6. DRIVER accept
   └──> deliveries.status = 'ASSIGNED'
   └──> orders.status = 'DRIVER_ASSIGNED'

7. DRIVER pickup
   └──> deliveries.status = 'PICKED_UP'
   └──> orders.status = 'PICKED_UP'

8. DRIVER antar
   └──> deliveries.status = 'ON_WAY'
   └──> orders.status = 'ON_DELIVERY'
   └──> driver_locations (update tiap 5-10 detik)

9. DELIVERED
   └──> delivery_proofs (foto + signature)
   └──> orders.status = 'DELIVERED' → 'COMPLETED'

10. SETTLEMENT
    └──> payments.status = 'SUCCESS'
    └──> wallet_transactions (merchant & driver)
```

---

## ⚡ Query Penting yang Sering Dipakai

### 1. Cari Driver Terdekat (PostGIS)
```sql
-- Butuh extension PostGIS
CREATE EXTENSION IF NOT EXISTS postgis;

SELECT d.id, d.user_id, 
       ST_Distance(
           ST_MakePoint(d.current_lng, d.current_lat)::geography,
           ST_MakePoint(106.8272, -6.1753)::geography -- lokasi merchant
       ) as distance_meters
FROM drivers d
WHERE d.status = 'ONLINE'
  AND ST_DWithin(
      ST_MakePoint(d.current_lng, d.current_lat)::geography,
      ST_MakePoint(106.8272, -6.1753)::geography,
      5000 -- radius 5km
  )
ORDER BY distance_meters
LIMIT 10;
```

### 2. Merchant Terdekat + Buka
```sql
SELECT m.*, 
       (6371 * acos(cos(radians(-6.1753)) * cos(radians(m.latitude)) 
       * cos(radians(m.longitude) - radians(106.8272)) 
       + sin(radians(-6.1753)) * sin(radians(m.latitude)))) AS distance_km
FROM merchants m
WHERE m.status = 'ACTIVE' 
  AND m.is_open = true
  AND m.type = 'FOOD'
ORDER BY distance_km
LIMIT 20;
```

### 3. Riwayat Order Customer
```sql
SELECT o.order_number, o.order_type, o.status, o.total_amount,
       m.name as merchant_name, o.created_at
FROM orders o
LEFT JOIN merchants m ON o.merchant_id = m.id
WHERE o.customer_id = 'uuid-customer'
ORDER BY o.created_at DESC
LIMIT 50;
```

---

## 🎯 Rekomendasi Teknologi

| Layer | Teknologi |
|-------|-----------|
| **Database** | PostgreSQL 15+ (dengan PostGIS) |
| **Cache** | Redis (untuk lokasi driver real-time, session) |
| **Search** | Elasticsearch (search merchant/produk) |
| **Queue** | RabbitMQ / Kafka (assign driver async) |
| **Real-time** | WebSocket / Firebase (tracking, chat) |
| **Object Storage** | AWS S3 / GCS (foto produk, bukti POD) |
| **Payment Gateway** | Midtrans / Xendit / Stripe |
| **Maps** | Google Maps API / Mapbox |

---

## 📌 Tips Implementasi

1. **Gunakan UUID** untuk primary key agar scalable dan aman
2. **Soft delete** (`deleted_at`) untuk data penting seperti user & merchant
3. **Indexing** pada kolom yang sering di-filter (status, created_at, foreign keys)
4. **Partitioning** untuk tabel `driver_locations` dan `audit_logs` karena data sangat besar
5. **Redis** untuk cache lokasi driver (update tiap 5 detik, jangan langsung ke DB)
6. **Event sourcing** untuk order status history agar bisa audit trail lengkap
7. **Separate DB** untuk analytics/reporting jika traffic sudah besar

---

Mau saya detailkan bagian tertentu? Misalnya:
- 🔧 **API Design** untuk setiap modul
- 📊 **Schema untuk admin dashboard / reporting**
- 🚀 **Strategi auto-assign driver** (algoritma matching)
- 💰 **Sistem settlement & komisi** yang lebih detail