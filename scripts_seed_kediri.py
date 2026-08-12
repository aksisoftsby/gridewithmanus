#!/usr/bin/env python3
"""Generate seed SQL for 40 Kediri merchants + menu items. No schema changes, INSERT only."""
import re

UPLOAD = 'https://gride.web.id/uploads'

PRODUCT_PHOTOS = {
 'restoran-modern': 'product_nasi_goreng.jpg',
 'indonesian-family': 'product_nasi_liwet.jpg',
 'chinese-cafe': 'product_capcay.jpg',
 'seafood': 'product_ikan_bakar.jpg',
 'padang': 'product_rendang.jpg',
 'kediri-rawon': 'product_rawon.jpg',
 'warung-24jam': 'product_nasi_goreng.jpg',
 'geprek': 'product_geprek.jpg',
 'bakso': 'product_bakso.jpg',
 'mie-ayam': 'product_mie_ayam.jpg',
 'pecel': 'product_pecel.jpg',
 'kelontong': 'product_indomie.jpg',
 'sembako': 'product_beras.jpg',
 'mart': 'product_mart.jpg',
 'supermarket': 'product_supermarket.jpg',
 'sayur': 'product_sayur.jpg',
}

def slugify(name):
    s = re.sub(r"[^a-z0-9]+", "-", name.lower()).strip("-")
    return s

def q(s):
    return s.replace("'", "''")

def photo_for(key):
    return PRODUCT_PHOTOS.get(key, 'product_default.jpg')

