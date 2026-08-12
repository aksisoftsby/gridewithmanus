-- Round 3: default tarif & komisi ke tabel app_settings (postgres live)
-- Jalankan dengan: psql -f round3_default_settings.sql
INSERT INTO app_settings (setting_key, setting_value, created_at, updated_at) VALUES
-- (kolom live: id bigserial, setting_key unique, setting_value text)
('ride_cost_per_km', '5000', NOW(), NOW()),
('ride_base_fare', '10000', NOW(), NOW()),
('food_commission_pct', '20', NOW(), NOW()),
('shop_commission_pct', '20', NOW(), NOW()),
('admin_ride_commission_enabled', 'OFF', NOW(), NOW()),
('admin_ride_commission_amount', '2000', NOW(), NOW()),
('admin_food_commission_enabled', 'OFF', NOW(), NOW()),
('admin_food_commission_amount', '3000', NOW(), NOW()),
('admin_shop_commission_enabled', 'OFF', NOW(), NOW()),
('admin_shop_commission_amount', '5000', NOW(), NOW()),
('apk_download_url_customer', 'https://gride.web.id/apk/customer.apk', NOW(), NOW()),
('apk_download_url_driver', 'https://gride.web.id/apk/driver.apk', NOW(), NOW()),
('apk_download_url_merchant', 'https://gride.web.id/apk/merchant.apk', NOW(), NOW())
ON CONFLICT ON CONSTRAINT app_settings_setting_key_unique DO NOTHING;
