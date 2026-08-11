# TASK: Build Public Branding Website / SuperApp Landing Pages for Existing Laravel Application

Saya memiliki aplikasi Laravel yang **sudah berjalan dan sudah memiliki admin area, authentication, database, route, API, dan berbagai fitur backend**.

Sekarang tugas kamu adalah mengembangkan **PUBLIC WEBSITE / BRANDING WEBSITE** dari aplikasi Laravel tersebut.

## PENTING

Sebelum melakukan perubahan apa pun:

1. Baca dan pahami seluruh struktur project Laravel.
2. Baca file `app-info.md`.
3. Baca dan pahami `schema.sql`.
4. Periksa seluruh route Laravel yang sudah tersedia.
5. Periksa API yang sudah tersedia.
6. Periksa model, controller, migration, service, repository, middleware, helper, dan komponen lain yang sudah ada.
7. Periksa struktur Blade/layout/frontend yang sudah ada.
8. Periksa struktur admin area yang sudah ada.
9. Jangan membuat sistem baru jika sebenarnya fitur tersebut sudah tersedia.
10. Gunakan dan manfaatkan database, model, API, route, helper, dan komponen yang sudah ada.
11. Jangan merusak fitur existing.
12. Jangan mengubah logic admin yang sudah berjalan kecuali memang diperlukan untuk menambahkan fitur baru yang diminta.
13. Jangan membuat duplicate model/controller/table apabila struktur existing sudah bisa digunakan.
14. Jika ada fitur yang belum lengkap, tambahkan dengan mengikuti pola arsitektur project yang sudah ada.

---

# TUJUAN UTAMA

Buat **public-facing branding website / landing website** untuk aplikasi ini.

Admin area sebenarnya sudah tersedia di:

`/admin`

Admin login dan halaman internal **tidak perlu ditampilkan di public website**.

Public website harus terasa seperti website resmi sebuah **SuperApp / marketplace platform**, bukan seperti halaman admin.

Fokus utama:

* Branding
* Landing page
* Merchant discovery
* Product discovery
* Promotion discovery
* Testimonials
* News
* Contact
* SEO-friendly public pages
* Responsive design
* Mobile friendly
* Modern UI
* Performance
* Reusable Blade components

---

# 1. PUBLIC HOME / LANDING PAGE

Buat halaman:

`/`

Halaman ini menjadi landing page utama dari SuperApp.

Landing page harus memiliki struktur yang profesional dan modern.

## Hero Section

Buat hero section yang menjelaskan:

* Apa aplikasi ini
* Apa manfaatnya
* Kenapa pengguna harus menggunakan platform ini
* CTA untuk melihat merchant
* CTA untuk melihat produk

Gunakan data branding dari aplikasi yang sudah tersedia.

Jangan hardcode nama aplikasi jika informasi tersebut sudah tersedia di settings/config/database.

---

## Section: Kenapa Memilih Kami

Buat section "Why Choose Us / Kenapa Memilih Kami".

Isi dapat berupa beberapa benefit utama seperti:

* Banyak merchant
* Banyak pilihan produk
* Promo menarik
* Mudah digunakan
* Transaksi praktis
* Merchant terpercaya

Namun jangan membuat klaim yang tidak didukung aplikasi.

Jika ada data atau konfigurasi existing yang relevan, gunakan data tersebut.

Buat component yang reusable.

---

# 2. SECTION JASA / LAYANAN

Buat section:

`Jasa Kami` / `Layanan Kami`

Tampilkan layanan utama yang memang tersedia pada aplikasi.

Jika aplikasi memiliki beberapa jenis layanan, gunakan data existing.

Jika belum ada data dinamis untuk layanan, buat struktur yang mudah diedit dari settings/admin apabila memang sesuai dengan arsitektur project.

Jangan membuat fitur kompleks yang tidak diperlukan.

---

# 3. MERCHANT LISTING DI HOME

Di homepage tampilkan beberapa merchant.

Contoh:

* Merchant terbaru
* Merchant unggulan jika sistem memang mendukung
* Merchant berdasarkan kategori

Gunakan data database existing.