# name, slug, lat, lon, address, phone, desc, photo, rating
FOOD = [
 ('Grand Panglima Resto', 'grand-panglima-resto', -7.8173333, 112.0161298, 'Jl. Panglima Polim No.25, Kediri', '+623546810001', 'Restoran modern khas Asia dengan suasana nyaman untuk keluarga. Menu populer: nasi goreng, seafood, dan aneka minuman.', 'food_grand_panglima.jpg', 4.7),
 ('Resto Keboen Rodjo Kediri', 'resto-keboen-rodjo-kediri', -7.7905769, 112.0091447, 'Jl. Mayor Bismo No.419, Kediri', '+623546810002', 'Restoran masakan Indonesia dengan suasana taman yang asri, favorit keluarga di Kediri.', 'food_keboen_rodjo.jpg', 4.5),
 ('Pandan Kediri Resto & Cafe', 'pandan-kediri-resto-cafe', -7.8155087, 112.018225, 'Jl. Hayam Wuruk No.40, Kediri', '+623546810003', 'Resto dan cafe bergaya oriental dengan menu Chinese food dan kopi pilihan.', 'food_pandan_resto.jpg', 4.2),
 ('Restaurant Pondok Kampoeng Nelayan', 'pondok-kampoeng-nelayan', -7.809187, 112.019364, 'Jl. Singosari No.30, Kediri', '+623546810004', 'Restoran seafood dengan konsep pondokan, ikan bakar dan cumi tepung jadi andalan.', 'food_pondok_nelayan.jpg', 4.5),
 ('Resto Sambel Idjo Kediri', 'resto-sambel-idjo-kediri', -7.8260441, 112.0107319, 'Jl. Panglima Sudirman Kel No.117, Kediri', '+623546810005', 'Masakan Padang autentik dengan sambal hijau khas. Rendang dan dendeng balado tersedia.', 'food_sambel_idjo.jpg', 4.2),
 ('Kayu Manis', 'kayu-manis-kediri', -7.8128202, 112.0539051, 'Jl. Erlangga No.10, Kediri', '+623546810006', 'Rumah makan Indonesia favorit warga Kediri dengan harga terjangkau dan porsi besar.', 'food_kayu_manis.jpg', 4.3),
 ('RM Mirasa 2', 'rm-mirasa-2', -7.8150613, 112.0166199, 'Jl. Hayam Wuruk No.58, Kediri', '+623546810007', 'Rumah makan Padang sederhana dengan lauk lengkap, buka dari pagi hingga malam.', 'food_rm_mirasa2.jpg', 4.3),
 ('Warung Leko', 'warung-leko', -7.8119762, 112.0104627, 'Jl. Ronggowarsito No.56-58, Kediri', '+623546810008', 'Warung legendaris khas Kediri terkenal dengan rawon dan sate kerangnya.', 'food_warung_leko.jpg', 4.4),
 ('Warung Gunung', 'warung-gunung', -7.8044922, 112.0047171, 'Jl. Ahmad Dahlan No.12, Kediri', '+623546810009', 'Warung makan 24 jam dengan menu rumahan yang variatif dan harga bersahabat.', 'food_warung_gunung.jpg', 4.1),
 ("Ayam Geprek Kremes Ndowerr", 'ayam-geprek-kremes-ndowerr', -7.8152342, 112.0182381, 'Jl. Hayam Wuruk No.109, Kediri', '+623546810010', 'Ayam geprek kremes renyah dengan sambal bawang level 1-10.', 'food_ayam_geprek.jpg', 4.4),
 ("Ayam Geprek Sa'i Bandar Lor", 'ayam-geprek-sai-bandar-lor', -7.8206695, 112.0057326, 'Jl. KH Wachid Hasyim No.120, Kediri', '+623546810011', 'Ayam geprek crispy dengan sambal korek pedas mantap, porsi kenyang.', 'food_geprek2.jpg', 4.3),
 ('Geprek Giyul', 'geprek-giyul', -7.8201773, 112.0214489, 'Jl. Dr. Sutomo No.56, Kediri', '+623546810012', 'Ayam geprek kekinian dengan pilihan sambal dan topping mozarella.', 'food_geprek2.jpg', 4.2),
 ('Ayam Geprek Sae Kota Kediri', 'ayam-geprek-sae-kota-kediri', -7.8165693, 112.0230718, 'Jl. Pahlawan Kusuma Bangsa No.8, Kediri', '+623546810013', 'Ayam geprek sae dengan ayam kampung pilihan, sambal ijo dan sambal bawang.', 'food_ayam_geprek.jpg', 4.5),
 ('Bakso Mama 1 Kediri', 'bakso-mama-1-kediri', -7.8243466, 112.0059302, 'Jl. KH Wachid Hasyim No.191, Kediri', '+623546810014', 'Bakso sapi jumbo dengan kuah gurih kaldunya, favorit mahasiswa dan keluarga.', 'food_bakso.jpg', 4.4),
 ('Bakso Barokah 313', 'bakso-barokah-313', -7.8203334, 112.028123, 'Jl. Letjend Suprapto No.107, Kediri', '+623546810015', 'Bakso bakar dan bakso halus dengan pentol jumbo, harga kaki lima.', 'food_bakso.jpg', 4.2),
 ('Bakso Kartini', 'bakso-kartini', -7.8091974, 112.0399962, 'Jl. Raden Ajeng Kartini No.5, Kediri', '+623546810016', 'Bakso urat dan bakso tahu dengan kuah bening segar.', 'food_bakso.jpg', 4.3),
 ('Mie Ayam Malioboro', 'mie-ayam-malioboro', -7.81534, 112.0057245, 'Pasar Bandar, Jl. KH Wachid Hasyim, Kediri', '+623546810017', 'Mie ayam pangsit dengan topping jamur dan pangsit goreng renyah.', 'food_mie_ayam.jpg', 4.4),
 ('Mie Ayam & Bakso Putra Solo', 'mie-ayam-bakso-putra-solo', -7.8128734, 112.0206145, 'Jl. Pemuda No.3, Kediri', '+623546810018', 'Mie ayam Solo asli dengan bakso urat, resep turun temurun.', 'food_mie_ayam.jpg', 4.3),
 ('Pecel Pudakit', 'pecel-pudakit', -7.8170353, 112.0133267, 'Jl. Dhoho No.80, Kediri', '+623546810019', 'Pecel tumpang khas Kediri dengan bumbu kacang hangat dan lalapan segar.', 'food_pecel.jpg', 4.5),
 ('Nasi Pecel Bu Darmo', 'nasi-pecel-bu-darmo', -7.8200596, 112.0279941, 'Jl. Banjaran 1 No.139-141, Kediri', '+623546810020', 'Pecel nasi dengan sayuran segar pilihan, buka pagi hari.', 'food_pecel.jpg', 4.4),
]

