
-- ============================================================================
-- MIGRASI TAMBAHAN (2026-08-14)
-- Dropdown Wilayah Indonesia: Provinsi -> Kota/Kabupaten (untuk form register/order)
-- Sumber data: skema_dan_insert_dropdown_wilayah_mysql.sql (konversi ke PostgreSQL)
-- Struktur only (tanpa INSERT) — data wilayah di-insert terpisah via wilayah_insert.sql
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