Buat:

* Card merchant
* Logo/foto merchant
* Nama merchant
* Kategori
* Lokasi jika tersedia
* Informasi singkat
* CTA "Lihat Merchant"

Jumlah merchant di homepage dibatasi agar tidak terlalu panjang.

CTA:

`Lihat Semua Merchant`

---

# 4. PRODUCT LISTING DI HOME

Tampilkan produk terbaru di homepage.

Card produk minimal memiliki:

* Foto
* Nama produk
* Harga
* Merchant
* Kategori jika tersedia
* Status jika relevan
* CTA/detail

Gunakan data existing.

CTA:

`Lihat Semua Produk`

---

# 5. PROMOTION / PROMO SECTION

Jika aplikasi sudah memiliki data promo, tampilkan promo di homepage.

Buat:

* Promo card
* Banner/image
* Judul
* Deskripsi singkat
* Periode promo jika tersedia
* Merchant jika relevan
* CTA detail

Jika belum ada sistem promo tetapi ada tabel/field yang bisa digunakan, manfaatkan existing structure.

Jika promo belum tersedia sama sekali, jangan membuat sistem besar tanpa alasan. Buat struktur yang konsisten dengan arsitektur existing dan dokumentasikan kebutuhan tambahan tersebut.

---

# 6. TESTIMONIAL

Tambahkan section testimonial pada homepage.

Homepage menampilkan beberapa testimonial terbaru.

Setiap testimonial minimal memiliki:

* Nama
* Foto/avatar
* Rating jika relevan
* Isi testimonial
* Tanggal testimonial
* Jabatan/role/location jika memang dibutuhkan
* Status published/active
* Urutan berdasarkan tanggal

---

## TESTIMONIAL DATABASE

Periksa `schema.sql` terlebih dahulu.

Jika tabel/field testimonial BELUM ADA:

Buat database structure yang proper.

Minimal konsep data:

* id
* name
* photo
* content
* rating
* role/title jika dibutuhkan
* location jika dibutuhkan
* published/active status
* testimonial date
* created_at
* updated_at

Pastikan testimonial memiliki **tanggal yang jelas** sehingga dapat diurutkan berdasarkan tanggal.

Gunakan migration jika project menggunakan migration.

Update `schema.sql` apabila project memang menggunakan schema.sql sebagai sumber struktur database.

Jangan membuat duplicate table apabila testimonial sebenarnya sudah tersedia.

---

# 7. TESTIMONIAL ADMIN

Jika testimonial baru dibuat, integrasikan ke admin area existing.

Tambahkan:

* Testimonial listing
* Create testimonial
* Edit testimonial
* Delete testimonial
* Publish/unpublish
* Upload photo
* Rating
* Date
* Sorting

Tambahkan menu:

`Testimonials`

ke navigation/sidebar admin yang sudah ada.

Ikuti style, authorization, validation, controller pattern, route pattern, layout dan komponen admin existing.

Jangan membuat admin UI yang terpisah dari sistem existing.

---

# 8. MERCHANT PAGE

Buat public page:

`/merchant`

atau gunakan route existing jika sudah tersedia.

Halaman merchant harus memiliki:

## Sidebar kategori merchant

Tampilkan kategori merchant di sidebar.

Kategori dapat dipilih.

Contoh:

* Semua
* Kuliner
* Fashion
* Elektronik
* Jasa
* Kesehatan
* dan kategori lain sesuai database.

Jangan hardcode kategori apabila sudah tersedia di database.

---

## Merchant Listing

Merchant harus:

* Diurutkan berdasarkan terbaru
* Pagination
* Responsive
* Search jika sistem existing mendukung atau jika mudah ditambahkan
* Filter kategori
* Card merchant

Urutan default:

`created_at DESC`

atau field tanggal terbaru yang memang digunakan aplikasi.

Gunakan pagination Laravel.

Jangan mengambil seluruh merchant sekaligus jika datanya besar.

---

# 9. MERCHANT DETAIL PAGE

Buat layout detail merchant yang profesional.

Contoh route:

`/merchant/{slug}`