TOKO = [
 ('Toko Jaya Mulya', 'toko-jaya-mulya', -7.8207244, 112.0131997, 'Jl. Pattimura No.31, Kediri', '+62354687721', 'Toko kelontong lengkap dengan kebutuhan sehari-hari.', 'toko_kelontong1.jpg', 4.5),
 ('Toko Kelontong Wijaya', 'toko-kelontong-wijaya', -7.8395491, 112.0181935, 'Gg. Balai Desa No.59, Kediri', '+62354687722', 'Kelontong lengkap harga bersahabat untuk warga sekitar.', 'toko_kelontong2.jpg', 5.0),
 ('Toko Laily', 'toko-laily', -7.8449852, 112.024778, 'Jl. Sumber I No.4, Kediri', '+62354687723', 'Toko kelontong dengan stok sembako dan jajanan lengkap.', 'toko_kelontong3.jpg', 4.6),
 ('Toko Sumber Ayem', 'toko-sumber-ayem', -7.8207227, 112.0125028, 'Jl. Pattimura No.18, Kediri', '+62354687724', 'Sembako dan kebutuhan rumah tangga terlengkap di Pattimura.', 'toko_kelontong4.jpg', 4.4),
 ('UD. Salsabilla', 'ud-salsabilla', -7.814939, 112.022507, 'Jl. Ngadisimo 1 Kavling Bali No.A1, Kediri', '+62354687725', 'Grosir dan eceran sembako dengan harga distributor.', 'toko_kelontong5.jpg', 4.9),
 ('Toko Sembako Basmalah Kediri', 'toko-sembako-basmalah', -7.8166463, 112.0182934, 'Jl. Patiunus No.14, Kediri', '+62354687726', 'Sembako pokok: beras, minyak, gula, telur dengan harga pasar.', 'toko_sembako1.jpg', 5.0),
 ('Toko Sumber Barokah', 'toko-sumber-barokah', -7.8538649, 112.0596794, 'Jl. Raya Bawang No.81, Kediri', '+62354687727', 'Toko sembako lengkap untuk kebutuhan desa dan sekitarnya.', 'toko_sembako2.jpg', 4.7),
 ('Toko Sayur Dan Sembako Mbak Yanti', 'toko-sayur-sembako-mbak-yanti', -7.8112475, 111.99304, 'Jl. Veteran No.64, Kediri', '+62354687728', 'Sayur segar harian dan kebutuhan pokok.', 'toko_kelontong6.jpg', 4.6),
 ('212 Mart Kediri', '212-mart-kediri', -7.811499, 111.992433, 'Jl. Veteran No.25A, Kediri', '+628561041812', 'Minimarket modern dengan produk lengkap dan harga promo.', 'toko_minimarket1.jpg', 4.7),
 ('Samudera Supermarket Kediri', 'samudera-supermarket-kediri', -7.8275336, 112.0107894, 'Jl. Brigjen Katamso No.1, Kediri', '+62354687730', 'Supermarket lokal dengan fresh produce dan produk rumah tangga.', 'toko_supermarket1.jpg', 4.2),
 ('Alfamart Ngronggo Kediri', 'alfamart-ngronggo-kediri', -7.8351933, 112.0090523, 'Jl. Urip Sumoharjo No.174, Kediri', '+628111500959', 'Minimarket 24 jam dengan produk siap saji dan kebutuhan harian.', 'toko_minimarket2.jpg', 4.3),
 ('Indomaret Mauni', 'indomaret-mauni', -7.8284564, 112.0357438, 'Jl. Mauni, Kediri', '+62816500580', 'Minimarket dengan layanan antar dan promo setiap minggu.', 'toko_minimarket3.jpg', 4.1),
 ('Alfamart Kilisuci', 'alfamart-kilisuci', -7.8286602, 112.0172228, 'Jl. Kilisuci No.72, Kediri', '+62211500959', 'Minimarket buka 24 jam di area kampus UNP Kediri.', 'toko_minimarket1.jpg', 4.2),
 ('Minimarket Semampir', 'minimarket-semampir', -7.7964601, 112.0092526, 'Jl. Mayor Bismo No.95, Kediri', '+628571234567', 'Minimarket lokal dengan produk segar dan kebutuhan harian.', 'toko_minimarket2.jpg', 4.3),
 ('Indomaret Balowerti', 'indomaret-balowerti', -7.808934, 112.0160157, 'Jl. Balowerti I No.64, Kediri', '+62816500580', 'Minimarket 24 jam dengan fasilitas tarik tunai.', 'toko_minimarket3.jpg', 3.6),
 ('Alfamart Patiunus', 'alfamart-patiunus', -7.8212681, 112.0173931, 'Jl. Patiunus No.82, Kediri', '+62211500959', 'Minimarket lengkap dengan layanan pembayaran tagihan.', 'toko_minimarket1.jpg', 4.3),
 ('Mekar Mart', 'mekar-mart', -7.82956, 112.037272, 'Jl. Brigjend Pol. IBH Pranoto No.68, Kediri', '+62354687737', 'Mart lengkap dengan harga bersaing dan stok selalu tersedia.', 'toko_supermarket2.jpg', 4.5),
 ('Alfamart Super Semar', 'alfamart-super-semar', -7.8440924, 112.0245811, 'Jl. Super Semar, Kediri', '+62211500959', 'Minimarket modern dengan produk siap saji hangat.', 'toko_minimarket2.jpg', 4.3),
 ('Laksanajaya Supermarket', 'laksanajaya-supermarket', -7.8136309, 112.0096048, 'Jl. Brawijaya No.73, Kediri', '+628223106000', 'Supermarket lokal tertua di Kediri dengan produk lengkap.', 'toko_supermarket3.jpg', 4.2),
 ('Alfamart Mauni Kel', 'alfamart-mauni-kel', -7.832373, 112.0455521, 'Jl. Mauni Kel No.87, Kediri', '+62211500959', 'Minimarket 24 jam dengan layanan antar pesanan.', 'toko_minimarket3.jpg', 4.5),
]

