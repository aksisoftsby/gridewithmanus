-- Seed: 40 merchants in Kediri (20 FOOD + 20 MART) + 5+ menu items each. No schema changes.
BEGIN;

INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Grand Panglima Resto', 'grand-panglima-resto', 'Restoran modern khas Asia dengan suasana nyaman untuk keluarga. Menu populer: nasi goreng, seafood, dan aneka minuman.', 'https://gride.web.id/uploads/merchants/food_grand_panglima.jpg', '+623546810001', 'Jl. Panglima Polim No.25, Kediri', 'Kediri', -7.8173333, 112.0161298, 'ACTIVE', true, 4.7, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Resto Keboen Rodjo Kediri', 'resto-keboen-rodjo-kediri', 'Restoran masakan Indonesia dengan suasana taman yang asri, favorit keluarga di Kediri.', 'https://gride.web.id/uploads/merchants/food_keboen_rodjo.jpg', '+623546810002', 'Jl. Mayor Bismo No.419, Kediri', 'Kediri', -7.7905769, 112.0091447, 'ACTIVE', true, 4.5, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Pandan Kediri Resto & Cafe', 'pandan-kediri-resto-cafe', 'Resto dan cafe bergaya oriental dengan menu Chinese food dan kopi pilihan.', 'https://gride.web.id/uploads/merchants/food_pandan_resto.jpg', '+623546810003', 'Jl. Hayam Wuruk No.40, Kediri', 'Kediri', -7.8155087, 112.018225, 'ACTIVE', true, 4.2, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Restaurant Pondok Kampoeng Nelayan', 'restaurant-pondok-kampoeng-nelayan', 'Restoran seafood dengan konsep pondokan, ikan bakar dan cumi tepung jadi andalan.', 'https://gride.web.id/uploads/merchants/food_pondok_nelayan.jpg', '+623546810004', 'Jl. Singosari No.30, Kediri', 'Kediri', -7.809187, 112.019364, 'ACTIVE', true, 4.5, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Resto Sambel Idjo Kediri', 'resto-sambel-idjo-kediri', 'Masakan Padang autentik dengan sambal hijau khas. Rendang dan dendeng balado tersedia.', 'https://gride.web.id/uploads/merchants/food_sambel_idjo.jpg', '+623546810005', 'Jl. Panglima Sudirman Kel No.117, Kediri', 'Kediri', -7.8260441, 112.0107319, 'ACTIVE', true, 4.2, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Kayu Manis', 'kayu-manis', 'Rumah makan Indonesia favorit warga Kediri dengan harga terjangkau dan porsi besar.', 'https://gride.web.id/uploads/merchants/food_kayu_manis.jpg', '+623546810006', 'Jl. Erlangga No.10, Kediri', 'Kediri', -7.8128202, 112.0539051, 'ACTIVE', true, 4.3, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'RM Mirasa 2', 'rm-mirasa-2', 'Rumah makan Padang sederhana dengan lauk lengkap, buka dari pagi hingga malam.', 'https://gride.web.id/uploads/merchants/food_rm_mirasa2.jpg', '+623546810007', 'Jl. Hayam Wuruk No.58, Kediri', 'Kediri', -7.8150613, 112.0166199, 'ACTIVE', true, 4.3, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Warung Leko', 'warung-leko', 'Warung legendaris khas Kediri terkenal dengan rawon dan sate kerangnya.', 'https://gride.web.id/uploads/merchants/food_warung_leko.jpg', '+623546810008', 'Jl. Ronggowarsito No.56-58, Kediri', 'Kediri', -7.8119762, 112.0104627, 'ACTIVE', true, 4.4, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Warung Gunung', 'warung-gunung', 'Warung makan 24 jam dengan menu rumahan yang variatif dan harga bersahabat.', 'https://gride.web.id/uploads/merchants/food_warung_gunung.jpg', '+623546810009', 'Jl. Ahmad Dahlan No.12, Kediri', 'Kediri', -7.8044922, 112.0047171, 'ACTIVE', true, 4.1, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Ayam Geprek Kremes Ndowerr', 'ayam-geprek-kremes-ndowerr', 'Ayam geprek kremes renyah dengan sambal bawang level 1-10.', 'https://gride.web.id/uploads/merchants/food_ayam_geprek.jpg', '+623546810010', 'Jl. Hayam Wuruk No.109, Kediri', 'Kediri', -7.8152342, 112.0182381, 'ACTIVE', true, 4.4, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Ayam Geprek Sa''i Bandar Lor', 'ayam-geprek-sa-i-bandar-lor', 'Ayam geprek crispy dengan sambal korek pedas mantap, porsi kenyang.', 'https://gride.web.id/uploads/merchants/food_geprek2.jpg', '+623546810011', 'Jl. KH Wachid Hasyim No.120, Kediri', 'Kediri', -7.8206695, 112.0057326, 'ACTIVE', true, 4.3, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Geprek Giyul', 'geprek-giyul', 'Ayam geprek kekinian dengan pilihan sambal dan topping mozarella.', 'https://gride.web.id/uploads/merchants/food_geprek2.jpg', '+623546810012', 'Jl. Dr. Sutomo No.56, Kediri', 'Kediri', -7.8201773, 112.0214489, 'ACTIVE', true, 4.2, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Ayam Geprek Sae Kota Kediri', 'ayam-geprek-sae-kota-kediri', 'Ayam geprek sae dengan ayam kampung pilihan, sambal ijo dan sambal bawang.', 'https://gride.web.id/uploads/merchants/food_ayam_geprek.jpg', '+623546810013', 'Jl. Pahlawan Kusuma Bangsa No.8, Kediri', 'Kediri', -7.8165693, 112.0230718, 'ACTIVE', true, 4.5, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Bakso Mama 1 Kediri', 'bakso-mama-1-kediri', 'Bakso sapi jumbo dengan kuah gurih kaldunya, favorit mahasiswa dan keluarga.', 'https://gride.web.id/uploads/merchants/food_bakso.jpg', '+623546810014', 'Jl. KH Wachid Hasyim No.191, Kediri', 'Kediri', -7.8243466, 112.0059302, 'ACTIVE', true, 4.4, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Bakso Barokah 313', 'bakso-barokah-313', 'Bakso bakar dan bakso halus dengan pentol jumbo, harga kaki lima.', 'https://gride.web.id/uploads/merchants/food_bakso.jpg', '+623546810015', 'Jl. Letjend Suprapto No.107, Kediri', 'Kediri', -7.8203334, 112.028123, 'ACTIVE', true, 4.2, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Bakso Kartini', 'bakso-kartini', 'Bakso urat dan bakso tahu dengan kuah bening segar.', 'https://gride.web.id/uploads/merchants/food_bakso.jpg', '+623546810016', 'Jl. Raden Ajeng Kartini No.5, Kediri', 'Kediri', -7.8091974, 112.0399962, 'ACTIVE', true, 4.3, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Mie Ayam Malioboro', 'mie-ayam-malioboro', 'Mie ayam pangsit dengan topping jamur dan pangsit goreng renyah.', 'https://gride.web.id/uploads/merchants/food_mie_ayam.jpg', '+623546810017', 'Pasar Bandar, Jl. KH Wachid Hasyim, Kediri', 'Kediri', -7.81534, 112.0057245, 'ACTIVE', true, 4.4, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Mie Ayam & Bakso Putra Solo', 'mie-ayam-bakso-putra-solo', 'Mie ayam Solo asli dengan bakso urat, resep turun temurun.', 'https://gride.web.id/uploads/merchants/food_mie_ayam.jpg', '+623546810018', 'Jl. Pemuda No.3, Kediri', 'Kediri', -7.8128734, 112.0206145, 'ACTIVE', true, 4.3, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Pecel Pudakit', 'pecel-pudakit', 'Pecel tumpang khas Kediri dengan bumbu kacang hangat dan lalapan segar.', 'https://gride.web.id/uploads/merchants/food_pecel.jpg', '+623546810019', 'Jl. Dhoho No.80, Kediri', 'Kediri', -7.8170353, 112.0133267, 'ACTIVE', true, 4.5, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'FOOD', 'Nasi Pecel Bu Darmo', 'nasi-pecel-bu-darmo', 'Pecel nasi dengan sayuran segar pilihan, buka pagi hari.', 'https://gride.web.id/uploads/merchants/food_pecel.jpg', '+623546810020', 'Jl. Banjaran 1 No.139-141, Kediri', 'Kediri', -7.8200596, 112.0279941, 'ACTIVE', true, 4.4, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Toko Jaya Mulya', 'toko-jaya-mulya', 'Toko kelontong lengkap dengan kebutuhan sehari-hari.', 'https://gride.web.id/uploads/merchants/toko_kelontong1.jpg', '+62354687721', 'Jl. Pattimura No.31, Kediri', 'Kediri', -7.8207244, 112.0131997, 'ACTIVE', true, 4.5, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Toko Kelontong Wijaya', 'toko-kelontong-wijaya', 'Kelontong lengkap harga bersahabat untuk warga sekitar.', 'https://gride.web.id/uploads/merchants/toko_kelontong2.jpg', '+62354687722', 'Gg. Balai Desa No.59, Kediri', 'Kediri', -7.8395491, 112.0181935, 'ACTIVE', true, 5.0, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Toko Laily', 'toko-laily', 'Toko kelontong dengan stok sembako dan jajanan lengkap.', 'https://gride.web.id/uploads/merchants/toko_kelontong3.jpg', '+62354687723', 'Jl. Sumber I No.4, Kediri', 'Kediri', -7.8449852, 112.024778, 'ACTIVE', true, 4.6, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Toko Sumber Ayem', 'toko-sumber-ayem', 'Sembako dan kebutuhan rumah tangga terlengkap di Pattimura.', 'https://gride.web.id/uploads/merchants/toko_kelontong4.jpg', '+62354687724', 'Jl. Pattimura No.18, Kediri', 'Kediri', -7.8207227, 112.0125028, 'ACTIVE', true, 4.4, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'UD. Salsabilla', 'ud-salsabilla', 'Grosir dan eceran sembako dengan harga distributor.', 'https://gride.web.id/uploads/merchants/toko_kelontong5.jpg', '+62354687725', 'Jl. Ngadisimo 1 Kavling Bali No.A1, Kediri', 'Kediri', -7.814939, 112.022507, 'ACTIVE', true, 4.9, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Toko Sembako Basmalah Kediri', 'toko-sembako-basmalah-kediri', 'Sembako pokok: beras, minyak, gula, telur dengan harga pasar.', 'https://gride.web.id/uploads/merchants/toko_sembako1.jpg', '+62354687726', 'Jl. Patiunus No.14, Kediri', 'Kediri', -7.8166463, 112.0182934, 'ACTIVE', true, 5.0, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Toko Sumber Barokah', 'toko-sumber-barokah', 'Toko sembako lengkap untuk kebutuhan desa dan sekitarnya.', 'https://gride.web.id/uploads/merchants/toko_sembako2.jpg', '+62354687727', 'Jl. Raya Bawang No.81, Kediri', 'Kediri', -7.8538649, 112.0596794, 'ACTIVE', true, 4.7, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Toko Sayur Dan Sembako Mbak Yanti', 'toko-sayur-dan-sembako-mbak-yanti', 'Sayur segar harian dan kebutuhan pokok.', 'https://gride.web.id/uploads/merchants/toko_kelontong6.jpg', '+62354687728', 'Jl. Veteran No.64, Kediri', 'Kediri', -7.8112475, 111.99304, 'ACTIVE', true, 4.6, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', '212 Mart Kediri', '212-mart-kediri', 'Minimarket modern dengan produk lengkap dan harga promo.', 'https://gride.web.id/uploads/merchants/toko_minimarket1.jpg', '+628561041812', 'Jl. Veteran No.25A, Kediri', 'Kediri', -7.811499, 111.992433, 'ACTIVE', true, 4.7, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Samudera Supermarket Kediri', 'samudera-supermarket-kediri', 'Supermarket lokal dengan fresh produce dan produk rumah tangga.', 'https://gride.web.id/uploads/merchants/toko_supermarket1.jpg', '+62354687730', 'Jl. Brigjen Katamso No.1, Kediri', 'Kediri', -7.8275336, 112.0107894, 'ACTIVE', true, 4.2, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Alfamart Ngronggo Kediri', 'alfamart-ngronggo-kediri', 'Minimarket 24 jam dengan produk siap saji dan kebutuhan harian.', 'https://gride.web.id/uploads/merchants/toko_minimarket2.jpg', '+628111500959', 'Jl. Urip Sumoharjo No.174, Kediri', 'Kediri', -7.8351933, 112.0090523, 'ACTIVE', true, 4.3, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Indomaret Mauni', 'indomaret-mauni', 'Minimarket dengan layanan antar dan promo setiap minggu.', 'https://gride.web.id/uploads/merchants/toko_minimarket3.jpg', '+62816500580', 'Jl. Mauni, Kediri', 'Kediri', -7.8284564, 112.0357438, 'ACTIVE', true, 4.1, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Alfamart Kilisuci', 'alfamart-kilisuci', 'Minimarket buka 24 jam di area kampus UNP Kediri.', 'https://gride.web.id/uploads/merchants/toko_minimarket1.jpg', '+62211500959', 'Jl. Kilisuci No.72, Kediri', 'Kediri', -7.8286602, 112.0172228, 'ACTIVE', true, 4.2, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Minimarket Semampir', 'minimarket-semampir', 'Minimarket lokal dengan produk segar dan kebutuhan harian.', 'https://gride.web.id/uploads/merchants/toko_minimarket2.jpg', '+628571234567', 'Jl. Mayor Bismo No.95, Kediri', 'Kediri', -7.7964601, 112.0092526, 'ACTIVE', true, 4.3, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Indomaret Balowerti', 'indomaret-balowerti', 'Minimarket 24 jam dengan fasilitas tarik tunai.', 'https://gride.web.id/uploads/merchants/toko_minimarket3.jpg', '+62816500580', 'Jl. Balowerti I No.64, Kediri', 'Kediri', -7.808934, 112.0160157, 'ACTIVE', true, 3.6, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Alfamart Patiunus', 'alfamart-patiunus', 'Minimarket lengkap dengan layanan pembayaran tagihan.', 'https://gride.web.id/uploads/merchants/toko_minimarket1.jpg', '+62211500959', 'Jl. Patiunus No.82, Kediri', 'Kediri', -7.8212681, 112.0173931, 'ACTIVE', true, 4.3, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Mekar Mart', 'mekar-mart', 'Mart lengkap dengan harga bersaing dan stok selalu tersedia.', 'https://gride.web.id/uploads/merchants/toko_supermarket2.jpg', '+62354687737', 'Jl. Brigjend Pol. IBH Pranoto No.68, Kediri', 'Kediri', -7.82956, 112.037272, 'ACTIVE', true, 4.5, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Alfamart Super Semar', 'alfamart-super-semar', 'Minimarket modern dengan produk siap saji hangat.', 'https://gride.web.id/uploads/merchants/toko_minimarket2.jpg', '+62211500959', 'Jl. Super Semar, Kediri', 'Kediri', -7.8440924, 112.0245811, 'ACTIVE', true, 4.3, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Laksanajaya Supermarket', 'laksanajaya-supermarket', 'Supermarket lokal tertua di Kediri dengan produk lengkap.', 'https://gride.web.id/uploads/merchants/toko_supermarket3.jpg', '+628223106000', 'Jl. Brawijaya No.73, Kediri', 'Kediri', -7.8136309, 112.0096048, 'ACTIVE', true, 4.2, 0, now(), now());
INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)
VALUES (2, 'MART', 'Alfamart Mauni Kel', 'alfamart-mauni-kel', 'Minimarket 24 jam dengan layanan antar pesanan.', 'https://gride.web.id/uploads/merchants/toko_minimarket3.jpg', '+62211500959', 'Jl. Mauni Kel No.87, Kediri', 'Kediri', -7.832373, 112.0455521, 'ACTIVE', true, 4.5, 0, now(), now());