Gunakan slug jika sistem existing menggunakan slug.

Detail merchant minimal:

* Cover/banner merchant jika tersedia
* Logo/foto merchant
* Nama merchant
* Kategori
* Deskripsi
* Alamat
* Nomor telepon jika tersedia
* WhatsApp jika tersedia
* Website/social media jika tersedia
* Jam operasional jika tersedia
* Lokasi/map jika tersedia
* Status merchant
* Informasi lainnya yang memang tersedia di database

Tambahkan CTA yang relevan.

---

## PRODUCT LISTING DI DETAIL MERCHANT

Pada halaman merchant detail harus ada:

`Produk dari Merchant Ini`

Tampilkan produk milik merchant tersebut.

Produk:

* Foto
* Nama
* Harga
* Kategori
* Status
* CTA detail

Tambahkan pagination jika jumlah produk besar.

Buat section kategori produk jika memang relevan.

---

# 10. PRODUCT PAGE

Buat public page:

`/product`

atau gunakan route existing.

Halaman produk memiliki:

## Sidebar kategori produk

Tampilkan kategori produk dari database.

Kategori dapat dipilih.

---

## Product Listing

Default:

`produk terbaru`

Urutkan berdasarkan:

`created_at DESC`

Gunakan Laravel pagination.

Card produk:

* Foto
* Nama
* Harga
* Merchant
* Kategori
* Status jika relevan
* CTA

---

# 11. PRODUCT DETAIL

Buat halaman:

`/product/{slug}`

atau mengikuti route existing.

Detail produk minimal:

* Foto utama
* Gallery jika tersedia
* Nama produk
* Harga
* Deskripsi
* Kategori
* Merchant
* Informasi merchant
* Lokasi merchant jika tersedia
* CTA ke merchant
* Produk lain dari merchant
* Produk terkait jika memungkinkan

Jangan menampilkan informasi database/internal yang seharusnya tidak public.

---

# 12. NEWS

Buat halaman:

`/news`

News harus menjadi bagian dari public branding website.

News listing:

* News terbaru
* Pagination
* Kategori news
* Sidebar kategori
* Featured image
* Judul
* Excerpt
* Tanggal
* Kategori
* CTA baca selengkapnya

Default sorting:

`published_at DESC`

atau tanggal publikasi yang sesuai dengan schema existing.

---

# 13. NEWS DATABASE

Periksa `schema.sql`.

Jika sistem news belum tersedia, tambahkan struktur database yang proper.

Minimal:

## News

* id
* category_id
* title
* slug
* excerpt
* content
* featured_image
* status
* published_at
* created_at
* updated_at

## News Category

* id
* name
* slug
* description jika diperlukan
* status jika diperlukan
* created_at
* updated_at

Gunakan migration jika project menggunakan migration.

Update `schema.sql` sesuai pola project.

Pastikan:

* slug unique
* relationship category → news
* news → category
* published news hanya tampil di public
* draft tidak tampil di public

---

# 14. NEWS DETAIL

Buat:

`/news/{slug}`

Detail news harus memiliki:

* Featured image
* Judul
* Kategori
* Tanggal
* Author jika sistem memiliki author
* Content
* Related news
* Previous/next news jika sesuai
* CTA kembali ke news

Gunakan semantic HTML.

Pastikan URL SEO-friendly.

---

# 15. NEWS ADMIN

Jika news belum tersedia di admin:

Tambahkan ke admin.

Menu utama:

`News`

Submenu:

* All News
* Add News
* Categories

Fitur:

* Create
* Edit
* Delete
* Draft/published
* Featured image
* Publish date
* Category
* Slug
* Excerpt
* Content editor jika project sudah memiliki editor
* Validation

Tambahkan:

`News`

dan:

`News Categories`

ke navigation/admin sidebar yang sudah ada.

Ikuti pola admin existing.

---

# 16. CONTACT PAGE

Buat halaman:

`/contact`

Halaman kontak harus memiliki:

* Nama aplikasi/perusahaan
* Alamat
* Nomor telepon
* Email
* WhatsApp jika tersedia
* Jam operasional jika tersedia
* Social media jika tersedia
* Contact form jika sistem memang membutuhkan
* Google Maps / map embed