FOOD_KEYS = ['restoran-modern','indonesian-family','chinese-cafe','seafood','padang','padang',
             'kediri-rawon','kediri-rawon','warung-24jam','geprek','geprek','geprek','geprek',
             'bakso','bakso','bakso','mie-ayam','mie-ayam','pecel','pecel']
TOKO_KEYS = ['kelontong','kelontong','kelontong','kelontong','kelontong','sembako','sembako','sayur',
             'mart','supermarket','mart','mart','mart','mart','mart','mart','mart','mart','supermarket','mart']

FOOD_ITEMS = {
 'restoran-modern': [
  ('Nasi Goreng Seafood Special', 'Nasi goreng dengan udang, cumi, dan bakso seafood.', 35000),
  ('Ayam Bakar Madu', 'Ayam bakar dengan olesan madu dan bumbu rempah.', 30000),
  ('Ikan Gurame Goreng', 'Gurame goreng dengan sambal kecap.', 45000),
  ('Es Teh Manis', 'Teh manis dingin segar.', 8000),
  ('Jus Alpukat', 'Jus alpukat creamy dengan susu cokelat.', 18000),
 ],
 'indonesian-family': [
  ('Nasi Liwet Komplit', 'Nasi liwet dengan ayam suwir, tempe, telur, dan sambal.', 28000),
  ('Ayam Goreng Serundeng', 'Ayam goreng dengan taburan serundeng gurih.', 27000),
  ('Sayur Lodeh', 'Sayur santan dengan nangka dan labu.', 15000),
  ('Es Jeruk Peras', 'Jeruk peras segar.', 10000),
  ('Kerupuk & Sambal', 'Pelengkap makan.', 5000),
 ],
 'chinese-cafe': [
  ('Nasi Goreng Cumi Asin', 'Nasi goreng dengan potongan cumi asin.', 32000),
  ('Cap Cay Seafood', 'Tumis sayur dengan seafood.', 35000),
  ('Hakau & Siomay', 'Dim sum udang dan ayam, 4 pcs.', 25000),
  ('Kopi Susu Gula Aren', 'Kopi susu kekinian dengan gula aren.', 20000),
  ('Milk Tea Brown Sugar', 'Milk tea dengan brown sugar.', 22000),
 ],
 'seafood': [
  ('Ikan Bakar Jimbaran', 'Ikan bakar bumbu kuning dengan plecing kangkung.', 55000),
  ('Cumi Goreng Tepung', 'Cumi goreng crispy dengan saus sambal.', 35000),
  ('Udang Saus Mentega', 'Udang besar dengan saus mentega.', 48000),
  ('Nasi Putih', 'Nasi putih hangat.', 5000),
  ('Es Campur', 'Es campur segar.', 15000),
 ],
 'padang': [
  ('Nasi Rendang Daging', 'Nasi dengan rendang daging sapi.', 32000),
  ('Ayam Pop', 'Ayam pop dengan sambal merah khas.', 28000),
  ('Dendeng Balado', 'Dendeng sapi dengan sambal balado.', 30000),
  ('Telur Balado', 'Telur rebus dengan sambal balado.', 8000),
  ('Es Teh Talua', 'Es teh khas Minang.', 12000),
 ],
 'kediri-rawon': [
  ('Rawon Sapi Kediri', 'Rawon daging sapi dengan telur asin.', 30000),
  ('Sate Kerang', 'Sate kerang bumbu kacang khas Kediri.', 25000),
  ('Sate Ayam Madura', '10 tusuk sate ayam dengan lontong.', 28000),
  ('Tahu Tempe Goreng', 'Tahu tempe goreng pelengkap.', 8000),
  ('Es Dawet', 'Es dawet ayu khas.', 10000),
 ],
 'warung-24jam': [
  ('Nasi Goreng Kampung', 'Nasi goreng dengan ayam kampung.', 22000),
  ('Mie Goreng Jawa', 'Mie goreng khas Jawa.', 20000),
  ('Ayam Geprek', 'Ayam geprek sambal bawang.', 18000),
  ('Es Jeruk', 'Es jeruk segar.', 8000),
  ('Nasi + Tempe Orek', 'Paket hemat nasi dengan tempe orek.', 15000),
 ],
 'geprek': [
  ('Ayam Geprek Sambal Bawang', 'Ayam geprek dengan sambal bawang.', 18000),
  ('Ayam Geprek Mozarella', 'Ayam geprek topping keju mozarella.', 25000),
  ('Ayam Geprek Sambal Ijo', 'Ayam geprek dengan sambal hijau.', 20000),
  ('Es Teh', 'Teh es manis.', 5000),
  ('Kerupuk', 'Kerupuk renyah.', 3000),
 ],
 'bakso': [
  ('Bakso Jumbo Special', 'Bakso jumbo isi telur dengan mie.', 25000),
  ('Bakso Urat Halus', 'Bakso urat dengan mie dan bihun.', 20000),
  ('Bakso Keju', 'Bakso isi keju leleh.', 22000),
  ('Pentol Goreng', '5 pcs pentol goreng.', 10000),
  ('Es Teh Manis', 'Teh manis dingin.', 5000),
 ],
 'mie-ayam': [
  ('Mie Ayam Original', 'Mie ayam dengan topping ayam cincang.', 15000),
  ('Mie Ayam Bakso', 'Mie ayam dengan bakso urat.', 18000),
  ('Mie Ayam Pangsit', 'Mie ayam dengan pangsit goreng.', 20000),
  ('Bakso Goreng', 'Bakso goreng crispy, 4 pcs.', 8000),
  ('Es Teh', 'Teh es.', 5000),
 ],
 'pecel': [
  ('Pecel Tumpang Komplit', 'Pecel dengan bumbu tumpang, tempe, telur.', 15000),
  ('Pecel Nasi + Tahu Tempe', 'Pecel nasi dengan tahu tempe goreng.', 12000),
  ('Tahu Campur', 'Tahu campur khas Kediri.', 15000),
  ('Sate Pletok', 'Sate oncom khas Kediri.', 10000),
  ('Es Dawet', 'Es dawet segar.', 6000),
 ],
}