-- menu items (5-6 items each)
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (3, 'Nasi Goreng Seafood Special', 'nasi-goreng-seafood-special', 'Nasi goreng dengan udang, cumi, dan bakso seafood.', 35000, 'https://gride.web.id/uploads/products/product_nasi_goreng.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (3, 'Ayam Bakar Madu', 'ayam-bakar-madu', 'Ayam bakar dengan olesan madu dan bumbu rempah.', 30000, 'https://gride.web.id/uploads/products/product_nasi_goreng.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (3, 'Ikan Gurame Goreng', 'ikan-gurame-goreng', 'Gurame goreng dengan sambal kecap.', 45000, 'https://gride.web.id/uploads/products/product_nasi_goreng.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (3, 'Es Teh Manis', 'es-teh-manis', 'Teh manis dingin segar.', 8000, 'https://gride.web.id/uploads/products/product_nasi_goreng.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (3, 'Jus Alpukat', 'jus-alpukat', 'Jus alpukat creamy dengan susu cokelat.', 18000, 'https://gride.web.id/uploads/products/product_nasi_goreng.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (4, 'Nasi Liwet Komplit', 'nasi-liwet-komplit', 'Nasi liwet dengan ayam suwir, tempe, telur, dan sambal.', 28000, 'https://gride.web.id/uploads/products/product_nasi_liwet.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (4, 'Ayam Goreng Serundeng', 'ayam-goreng-serundeng', 'Ayam goreng dengan taburan serundeng gurih.', 27000, 'https://gride.web.id/uploads/products/product_nasi_liwet.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (4, 'Sayur Lodeh', 'sayur-lodeh', 'Sayur santan dengan nangka dan labu.', 15000, 'https://gride.web.id/uploads/products/product_nasi_liwet.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (4, 'Es Jeruk Peras', 'es-jeruk-peras', 'Jeruk peras segar.', 10000, 'https://gride.web.id/uploads/products/product_nasi_liwet.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (4, 'Kerupuk & Sambal', 'kerupuk-sambal', 'Pelengkap makan.', 5000, 'https://gride.web.id/uploads/products/product_nasi_liwet.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (5, 'Nasi Goreng Cumi Asin', 'nasi-goreng-cumi-asin', 'Nasi goreng dengan potongan cumi asin.', 32000, 'https://gride.web.id/uploads/products/product_capcay.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (5, 'Cap Cay Seafood', 'cap-cay-seafood', 'Tumis sayur dengan seafood.', 35000, 'https://gride.web.id/uploads/products/product_capcay.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (5, 'Hakau & Siomay', 'hakau-siomay', 'Dim sum udang dan ayam, 4 pcs.', 25000, 'https://gride.web.id/uploads/products/product_capcay.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (5, 'Kopi Susu Gula Aren', 'kopi-susu-gula-aren', 'Kopi susu kekinian dengan gula aren.', 20000, 'https://gride.web.id/uploads/products/product_capcay.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (5, 'Milk Tea Brown Sugar', 'milk-tea-brown-sugar', 'Milk tea dengan brown sugar.', 22000, 'https://gride.web.id/uploads/products/product_capcay.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (6, 'Ikan Bakar Jimbaran', 'ikan-bakar-jimbaran', 'Ikan bakar bumbu kuning dengan plecing kangkung.', 55000, 'https://gride.web.id/uploads/products/product_ikan_bakar.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (6, 'Cumi Goreng Tepung', 'cumi-goreng-tepung', 'Cumi goreng crispy dengan saus sambal.', 35000, 'https://gride.web.id/uploads/products/product_ikan_bakar.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (6, 'Udang Saus Mentega', 'udang-saus-mentega', 'Udang besar dengan saus mentega.', 48000, 'https://gride.web.id/uploads/products/product_ikan_bakar.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (6, 'Nasi Putih', 'nasi-putih', 'Nasi putih hangat.', 5000, 'https://gride.web.id/uploads/products/product_ikan_bakar.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (6, 'Es Campur', 'es-campur', 'Es campur segar.', 15000, 'https://gride.web.id/uploads/products/product_ikan_bakar.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (7, 'Nasi Rendang Daging', 'nasi-rendang-daging', 'Nasi dengan rendang daging sapi.', 32000, 'https://gride.web.id/uploads/products/product_rendang.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (7, 'Ayam Pop', 'ayam-pop', 'Ayam pop dengan sambal merah khas.', 28000, 'https://gride.web.id/uploads/products/product_rendang.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (7, 'Dendeng Balado', 'dendeng-balado', 'Dendeng sapi dengan sambal balado.', 30000, 'https://gride.web.id/uploads/products/product_rendang.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (7, 'Telur Balado', 'telur-balado', 'Telur rebus dengan sambal balado.', 8000, 'https://gride.web.id/uploads/products/product_rendang.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (7, 'Es Teh Talua', 'es-teh-talua', 'Es teh khas Minang.', 12000, 'https://gride.web.id/uploads/products/product_rendang.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (8, 'Nasi Rendang Daging', 'nasi-rendang-daging', 'Nasi dengan rendang daging sapi.', 32000, 'https://gride.web.id/uploads/products/product_rendang.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (8, 'Ayam Pop', 'ayam-pop', 'Ayam pop dengan sambal merah khas.', 28000, 'https://gride.web.id/uploads/products/product_rendang.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (8, 'Dendeng Balado', 'dendeng-balado', 'Dendeng sapi dengan sambal balado.', 30000, 'https://gride.web.id/uploads/products/product_rendang.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (8, 'Telur Balado', 'telur-balado', 'Telur rebus dengan sambal balado.', 8000, 'https://gride.web.id/uploads/products/product_rendang.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (8, 'Es Teh Talua', 'es-teh-talua', 'Es teh khas Minang.', 12000, 'https://gride.web.id/uploads/products/product_rendang.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (9, 'Rawon Sapi Kediri', 'rawon-sapi-kediri', 'Rawon daging sapi dengan telur asin.', 30000, 'https://gride.web.id/uploads/products/product_rawon.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (9, 'Sate Kerang', 'sate-kerang', 'Sate kerang bumbu kacang khas Kediri.', 25000, 'https://gride.web.id/uploads/products/product_rawon.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (9, 'Sate Ayam Madura', 'sate-ayam-madura', '10 tusuk sate ayam dengan lontong.', 28000, 'https://gride.web.id/uploads/products/product_rawon.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (9, 'Tahu Tempe Goreng', 'tahu-tempe-goreng', 'Tahu tempe goreng pelengkap.', 8000, 'https://gride.web.id/uploads/products/product_rawon.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (9, 'Es Dawet', 'es-dawet', 'Es dawet ayu khas.', 10000, 'https://gride.web.id/uploads/products/product_rawon.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (10, 'Rawon Sapi Kediri', 'rawon-sapi-kediri', 'Rawon daging sapi dengan telur asin.', 30000, 'https://gride.web.id/uploads/products/product_rawon.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (10, 'Sate Kerang', 'sate-kerang', 'Sate kerang bumbu kacang khas Kediri.', 25000, 'https://gride.web.id/uploads/products/product_rawon.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (10, 'Sate Ayam Madura', 'sate-ayam-madura', '10 tusuk sate ayam dengan lontong.', 28000, 'https://gride.web.id/uploads/products/product_rawon.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (10, 'Tahu Tempe Goreng', 'tahu-tempe-goreng', 'Tahu tempe goreng pelengkap.', 8000, 'https://gride.web.id/uploads/products/product_rawon.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (10, 'Es Dawet', 'es-dawet', 'Es dawet ayu khas.', 10000, 'https://gride.web.id/uploads/products/product_rawon.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (11, 'Nasi Goreng Kampung', 'nasi-goreng-kampung', 'Nasi goreng dengan ayam kampung.', 22000, 'https://gride.web.id/uploads/products/product_nasi_goreng.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (11, 'Mie Goreng Jawa', 'mie-goreng-jawa', 'Mie goreng khas Jawa.', 20000, 'https://gride.web.id/uploads/products/product_nasi_goreng.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (11, 'Ayam Geprek', 'ayam-geprek', 'Ayam geprek sambal bawang.', 18000, 'https://gride.web.id/uploads/products/product_nasi_goreng.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (11, 'Es Jeruk', 'es-jeruk', 'Es jeruk segar.', 8000, 'https://gride.web.id/uploads/products/product_nasi_goreng.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (11, 'Nasi + Tempe Orek', 'nasi-tempe-orek', 'Paket hemat nasi dengan tempe orek.', 15000, 'https://gride.web.id/uploads/products/product_nasi_goreng.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (12, 'Ayam Geprek Sambal Bawang', 'ayam-geprek-sambal-bawang', 'Ayam geprek dengan sambal bawang.', 18000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (12, 'Ayam Geprek Mozarella', 'ayam-geprek-mozarella', 'Ayam geprek topping keju mozarella.', 25000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (12, 'Ayam Geprek Sambal Ijo', 'ayam-geprek-sambal-ijo', 'Ayam geprek dengan sambal hijau.', 20000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (12, 'Es Teh', 'es-teh', 'Teh es manis.', 5000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (12, 'Kerupuk', 'kerupuk', 'Kerupuk renyah.', 3000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (13, 'Ayam Geprek Sambal Bawang', 'ayam-geprek-sambal-bawang', 'Ayam geprek dengan sambal bawang.', 18000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (13, 'Ayam Geprek Mozarella', 'ayam-geprek-mozarella', 'Ayam geprek topping keju mozarella.', 25000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (13, 'Ayam Geprek Sambal Ijo', 'ayam-geprek-sambal-ijo', 'Ayam geprek dengan sambal hijau.', 20000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (13, 'Es Teh', 'es-teh', 'Teh es manis.', 5000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (13, 'Kerupuk', 'kerupuk', 'Kerupuk renyah.', 3000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (14, 'Ayam Geprek Sambal Bawang', 'ayam-geprek-sambal-bawang', 'Ayam geprek dengan sambal bawang.', 18000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (14, 'Ayam Geprek Mozarella', 'ayam-geprek-mozarella', 'Ayam geprek topping keju mozarella.', 25000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (14, 'Ayam Geprek Sambal Ijo', 'ayam-geprek-sambal-ijo', 'Ayam geprek dengan sambal hijau.', 20000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (14, 'Es Teh', 'es-teh', 'Teh es manis.', 5000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (14, 'Kerupuk', 'kerupuk', 'Kerupuk renyah.', 3000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (15, 'Ayam Geprek Sambal Bawang', 'ayam-geprek-sambal-bawang', 'Ayam geprek dengan sambal bawang.', 18000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (15, 'Ayam Geprek Mozarella', 'ayam-geprek-mozarella', 'Ayam geprek topping keju mozarella.', 25000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (15, 'Ayam Geprek Sambal Ijo', 'ayam-geprek-sambal-ijo', 'Ayam geprek dengan sambal hijau.', 20000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (15, 'Es Teh', 'es-teh', 'Teh es manis.', 5000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (15, 'Kerupuk', 'kerupuk', 'Kerupuk renyah.', 3000, 'https://gride.web.id/uploads/products/product_geprek.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (16, 'Bakso Jumbo Special', 'bakso-jumbo-special', 'Bakso jumbo isi telur dengan mie.', 25000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (16, 'Bakso Urat Halus', 'bakso-urat-halus', 'Bakso urat dengan mie dan bihun.', 20000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (16, 'Bakso Keju', 'bakso-keju', 'Bakso isi keju leleh.', 22000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (16, 'Pentol Goreng', 'pentol-goreng', '5 pcs pentol goreng.', 10000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (16, 'Es Teh Manis', 'es-teh-manis', 'Teh manis dingin.', 5000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (17, 'Bakso Jumbo Special', 'bakso-jumbo-special', 'Bakso jumbo isi telur dengan mie.', 25000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (17, 'Bakso Urat Halus', 'bakso-urat-halus', 'Bakso urat dengan mie dan bihun.', 20000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (17, 'Bakso Keju', 'bakso-keju', 'Bakso isi keju leleh.', 22000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (17, 'Pentol Goreng', 'pentol-goreng', '5 pcs pentol goreng.', 10000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (17, 'Es Teh Manis', 'es-teh-manis', 'Teh manis dingin.', 5000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (18, 'Bakso Jumbo Special', 'bakso-jumbo-special', 'Bakso jumbo isi telur dengan mie.', 25000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (18, 'Bakso Urat Halus', 'bakso-urat-halus', 'Bakso urat dengan mie dan bihun.', 20000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (18, 'Bakso Keju', 'bakso-keju', 'Bakso isi keju leleh.', 22000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (18, 'Pentol Goreng', 'pentol-goreng', '5 pcs pentol goreng.', 10000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (18, 'Es Teh Manis', 'es-teh-manis', 'Teh manis dingin.', 5000, 'https://gride.web.id/uploads/products/product_bakso.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (19, 'Mie Ayam Original', 'mie-ayam-original', 'Mie ayam dengan topping ayam cincang.', 15000, 'https://gride.web.id/uploads/products/product_mie_ayam.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (19, 'Mie Ayam Bakso', 'mie-ayam-bakso', 'Mie ayam dengan bakso urat.', 18000, 'https://gride.web.id/uploads/products/product_mie_ayam.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (19, 'Mie Ayam Pangsit', 'mie-ayam-pangsit', 'Mie ayam dengan pangsit goreng.', 20000, 'https://gride.web.id/uploads/products/product_mie_ayam.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (19, 'Bakso Goreng', 'bakso-goreng', 'Bakso goreng crispy, 4 pcs.', 8000, 'https://gride.web.id/uploads/products/product_mie_ayam.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (19, 'Es Teh', 'es-teh', 'Teh es.', 5000, 'https://gride.web.id/uploads/products/product_mie_ayam.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (20, 'Mie Ayam Original', 'mie-ayam-original', 'Mie ayam dengan topping ayam cincang.', 15000, 'https://gride.web.id/uploads/products/product_mie_ayam.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (20, 'Mie Ayam Bakso', 'mie-ayam-bakso', 'Mie ayam dengan bakso urat.', 18000, 'https://gride.web.id/uploads/products/product_mie_ayam.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (20, 'Mie Ayam Pangsit', 'mie-ayam-pangsit', 'Mie ayam dengan pangsit goreng.', 20000, 'https://gride.web.id/uploads/products/product_mie_ayam.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (20, 'Bakso Goreng', 'bakso-goreng', 'Bakso goreng crispy, 4 pcs.', 8000, 'https://gride.web.id/uploads/products/product_mie_ayam.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (20, 'Es Teh', 'es-teh', 'Teh es.', 5000, 'https://gride.web.id/uploads/products/product_mie_ayam.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (21, 'Pecel Tumpang Komplit', 'pecel-tumpang-komplit', 'Pecel dengan bumbu tumpang, tempe, telur.', 15000, 'https://gride.web.id/uploads/products/product_pecel.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (21, 'Pecel Nasi + Tahu Tempe', 'pecel-nasi-tahu-tempe', 'Pecel nasi dengan tahu tempe goreng.', 12000, 'https://gride.web.id/uploads/products/product_pecel.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (21, 'Tahu Campur', 'tahu-campur', 'Tahu campur khas Kediri.', 15000, 'https://gride.web.id/uploads/products/product_pecel.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (21, 'Sate Pletok', 'sate-pletok', 'Sate oncom khas Kediri.', 10000, 'https://gride.web.id/uploads/products/product_pecel.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (21, 'Es Dawet', 'es-dawet', 'Es dawet segar.', 6000, 'https://gride.web.id/uploads/products/product_pecel.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (22, 'Pecel Tumpang Komplit', 'pecel-tumpang-komplit', 'Pecel dengan bumbu tumpang, tempe, telur.', 15000, 'https://gride.web.id/uploads/products/product_pecel.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (22, 'Pecel Nasi + Tahu Tempe', 'pecel-nasi-tahu-tempe', 'Pecel nasi dengan tahu tempe goreng.', 12000, 'https://gride.web.id/uploads/products/product_pecel.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (22, 'Tahu Campur', 'tahu-campur', 'Tahu campur khas Kediri.', 15000, 'https://gride.web.id/uploads/products/product_pecel.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (22, 'Sate Pletok', 'sate-pletok', 'Sate oncom khas Kediri.', 10000, 'https://gride.web.id/uploads/products/product_pecel.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (22, 'Es Dawet', 'es-dawet', 'Es dawet segar.', 6000, 'https://gride.web.id/uploads/products/product_pecel.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (23, 'Indomie Goreng', 'indomie-goreng', 'Mie instan goreng, 1 sachet.', 3500, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (23, 'Minyak Goreng 2L', 'minyak-goreng-2l', 'Minyak goreng kemasan 2 liter.', 38000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (23, 'Beras Medium 5kg', 'beras-medium-5kg', 'Beras putih medium 5 kg.', 65000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (23, 'Gula Pasir 1kg', 'gula-pasir-1kg', 'Gula pasir 1 kilogram.', 15000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (23, 'Telur Ayam 1kg', 'telur-ayam-1kg', 'Telur ayam ras 1 kg.', 28000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (23, 'Sabun Mandi', 'sabun-mandi', 'Sabun mandi batang.', 4000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (24, 'Indomie Goreng', 'indomie-goreng', 'Mie instan goreng, 1 sachet.', 3500, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (24, 'Minyak Goreng 2L', 'minyak-goreng-2l', 'Minyak goreng kemasan 2 liter.', 38000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (24, 'Beras Medium 5kg', 'beras-medium-5kg', 'Beras putih medium 5 kg.', 65000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (24, 'Gula Pasir 1kg', 'gula-pasir-1kg', 'Gula pasir 1 kilogram.', 15000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (24, 'Telur Ayam 1kg', 'telur-ayam-1kg', 'Telur ayam ras 1 kg.', 28000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (24, 'Sabun Mandi', 'sabun-mandi', 'Sabun mandi batang.', 4000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (25, 'Indomie Goreng', 'indomie-goreng', 'Mie instan goreng, 1 sachet.', 3500, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (25, 'Minyak Goreng 2L', 'minyak-goreng-2l', 'Minyak goreng kemasan 2 liter.', 38000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (25, 'Beras Medium 5kg', 'beras-medium-5kg', 'Beras putih medium 5 kg.', 65000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (25, 'Gula Pasir 1kg', 'gula-pasir-1kg', 'Gula pasir 1 kilogram.', 15000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (25, 'Telur Ayam 1kg', 'telur-ayam-1kg', 'Telur ayam ras 1 kg.', 28000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (25, 'Sabun Mandi', 'sabun-mandi', 'Sabun mandi batang.', 4000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (26, 'Indomie Goreng', 'indomie-goreng', 'Mie instan goreng, 1 sachet.', 3500, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (26, 'Minyak Goreng 2L', 'minyak-goreng-2l', 'Minyak goreng kemasan 2 liter.', 38000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (26, 'Beras Medium 5kg', 'beras-medium-5kg', 'Beras putih medium 5 kg.', 65000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (26, 'Gula Pasir 1kg', 'gula-pasir-1kg', 'Gula pasir 1 kilogram.', 15000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (26, 'Telur Ayam 1kg', 'telur-ayam-1kg', 'Telur ayam ras 1 kg.', 28000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (26, 'Sabun Mandi', 'sabun-mandi', 'Sabun mandi batang.', 4000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (27, 'Indomie Goreng', 'indomie-goreng', 'Mie instan goreng, 1 sachet.', 3500, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (27, 'Minyak Goreng 2L', 'minyak-goreng-2l', 'Minyak goreng kemasan 2 liter.', 38000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (27, 'Beras Medium 5kg', 'beras-medium-5kg', 'Beras putih medium 5 kg.', 65000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (27, 'Gula Pasir 1kg', 'gula-pasir-1kg', 'Gula pasir 1 kilogram.', 15000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (27, 'Telur Ayam 1kg', 'telur-ayam-1kg', 'Telur ayam ras 1 kg.', 28000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (27, 'Sabun Mandi', 'sabun-mandi', 'Sabun mandi batang.', 4000, 'https://gride.web.id/uploads/products/product_indomie.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (28, 'Beras Premium 5kg', 'beras-premium-5kg', 'Beras pulen premium 5 kg.', 75000, 'https://gride.web.id/uploads/products/product_beras.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (28, 'Minyak Goreng 1L', 'minyak-goreng-1l', 'Minyak goreng 1 liter.', 20000, 'https://gride.web.id/uploads/products/product_beras.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (28, 'Tepung Terigu 1kg', 'tepung-terigu-1kg', 'Tepung terigu kunci biru.', 12000, 'https://gride.web.id/uploads/products/product_beras.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (28, 'Telur Ayam 1kg', 'telur-ayam-1kg', 'Telur ayam 1 kg.', 28000, 'https://gride.web.id/uploads/products/product_beras.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (28, 'Kecap Manis Bango', 'kecap-manis-bango', 'Kecap manis kemasan botol.', 22000, 'https://gride.web.id/uploads/products/product_beras.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (28, 'Garam Halus 500g', 'garam-halus-500g', 'Garam beryodium.', 5000, 'https://gride.web.id/uploads/products/product_beras.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (29, 'Beras Premium 5kg', 'beras-premium-5kg', 'Beras pulen premium 5 kg.', 75000, 'https://gride.web.id/uploads/products/product_beras.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (29, 'Minyak Goreng 1L', 'minyak-goreng-1l', 'Minyak goreng 1 liter.', 20000, 'https://gride.web.id/uploads/products/product_beras.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (29, 'Tepung Terigu 1kg', 'tepung-terigu-1kg', 'Tepung terigu kunci biru.', 12000, 'https://gride.web.id/uploads/products/product_beras.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (29, 'Telur Ayam 1kg', 'telur-ayam-1kg', 'Telur ayam 1 kg.', 28000, 'https://gride.web.id/uploads/products/product_beras.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (29, 'Kecap Manis Bango', 'kecap-manis-bango', 'Kecap manis kemasan botol.', 22000, 'https://gride.web.id/uploads/products/product_beras.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (29, 'Garam Halus 500g', 'garam-halus-500g', 'Garam beryodium.', 5000, 'https://gride.web.id/uploads/products/product_beras.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (30, 'Bayam Segar 250g', 'bayam-segar-250g', 'Bayam hijau segar.', 5000, 'https://gride.web.id/uploads/products/product_sayur.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (30, 'Cabai Merah 250g', 'cabai-merah-250g', 'Cabai merah keriting.', 12000, 'https://gride.web.id/uploads/products/product_sayur.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (30, 'Tomat 500g', 'tomat-500g', 'Tomat merah segar.', 8000, 'https://gride.web.id/uploads/products/product_sayur.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (30, 'Bawang Merah 250g', 'bawang-merah-250g', 'Bawang merah Brebes.', 10000, 'https://gride.web.id/uploads/products/product_sayur.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (30, 'Wortel 250g', 'wortel-250g', 'Wortel segar.', 7000, 'https://gride.web.id/uploads/products/product_sayur.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (30, 'Kentang 500g', 'kentang-500g', 'Kentang segar.', 12000, 'https://gride.web.id/uploads/products/product_sayur.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (31, 'Air Mineral 600ml', 'air-mineral-600ml', 'Air minum kemasan botol.', 4000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (31, 'Roti Tawar', 'roti-tawar', 'Roti tawar kupas isi.', 16000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (31, 'Susu UHT Cokelat', 'susu-uht-cokelat', 'Susu UHT cokelat 250ml.', 6000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (31, 'Chitato Sapi Panggang', 'chitato-sapi-panggang', 'Keripik kentang 68g.', 11000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (31, 'Sampo Sachet', 'sampo-sachet', 'Sachet sampo 2in1.', 2000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (31, 'Baterai AA', 'baterai-aa', 'Baterai alkaline AA 2 pcs.', 15000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (32, 'Beras Setara 5kg', 'beras-setara-5kg', 'Beras medium 5 kg.', 72000, 'https://gride.web.id/uploads/products/product_supermarket.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (32, 'Minyak Goreng 2L', 'minyak-goreng-2l', 'Minyak goreng 2 liter.', 38000, 'https://gride.web.id/uploads/products/product_supermarket.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (32, 'Gula Pasir 1kg', 'gula-pasir-1kg', 'Gula pasir kristal.', 15000, 'https://gride.web.id/uploads/products/product_supermarket.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (32, 'Deterjen Bubuk 800g', 'deterjen-bubuk-800g', 'Deterjen cuci pakaian.', 20000, 'https://gride.web.id/uploads/products/product_supermarket.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (32, 'Pasta Gigi 190g', 'pasta-gigi-190g', 'Pasta gigi keluarga.', 18000, 'https://gride.web.id/uploads/products/product_supermarket.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (32, 'Tisu Gulung 10 rolls', 'tisu-gulung-10-rolls', 'Tisu toilet 10 gulung.', 35000, 'https://gride.web.id/uploads/products/product_supermarket.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (33, 'Air Mineral 600ml', 'air-mineral-600ml', 'Air minum kemasan botol.', 4000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (33, 'Roti Tawar', 'roti-tawar', 'Roti tawar kupas isi.', 16000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (33, 'Susu UHT Cokelat', 'susu-uht-cokelat', 'Susu UHT cokelat 250ml.', 6000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (33, 'Chitato Sapi Panggang', 'chitato-sapi-panggang', 'Keripik kentang 68g.', 11000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (33, 'Sampo Sachet', 'sampo-sachet', 'Sachet sampo 2in1.', 2000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (33, 'Baterai AA', 'baterai-aa', 'Baterai alkaline AA 2 pcs.', 15000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (34, 'Air Mineral 600ml', 'air-mineral-600ml', 'Air minum kemasan botol.', 4000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (34, 'Roti Tawar', 'roti-tawar', 'Roti tawar kupas isi.', 16000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (34, 'Susu UHT Cokelat', 'susu-uht-cokelat', 'Susu UHT cokelat 250ml.', 6000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (34, 'Chitato Sapi Panggang', 'chitato-sapi-panggang', 'Keripik kentang 68g.', 11000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (34, 'Sampo Sachet', 'sampo-sachet', 'Sachet sampo 2in1.', 2000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (34, 'Baterai AA', 'baterai-aa', 'Baterai alkaline AA 2 pcs.', 15000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (35, 'Air Mineral 600ml', 'air-mineral-600ml', 'Air minum kemasan botol.', 4000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (35, 'Roti Tawar', 'roti-tawar', 'Roti tawar kupas isi.', 16000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (35, 'Susu UHT Cokelat', 'susu-uht-cokelat', 'Susu UHT cokelat 250ml.', 6000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (35, 'Chitato Sapi Panggang', 'chitato-sapi-panggang', 'Keripik kentang 68g.', 11000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (35, 'Sampo Sachet', 'sampo-sachet', 'Sachet sampo 2in1.', 2000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (35, 'Baterai AA', 'baterai-aa', 'Baterai alkaline AA 2 pcs.', 15000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (36, 'Air Mineral 600ml', 'air-mineral-600ml', 'Air minum kemasan botol.', 4000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (36, 'Roti Tawar', 'roti-tawar', 'Roti tawar kupas isi.', 16000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (36, 'Susu UHT Cokelat', 'susu-uht-cokelat', 'Susu UHT cokelat 250ml.', 6000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (36, 'Chitato Sapi Panggang', 'chitato-sapi-panggang', 'Keripik kentang 68g.', 11000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (36, 'Sampo Sachet', 'sampo-sachet', 'Sachet sampo 2in1.', 2000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (36, 'Baterai AA', 'baterai-aa', 'Baterai alkaline AA 2 pcs.', 15000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (37, 'Air Mineral 600ml', 'air-mineral-600ml', 'Air minum kemasan botol.', 4000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (37, 'Roti Tawar', 'roti-tawar', 'Roti tawar kupas isi.', 16000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (37, 'Susu UHT Cokelat', 'susu-uht-cokelat', 'Susu UHT cokelat 250ml.', 6000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (37, 'Chitato Sapi Panggang', 'chitato-sapi-panggang', 'Keripik kentang 68g.', 11000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (37, 'Sampo Sachet', 'sampo-sachet', 'Sachet sampo 2in1.', 2000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (37, 'Baterai AA', 'baterai-aa', 'Baterai alkaline AA 2 pcs.', 15000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (38, 'Air Mineral 600ml', 'air-mineral-600ml', 'Air minum kemasan botol.', 4000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (38, 'Roti Tawar', 'roti-tawar', 'Roti tawar kupas isi.', 16000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (38, 'Susu UHT Cokelat', 'susu-uht-cokelat', 'Susu UHT cokelat 250ml.', 6000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (38, 'Chitato Sapi Panggang', 'chitato-sapi-panggang', 'Keripik kentang 68g.', 11000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (38, 'Sampo Sachet', 'sampo-sachet', 'Sachet sampo 2in1.', 2000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (38, 'Baterai AA', 'baterai-aa', 'Baterai alkaline AA 2 pcs.', 15000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (39, 'Air Mineral 600ml', 'air-mineral-600ml', 'Air minum kemasan botol.', 4000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (39, 'Roti Tawar', 'roti-tawar', 'Roti tawar kupas isi.', 16000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (39, 'Susu UHT Cokelat', 'susu-uht-cokelat', 'Susu UHT cokelat 250ml.', 6000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (39, 'Chitato Sapi Panggang', 'chitato-sapi-panggang', 'Keripik kentang 68g.', 11000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (39, 'Sampo Sachet', 'sampo-sachet', 'Sachet sampo 2in1.', 2000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (39, 'Baterai AA', 'baterai-aa', 'Baterai alkaline AA 2 pcs.', 15000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (40, 'Air Mineral 600ml', 'air-mineral-600ml', 'Air minum kemasan botol.', 4000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (40, 'Roti Tawar', 'roti-tawar', 'Roti tawar kupas isi.', 16000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (40, 'Susu UHT Cokelat', 'susu-uht-cokelat', 'Susu UHT cokelat 250ml.', 6000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (40, 'Chitato Sapi Panggang', 'chitato-sapi-panggang', 'Keripik kentang 68g.', 11000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (40, 'Sampo Sachet', 'sampo-sachet', 'Sachet sampo 2in1.', 2000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (40, 'Baterai AA', 'baterai-aa', 'Baterai alkaline AA 2 pcs.', 15000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (41, 'Beras Setara 5kg', 'beras-setara-5kg', 'Beras medium 5 kg.', 72000, 'https://gride.web.id/uploads/products/product_supermarket.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (41, 'Minyak Goreng 2L', 'minyak-goreng-2l', 'Minyak goreng 2 liter.', 38000, 'https://gride.web.id/uploads/products/product_supermarket.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (41, 'Gula Pasir 1kg', 'gula-pasir-1kg', 'Gula pasir kristal.', 15000, 'https://gride.web.id/uploads/products/product_supermarket.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (41, 'Deterjen Bubuk 800g', 'deterjen-bubuk-800g', 'Deterjen cuci pakaian.', 20000, 'https://gride.web.id/uploads/products/product_supermarket.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (41, 'Pasta Gigi 190g', 'pasta-gigi-190g', 'Pasta gigi keluarga.', 18000, 'https://gride.web.id/uploads/products/product_supermarket.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (41, 'Tisu Gulung 10 rolls', 'tisu-gulung-10-rolls', 'Tisu toilet 10 gulung.', 35000, 'https://gride.web.id/uploads/products/product_supermarket.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (42, 'Air Mineral 600ml', 'air-mineral-600ml', 'Air minum kemasan botol.', 4000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (42, 'Roti Tawar', 'roti-tawar', 'Roti tawar kupas isi.', 16000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (42, 'Susu UHT Cokelat', 'susu-uht-cokelat', 'Susu UHT cokelat 250ml.', 6000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (42, 'Chitato Sapi Panggang', 'chitato-sapi-panggang', 'Keripik kentang 68g.', 11000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (42, 'Sampo Sachet', 'sampo-sachet', 'Sachet sampo 2in1.', 2000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());
INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)
VALUES (42, 'Baterai AA', 'baterai-aa', 'Baterai alkaline AA 2 pcs.', 15000, 'https://gride.web.id/uploads/products/product_mart.jpg', true, now(), now());

COMMIT;