---

# 17. GOOGLE MAP / OFFICE LOCATION

Tambahkan map lokasi kantor.

Alamat kantor dan koordinat/map embed **harus dapat diedit dari Admin Settings**.

Jangan hardcode alamat kantor di Blade.

Gunakan setting existing apabila sudah tersedia.

Jika belum ada setting:

Tambahkan field setting yang sesuai dengan sistem settings existing.

Minimal:

* Office address
* Latitude
* Longitude
* Google Maps URL/embed URL jika diperlukan

Public contact page mengambil data dari settings.

---

# 18. ADMIN SETTINGS

Periksa halaman settings admin.

Pastikan branding/public website dapat mengambil data dari settings.

Minimal setting yang sebaiknya tersedia:

### Branding

* Application name
* Logo
* Favicon
* Tagline
* Description
* Primary color
* Secondary color

### Contact

* Address
* Phone
* WhatsApp
* Email
* Office hours

### Social Media

* Facebook
* Instagram
* TikTok
* YouTube
* X/Twitter jika tersedia

### Map

* Latitude
* Longitude
* Google Maps URL/embed URL

Gunakan settings system existing apabila sudah tersedia.

Jangan membuat settings system kedua.

---

# 19. BRANDING & DESIGN

Sebelum membuat UI:

Baca layout Laravel existing.

Cari:

* warna aplikasi
* logo
* favicon
* font
* typography
* button style
* spacing
* border radius
* icon style
* existing CSS
* existing Tailwind/Bootstrap configuration
* theme variables

Gunakan branding aplikasi tersebut secara konsisten.

Jika aplikasi sudah memiliki logo:

**Gunakan logo existing.**

Jangan membuat logo baru secara random.

Jika logo tersedia sebagai asset, gunakan asset tersebut.

Jika hanya tersedia referensi logo di Blade/layout, pahami penggunaannya dan sesuaikan public website.

---

# 20. RESPONSIVE DESIGN

Website harus responsive.

Prioritas:

* Mobile
* Tablet
* Desktop

Pastikan:

* Sidebar kategori menjadi drawer/dropdown di mobile
* Grid merchant responsive
* Grid product responsive
* News responsive
* Navbar responsive
* Hero responsive
* Footer responsive
* Images tidak overflow
* Typography responsive

---

# 21. SEO

Public website harus SEO-friendly.

Tambahkan:

* `<title>` dinamis
* Meta description
* Canonical URL
* Open Graph
* Twitter/X card jika relevan
* Semantic HTML
* Proper heading hierarchy
* Alt text gambar
* SEO-friendly slug
* Sitemap jika sistem belum memiliki
* robots.txt jika diperlukan

Jangan membuat duplicate SEO implementation jika sudah tersedia.

---

# 22. PERFORMANCE

Perhatikan performance.

Jangan:

* Query database berulang di Blade
* N+1 queries
* Load semua data sekaligus
* Load image original yang sangat besar jika ada thumbnail
* Query kategori berulang kali

Gunakan:

* eager loading
* pagination
* caching jika memang sesuai
* existing image optimization
* reusable components

Contoh:

Merchant:

`with('category')`

Product:

`with(['merchant', 'category'])`

Sesuaikan dengan relationship sebenarnya di project.

---

# 23. PUBLIC NAVIGATION

Buat public navigation yang minimal dan jelas.

Contoh:

* Home
* Merchant
* Produk
* Promo
* News
* Tentang Kami jika memang relevan
* Kontak

Tambahkan CTA utama bila diperlukan.

**Jangan tampilkan link `/admin` pada public navbar.**

Admin tetap dapat diakses melalui `/admin`, tetapi tidak perlu dipromosikan di public website.

---

# 24. FOOTER

Buat footer profesional.

Minimal:

* Logo
* Nama aplikasi
* Short description
* Navigation
* Merchant
* Produk
* News
* Kontak
* Address
* Phone
* Email
* Social media
* Copyright
* Privacy Policy jika tersedia
* Terms jika tersedia