TOKO_ITEMS = {
 'kelontong': [
  ('Indomie Goreng', 'Mie instan goreng, 1 sachet.', 3500),
  ('Minyak Goreng 2L', 'Minyak goreng kemasan 2 liter.', 38000),
  ('Beras Medium 5kg', 'Beras putih medium 5 kg.', 65000),
  ('Gula Pasir 1kg', 'Gula pasir 1 kilogram.', 15000),
  ('Telur Ayam 1kg', 'Telur ayam ras 1 kg.', 28000),
  ('Sabun Mandi', 'Sabun mandi batang.', 4000),
 ],
 'sembako': [
  ('Beras Premium 5kg', 'Beras pulen premium 5 kg.', 75000),
  ('Minyak Goreng 1L', 'Minyak goreng 1 liter.', 20000),
  ('Tepung Terigu 1kg', 'Tepung terigu kunci biru.', 12000),
  ('Telur Ayam 1kg', 'Telur ayam 1 kg.', 28000),
  ('Kecap Manis Bango', 'Kecap manis kemasan botol.', 22000),
  ('Garam Halus 500g', 'Garam beryodium.', 5000),
 ],
 'mart': [
  ('Air Mineral 600ml', 'Air minum kemasan botol.', 4000),
  ('Roti Tawar', 'Roti tawar kupas isi.', 16000),
  ('Susu UHT Cokelat', 'Susu UHT cokelat 250ml.', 6000),
  ('Chitato Sapi Panggang', 'Keripik kentang 68g.', 11000),
  ('Sampo Sachet', 'Sachet sampo 2in1.', 2000),
  ('Baterai AA', 'Baterai alkaline AA 2 pcs.', 15000),
 ],
 'supermarket': [
  ('Beras Setara 5kg', 'Beras medium 5 kg.', 72000),
  ('Minyak Goreng 2L', 'Minyak goreng 2 liter.', 38000),
  ('Gula Pasir 1kg', 'Gula pasir kristal.', 15000),
  ('Deterjen Bubuk 800g', 'Deterjen cuci pakaian.', 20000),
  ('Pasta Gigi 190g', 'Pasta gigi keluarga.', 18000),
  ('Tisu Gulung 10 rolls', 'Tisu toilet 10 gulung.', 35000),
 ],
 'sayur': [
  ('Bayam Segar 250g', 'Bayam hijau segar.', 5000),
  ('Cabai Merah 250g', 'Cabai merah keriting.', 12000),
  ('Tomat 500g', 'Tomat merah segar.', 8000),
  ('Bawang Merah 250g', 'Bawang merah Brebes.', 10000),
  ('Wortel 250g', 'Wortel segar.', 7000),
  ('Kentang 500g', 'Kentang segar.', 12000),
 ],
}