Gunakan data settings.

---

# 25. ERROR / EMPTY STATE

Semua halaman listing harus memiliki empty state.

Contoh:

* Belum ada merchant
* Belum ada produk
* Belum ada news
* Belum ada testimonial
* Tidak ada hasil berdasarkan kategori

Jangan menampilkan error atau halaman kosong yang terlihat rusak.

---

# 26. SECURITY

Public page tidak boleh membocorkan:

* Password
* Internal IDs jika tidak diperlukan
* Admin information
* Private fields
* API secrets
* Database credentials
* Internal admin URLs
* Sensitive merchant fields

Gunakan validation dan authorization pada admin.

Pastikan HTML content dari news/testimonial tidak membuka XSS vulnerability.

---

# 27. ROUTING

Periksa route existing terlebih dahulu.

Jika route public sudah ada:

**gunakan route tersebut.**

Jangan membuat duplicate route.

Jika belum ada, buat route dengan struktur yang konsisten.

Gunakan named routes.

Contoh:

* `home`
* `merchants.index`
* `merchants.show`
* `products.index`
* `products.show`
* `news.index`
* `news.show`
* `contact`

Sesuaikan dengan naming convention project existing.

---

# 28. CONTROLLER / MODEL / SERVICE

Ikuti arsitektur project existing.

Jangan menaruh query database kompleks langsung di Blade.

Jika project menggunakan:

* Repository
* Service
* Action
* Resource
* DTO

maka gunakan pola tersebut.

Jika project menggunakan controller sederhana, ikuti pola tersebut.

Tujuannya adalah **konsistensi dengan codebase existing**.

---

# 29. DATABASE / SCHEMA

Sebelum membuat tabel baru:

Periksa:

* `schema.sql`
* migrations
* existing models
* existing tables

Pastikan tidak ada duplicate data structure.

Jika ada data yang sudah tersedia, gunakan data tersebut.

Jika memang belum tersedia, tambahkan migration + model + relationship + admin CRUD sesuai kebutuhan.

Setelah perubahan database, update `schema.sql` jika project memang menggunakannya.

---

# 30. ADMIN MENU

Pastikan admin navigation akhirnya memiliki menu yang masuk akal.

Contoh:

Dashboard

Merchant

* All Merchants
* Categories

Products

* All Products
* Categories

Promotions

Testimonials

News

* All News
* Categories

Settings

Jangan mengubah menu existing yang tidak berkaitan.

Sesuaikan dengan menu yang sebenarnya sudah ada.

---

# 31. DATA RELATIONSHIP

Pastikan relationship database benar.

Contoh:

Merchant:

* belongsTo category jika struktur memang demikian
* hasMany products

Product:

* belongsTo merchant
* belongsTo category

News:

* belongsTo category

News Category:

* hasMany news

Testimonial:

* standalone atau relationship sesuai schema existing

Jangan mengasumsikan nama tabel/field. Baca schema terlebih dahulu.

---

# 32. IMAGE HANDLING

Periksa bagaimana project saat ini menangani upload image.

Gunakan mekanisme existing.

Jangan membuat sistem storage kedua.

Perhatikan:

* Logo
* Merchant logo
* Merchant cover
* Product image
* News featured image
* Testimonial photo

Pastikan public URL image benar.

Gunakan fallback image jika gambar tidak tersedia.

---

# 33. HOMEPAGE DATA

Homepage sebaiknya menggunakan data real dari database.

Contoh:

Latest merchants:

`latest()->take(...)`

Latest products:

`latest()->take(...)`

Latest news:

`published()->latest()->take(...)`

Latest testimonials:

`active()->latest('testimonial_date')->take(...)`

Sesuaikan dengan model/scope yang sebenarnya ada.

Jangan menggunakan dummy data jika data production sudah tersedia.

---

# 34. PUBLIC LAYOUT

Buat reusable public layout, misalnya:

* `layouts.public`
* `components.public.navbar`
* `components.public.footer`
* `components.public.merchant-card`
* `components.public.product-card`
* `components.public.news-card`
* `components.public.testimonial-card`

Namun ikuti struktur Blade project existing.

Jangan membuat struktur baru jika project sudah memiliki pola component/layout sendiri.

---

# 35. FINAL AUDIT

Setelah implementasi selesai:

Periksa seluruh public page:

1. `/`
2. `/merchant`
3. `/merchant/{slug}`
4. `/product`
5. `/product/{slug}`
6. `/news`
7. `/news/{slug}`
8. `/contact`

Pastikan semua dapat diakses tanpa login.

Kemudian periksa admin:

1. `/admin`
2. Merchant
3. Product
4. Promotion
5. Testimonial
6. News
7. News Categories
8. Settings

Pastikan admin tetap berjalan.

---

# 36. TESTING

Lakukan testing setelah implementasi.

Minimal:

* route test
* database relationship test
* public page rendering
* pagination
* category filtering
* merchant detail
* product detail
* news detail
* testimonial CRUD
* news CRUD
* category CRUD
* image upload
* settings update
* map display
* responsive layout jika memungkinkan

Perbaiki semua error yang ditemukan.

Jalankan:

* Laravel route check
* migration check
* application tests
* lint/build frontend jika tersedia

Jangan berhenti hanya karena halaman sudah terlihat.

---

# 37. HAL YANG HARUS DITAMBAHKAN JIKA TERLUPA

Selama membaca project, jika kamu menemukan kebutuhan penting yang belum saya sebutkan, tambahkan **selama masih berhubungan langsung dengan public branding website**.

Contohnya:

* About Us
* FAQ
* Privacy Policy
* Terms & Conditions
* SEO settings
* Sitemap
* Open Graph image
* favicon
* 404 page
* search
* breadcrumb
* related products
* related news
* share buttons
* social links
* CTA
* newsletter jika memang sesuai
* structured data/schema.org jika relevan

Tetapi jangan membuat fitur besar yang tidak diperlukan hanya untuk menambah jumlah fitur.

Prioritaskan fitur yang membuat website terasa seperti **website resmi SuperApp yang lengkap, profesional, dan siap dipublikasikan**.

---

# ATURAN TERAKHIR

**Jangan asal coding.**

Workflow yang harus kamu lakukan:

### STEP 1

Audit project.

### STEP 2

Baca:

* `app-info.md`
* `schema.sql`
* routes
* models
* controllers
* migrations
* Blade/layout
* admin
* API

### STEP 3

Buat mapping:

`Existing Feature → Reuse`

`Missing Feature → Implement`

### STEP 4

Implement database terlebih dahulu jika diperlukan.

### STEP 5

Implement model/relationship.

### STEP 6

Implement admin CRUD untuk fitur baru.

### STEP 7

Implement public controllers/routes.

### STEP 8

Implement public Blade/layout/components.

### STEP 9

Implement SEO/responsive/performance.

### STEP 10

Test seluruh halaman.

---

## HASIL AKHIR YANG DIHARAPKAN

Aplikasi Laravel ini harus memiliki:

### PUBLIC

* Professional SuperApp landing page
* Merchant listing
* Merchant category
* Merchant detail
* Merchant products
* Product listing
* Product category
* Product detail
* Merchant information on product
* Promotion section
* Testimonials
* News listing
* News categories
* News detail
* Contact
* Office map
* Responsive navigation
* Footer
* SEO
* Empty states
* Responsive mobile/tablet/desktop

### ADMIN

* Existing admin tetap berjalan
* Testimonial management
* News management
* News category management
* Settings untuk contact/map/branding jika belum tersedia
* Navigation admin diperbarui
* Semua fitur baru mengikuti style dan arsitektur admin existing

### DATABASE

* Reuse existing tables whenever possible
* Add only missing tables/columns
* Proper relationships
* Proper indexes
* Slug support
* Publication status
* Dates for sorting
* Migration
* Update `schema.sql` bila memang digunakan project

**Sekali lagi: baca dan pahami codebase terlebih dahulu sebelum melakukan perubahan. Jangan mengarang struktur database, route, model, atau API yang sebenarnya sudah tersedia.**