def main():
    out = '/home/ubuntu/apktest/seed_kediri.sql'
    with open(out, 'w') as f:
        f.write("-- Seed: 40 merchants in Kediri (20 FOOD + 20 MART) + 5+ menu items each. No schema changes.\n")
        f.write("BEGIN;\n\n")
        for name, slug, lat, lon, addr, phone, desc, photo, rating in FOOD:
            f.write(f"INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)\n")
            f.write(f"VALUES (2, 'FOOD', '{q(name)}', '{slugify(name)}', '{q(desc)}', '{UPLOAD}/merchants/{photo}', '{phone}', '{q(addr)}', 'Kediri', {lat}, {lon}, 'ACTIVE', true, {rating}, 0, now(), now());\n")
        for name, slug, lat, lon, addr, phone, desc, photo, rating in TOKO:
            f.write(f"INSERT INTO merchants (owner_id, type, name, slug, description, logo_url, phone, address_line, city, latitude, longitude, status, is_open, rating, total_orders, created_at, updated_at)\n")
            f.write(f"VALUES (2, 'MART', '{q(name)}', '{slugify(name)}', '{q(desc)}', '{UPLOAD}/merchants/{photo}', '{phone}', '{q(addr)}', 'Kediri', {lat}, {lon}, 'ACTIVE', true, {rating}, 0, now(), now());\n")
        f.write("\n-- menu items (5-6 items each)\n")
        keys = FOOD_KEYS + TOKO_KEYS
        base = 3
        for i, key in enumerate(keys):
            mid = base + i
            items = FOOD_ITEMS[key] if key in FOOD_ITEMS else TOKO_ITEMS[key]
            ph = photo_for(key)
            for (iname, idesc, price) in items:
                f.write(f"INSERT INTO menu_items (merchant_id, name, slug, description, price, image_url, is_available, created_at, updated_at)\n")
                f.write(f"VALUES ({mid}, '{q(iname)}', '{slugify(iname)}', '{q(idesc)}', {price}, '{UPLOAD}/products/{ph}', true, now(), now());\n")
        f.write("\nCOMMIT;\n")

if __name__ == '__main__':
    main()
