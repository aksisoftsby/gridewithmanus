# MASTER TASK — BUILD FLUTTER MERCHANT APP FROM ZERO

Saya memiliki project **Laravel backend yang sudah terinstall dan berjalan**.

Di dalam project Laravel sudah terdapat:

```text
app-customer/
app-driver/
```

Sekarang saya ingin membuat aplikasi Flutter ketiga:

```text
app-merchant/
```

Aplikasi ini adalah **Flutter Merchant Mobile Application** untuk digunakan oleh merchant/toko/business operator.

Flutter CLI sudah terinstall di Windows dan dapat digunakan melalui terminal.

---

# STRUKTUR PROJECT WAJIB

Target struktur:

```text
backend/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
│
├── app-info.md
├── schema.sql
├── composer.json
│
├── app-customer/
│   ├── android/
│   ├── ios/
│   ├── lib/
│   ├── test/
│   └── pubspec.yaml
│
├── app-driver/
│   ├── android/
│   ├── ios/
│   ├── lib/
│   ├── test/
│   └── pubspec.yaml
│
└── app-merchant/
    ├── android/
    ├── ios/
    ├── lib/
    ├── test/
    ├── assets/
    └── pubspec.yaml
```

**SEMUA kode Flutter Merchant harus berada di dalam `app-merchant/`.**

Jangan membuat Flutter files di root Laravel.

Jangan merusak:

```text
app-customer/
app-driver/
```

---

# PERAN APLIKASI

`app-merchant` adalah aplikasi untuk:

* merchant
* toko
* business owner
* store operator

atau role lain yang secara eksplisit didefinisikan oleh `app-info.md`.

Konsep UX boleh menggunakan aplikasi merchant modern sebagai referensi.

Namun jangan menyalin aplikasi lain.

Jangan menyalin:

* logo
* warna
* branding
* icon
* asset
* UI secara identik
* business logic
* nama fitur

Fitur aktual HARUS berasal dari:

```text
app-info.md
schema.sql
Laravel API
```

---

# SUMBER KEBENARAN

Sebelum coding, baca:

```text
app-info.md
schema.sql
```

secara penuh.

Kemudian audit Laravel:

```text
routes/
app/Models/
app/Http/Controllers/
app/Http/Requests/
app/Http/Resources/
app/Services/
resources/views/
public/
```

Periksa juga:

```text
app-customer/
app-driver/
```

untuk memahami:

* API contract
* authentication
* branding
* naming convention
* model/response
* design language
* reusable patterns

Namun:

**Jangan copy-paste seluruh architecture customer/driver.**

Merchant memiliki kebutuhan dan flow sendiri.

---

# PRIORITAS INFORMASI

Gunakan urutan:

```text
1. app-info.md
2. schema.sql
3. Laravel API implementation
4. Laravel API documentation
5. Existing Laravel layout/branding
6. Existing app-customer
7. Existing app-driver
8. Flutter best practices
```

---

# JANGAN MENGARANG FITUR MERCHANT

Jangan otomatis menganggap merchant memiliki:

```text
Products
Orders
Inventory
Sales
Wallet
Reports
Promotions
Opening Hours
```

kecuali memang terdapat pada specification/backend.

Jika fitur tersebut memang ada, implementasikan secara lengkap.

---

# PHASE 0 — FULL SYSTEM AUDIT

Sebelum coding:

Baca:

```text
app-info.md
schema.sql
```

secara penuh.

Kemudian audit:

```text
Laravel API
Customer App
Driver App
Laravel UI
Database
Merchant Role
```

Tujuan phase ini adalah memahami hubungan:

```text
Customer
   ↓
Order
   ↓
Merchant
   ↓
Driver
   ↓
Order Status
```

**Hanya jika relationship tersebut memang ada di schema dan specification.**

---

# PHASE 0.1 — IDENTIFY MERCHANT ROLE

Cari di:

```text
schema.sql
app-info.md
Laravel Models
Laravel Middleware
Laravel Policies
Laravel Controllers
```

informasi tentang:

```text
merchant
store
shop
seller
business
vendor
owner
admin
```

Tentukan:

* merchant login
* merchant ID
* store ID
* ownership
* role
* permission
* verification
* status
* active/inactive
* multiple stores jika ada

Jangan membuat role baru jika backend sudah menentukan role.

---

# PHASE 0.2 — MERCHANT FLOW

Buat internal flow sebelum coding.

Flow dasar:

```text
App Start
    ↓
Splash
    ↓
Check Merchant Authentication
    ↓
Authenticated?
 ├── NO  → Login
 └── YES → Merchant Dashboard
```

Jika merchant membutuhkan verification:

```text
Login
 ↓
Verification Status
 ↓
Pending / Rejected / Approved
```

Ikuti backend.

---

# PHASE 0.3 — MERCHANT BUSINESS FLOW

Jika merchant menerima order:

```text
Merchant Dashboard
       ↓
New Order
       ↓
Order Detail
       ↓
Accept / Reject
       ↓
Process Order
       ↓
Ready / Completed
```

Status aktual HARUS mengikuti:

```text
app-info.md
schema.sql
Laravel API
```

Jangan mengarang status.

---

# PHASE 0.4 — DATA RELATIONSHIP

Pahami relationship antara:

```text
Merchant
Store
Product
Category
Customer
Order
Order Item
Payment
Driver
Notification
```

Tetapi hanya entity yang memang ada.

Buat mapping internal:

```text
ENTITY
 ↓
TABLE
 ↓
MODEL
 ↓
API
 ↓
FLUTTER MODEL
 ↓
SCREEN
```

---

# PHASE 1 — AUDIT LARAVEL API

Jalankan:

```bash
php artisan route:list
```

Identifikasi seluruh endpoint merchant.

Buat mapping:

```text
SCREEN / ACTION
      ↓
API ENDPOINT
      ↓
HTTP METHOD
      ↓
AUTH
      ↓
REQUEST
      ↓
RESPONSE
```

Contoh ilustrasi:

```text
Merchant Login
→ POST /api/v1/merchant/login

Dashboard
→ GET /api/v1/merchant/dashboard

Products
→ GET /api/v1/merchant/products

Create Product
→ POST /api/v1/merchant/products

Orders
→ GET /api/v1/merchant/orders

Accept Order
→ POST /api/v1/merchant/orders/{id}/accept
```

**Contoh di atas hanya ilustrasi.**

Gunakan endpoint aktual dari Laravel.

---

# API CONTRACT

Flutter Merchant HARUS mengikuti API Laravel.

Jangan mengarang:

* endpoint
* method
* request
* response
* field
* status
* authentication
* pagination
* error

Jika backend memiliki:

```text
POST /api/v1/merchant/login
```

gunakan endpoint tersebut.

Jangan membuat endpoint alternatif.

---

# PHASE 2 — CREATE FLUTTER PROJECT

Pastikan berada di root Laravel.

Jika `app-merchant/` belum ada:

```bash
flutter create app-merchant
```

Kemudian:

```bash
cd app-merchant
flutter pub get
flutter analyze
flutter test
```

Pastikan baseline bersih.

Jika folder sudah berisi Flutter project:

**Jangan menjalankan `flutter create` secara sembarangan.**

Audit dahulu.

---

# PHASE 3 — MERCHANT ARCHITECTURE

Gunakan architecture sederhana dan scalable:

```text
app-merchant/
│
├── android/
├── ios/
├── test/
├── assets/
│
├── lib/
│   ├── main.dart
│   │
│   ├── app/
│   │   ├── app.dart
│   │   ├── router/
│   │   ├── theme/
│   │   └── config/
│   │
│   ├── core/
│   │   ├── constants/
│   │   ├── errors/
│   │   ├── network/
│   │   ├── storage/
│   │   ├── permissions/
│   │   ├── utils/
│   │   └── widgets/
│   │
│   ├── features/
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── orders/
│   │   ├── products/
│   │   ├── categories/
│   │   ├── inventory/
│   │   ├── sales/
│   │   ├── notifications/
│   │   ├── profile/
│   │   └── ...
│   │
│   └── shared/
│       ├── models/
│       ├── widgets/
│       └── services/
│
└── pubspec.yaml
```

**Jangan membuat feature yang tidak diperlukan.**

Folder hanya dibuat ketika dibutuhkan.

---

# PHASE 4 — BRANDING AUDIT

Merchant App harus menggunakan branding aplikasi Laravel yang sama.

Baca:

```text
resources/views/
resources/views/layouts/
resources/views/components/
resources/views/partials/
resources/css/
public/
```

Cari:

* application name
* primary color
* secondary color
* accent color
* background
* text
* font
* button style
* card style
* border radius
* logo
* favicon

---

# WARNA

Cari warna aktual dari:

```text
CSS
SCSS
Tailwind
Bootstrap
CSS variables
Blade
theme configuration
```

Jangan menebak.

Buat:

```text
lib/app/theme/app_colors.dart
lib/app/theme/app_theme.dart
lib/app/theme/app_typography.dart
```

Semua UI Merchant menggunakan theme tersebut.

Jangan hard-code warna random.

---

# FONT

Cari:

```text
font-family
@font-face
Google Fonts
Tailwind configuration
CSS imports
```

Gunakan font Laravel.

Jika custom font tersedia dan dapat digunakan:

```text
public/fonts/
resources/fonts/
```

salin ke:

```text
app-merchant/assets/fonts/
```

dan daftarkan di `pubspec.yaml`.

---

# LOGO

Cari logo Laravel:

```text
public/
public/images/
public/assets/
resources/
storage/
```

Cari:

```text
logo.png
logo.svg
logo.webp
brand.svg
favicon
```

Periksa Blade:

```blade
<img src="{{ asset('...') }}">
```

atau:

```blade
<x-logo />
```

atau SVG langsung.

---

# JIKA TIDAK BISA MEMBACA GAMBAR

Jangan membuat logo baru berdasarkan tebakan.

Gunakan:

```text
Blade / HTML
 ↓
<img>
<svg>
<x-logo>
alt
title
aria-label
src
class
id
 ↓
Cari asset
 ↓
Baca SVG/XML jika tersedia
 ↓
Gunakan logo asli
```

---

# ANDROID LOGO

Buat launcher icon berdasarkan logo Laravel.

Periksa struktur Android terlebih dahulu.

Jika menggunakan adaptive icon:

```xml
<adaptive-icon>
```

gunakan struktur Android yang valid.

Buat resource yang benar:

```text
mipmap-anydpi-v26/
mipmap-hdpi/
mipmap-mdpi/
mipmap-xhdpi/
mipmap-xxhdpi/
mipmap-xxxhdpi/
```

jika memang diperlukan.

Jika membutuhkan:

```text
ic_launcher.xml
ic_launcher_round.xml
```

buat dengan benar.

Jika diminta membuat:

```text
logo.xml
```

buat file XML yang valid dan benar-benar digunakan oleh Android resource system.

Jangan membuat XML yang tidak direferensikan.

---

# SPLASH SCREEN

Gunakan:

* Laravel logo
* Laravel primary color
* Laravel branding

Jangan menggunakan default Flutter branding.

---

# PHASE 5 — MERCHANT THEME

Gunakan Material 3 jika tidak ada design system lain.

Gunakan:

```dart
Theme.of(context)
```

atau:

```dart
AppColors.primary
```

Jangan menggunakan warna random per screen.

---

# PHASE 6 — ROUTING

Buat routing sesuai feature aktual.

Contoh:

```text
/splash
/login
/dashboard
/orders
/orders/:id
/products
/products/create
/products/:id/edit
/inventory
/sales
/notifications
/profile
/settings
```

Contoh tersebut hanya referensi.

Gunakan route aktual.

---

# PHASE 7 — AUTHENTICATION

Flow:

```text
App Start
 ↓
Splash
 ↓
Secure Token
 ↓
Check Session
 ↓
Authenticated?
 ├── NO  → Login
 └── YES → Dashboard
```

Login:

```text
Login Form
 ↓
Validation
 ↓
Laravel API
 ↓
Token
 ↓
Secure Storage
 ↓
Merchant Profile
 ↓
Dashboard
```

Logout:

```text
Logout
 ↓
Clear token
 ↓
Clear merchant session
 ↓
Login
```

---

# AUTHORIZATION

Merchant authorization HARUS berasal dari backend.

Flutter tidak boleh menganggap:

```text
merchant_id
store_id
role
```

sebagai bukti ownership.

Backend harus memastikan merchant hanya dapat mengakses:

```text
store miliknya
order miliknya
product miliknya
sales miliknya
```

---

# PHASE 8 — API CLIENT

Buat:

```text
lib/core/network/
├── api_client.dart
├── api_exception.dart
├── api_response.dart
└── ...
```

Semua request menggunakan API client.

Base URL:

```text
API_BASE_URL
```

satu tempat.

Authenticated request otomatis:

```text
Authorization: Bearer TOKEN
```

---

# 401 HANDLING

Jika:

```text
401
```

dan backend tidak memiliki refresh token:

```text
401
 ↓
Clear session
 ↓
Login
```

Jika backend mendukung refresh token:

```text
401
 ↓
Refresh
 ↓
Retry
```

Ikuti backend.

---

# PHASE 9 — STATE MANAGEMENT

Gunakan SATU state management solution.

Pilih berdasarkan project.

Jangan:

```text
Provider + Riverpod + Bloc
```

secara bersamaan.

---

# PHASE 10 — DATA FLOW

Gunakan:

```text
UI
 ↓
State / Controller
 ↓
Repository
 ↓
API Client
 ↓
Laravel API
 ↓
Database
```

Jangan HTTP request langsung dari Widget untuk feature utama.

---

# PHASE 11 — MERCHANT DASHBOARD

Dashboard harus menjadi pusat aktivitas merchant.

Jika sesuai specification, pola:

```text
┌─────────────────────────────┐
│ Store / Merchant            │
│ Notification                │
├─────────────────────────────┤
│ Store Status                │
│ OPEN / CLOSED               │
├─────────────────────────────┤
│ Today's Summary             │
│                             │
│ Orders | Sales | ...        │
├─────────────────────────────┤
│ New Orders                  │
├─────────────────────────────┤
│ Active Orders               │
├─────────────────────────────┤
│ Quick Actions               │
├─────────────────────────────┤
│ Home | Orders | Profile     │
└─────────────────────────────┘
```

Ini hanya referensi.

Gunakan data aktual dari backend.

---

# DASHBOARD DATA

Jika backend menyediakan dashboard summary:

Gunakan API backend.

Jangan menghitung sendiri jika server sudah menyediakan:

* total sales
* order count
* revenue
* pending order
* completed order
* etc.

Backend menjadi source of truth.

---

# PHASE 12 — STORE STATUS

Jika merchant memiliki status buka/tutup:

Flow:

```text
CLOSED
 ↓
Tap Open
 ↓
API
 ↓
Success
 ↓
OPEN
```

Jika API gagal:

```text
Remain CLOSED
```

Jangan mengubah UI sebelum server sukses.

---

# PHASE 13 — ORDERS

Jika merchant menerima order:

```text
Orders
 ↓
Pending
 ↓
Order Detail
 ↓
Accept / Reject
 ↓
Process
 ↓
Ready / Complete
```

Status harus mengikuti backend.

---

# ORDER STATUS

Jangan mengarang status.

Jika backend memiliki:

```text
pending
accepted
processing
ready
completed
cancelled
```

gunakan status tersebut.

Jika berbeda, gunakan status backend.

---

# ORDER DETAIL

Tampilkan hanya data yang tersedia:

```text
Order Number
Customer
Items
Quantity
Price
Subtotal
Delivery Fee
Discount
Total
Payment Status
Order Status
Created At
```

Hanya jika API menyediakan field tersebut.

---

# ORDER ACTION

Action harus berdasarkan status.

Contoh:

```text
pending
 ↓
Accept
```

Kemudian:

```text
accepted
 ↓
Process
```

Kemudian:

```text
processing
 ↓
Ready
```

Jangan menampilkan action yang tidak valid.

---

# CONCURRENCY

Order dapat berubah status karena:

* customer
* driver
* merchant
* admin

Karena itu backend adalah source of truth.

Jika API mengembalikan:

```text
409 Conflict
```

atau equivalent:

```text
Order status sudah berubah.
```

Kemudian refresh data.

Jangan mengasumsikan status lokal selalu benar.

---

# PHASE 14 — PRODUCTS

Jika merchant memiliki product/menu management:

```text
Products
 ↓
List
 ↓
Create
 ↓
Edit
 ↓
Delete
```

Flow create:

```text
Form
 ↓
Validate
 ↓
Upload image jika perlu
 ↓
API
 ↓
Success
 ↓
Refresh list
```

---

# PRODUCT FORM

Validasi sesuai backend:

```text
Name
Description
Price
Category
Stock
Status
Image
```

Hanya field yang benar-benar tersedia.

---

# PRODUCT STATUS

Jika backend mendukung:

```text
Active
Inactive
Available
Unavailable
```

gunakan status backend.

Jangan membuat status baru.

---

# PHASE 15 — CATEGORY

Jika tersedia:

```text
Category
 ↓
List
 ↓
Create
 ↓
Edit
 ↓
Delete
```

Pastikan category ownership diperiksa backend.

---

# PHASE 16 — INVENTORY

Jika schema/API menyediakan inventory:

```text
Inventory
 ↓
Product
 ↓
Stock
 ↓
Update
 ↓
API
```

Jangan menghitung stock sendiri jika backend sudah menentukan.

---

# STOCK CONSISTENCY

Jika order masuk dan stock berubah di backend:

Flutter harus refresh dari API.

Jangan mengandalkan:

```text
localStock = localStock - quantity
```

sebagai source of truth.

---

# PHASE 17 — SALES / TRANSACTIONS

Jika tersedia:

```text
Sales
 ↓
Summary
 ↓
Period
 ↓
Transactions
 ↓
Detail
```

Gunakan data server.

---

# PHASE 18 — REPORTS

Jika backend memiliki report:

```text
Report
 ↓
Date Range
 ↓
Filter
 ↓
API
 ↓
Result
```

Jangan membuat kalkulasi berbeda dari backend.

---

# PHASE 19 — NOTIFICATIONS

Jika tersedia:

```text
Notifications
 ↓
List
 ↓
Unread
 ↓
Mark Read
```

Unread count mengikuti backend.

---

# PHASE 20 — PROFILE / STORE

Jika merchant memiliki profile/store settings:

```text
Profile
 ↓
Store Information
 ↓
Edit
 ↓
Validation
 ↓
API
 ↓
Success
```

Kemungkinan data:

```text
Store Name
Description
Address
Phone
Logo
Operating Hours
Status
```

Hanya jika tersedia.

---

# PHASE 21 — OPERATING HOURS

Jika backend mendukung:

```text
Operating Hours
 ↓
Select Day
 ↓
Open Time
 ↓
Close Time
 ↓
Save
```

Pastikan timezone mengikuti backend/application.

Jangan membuat timezone sendiri.

---

# PHASE 22 — FILE / IMAGE UPLOAD

Jika product/store membutuhkan image:

```text
Select
 ↓
Preview
 ↓
Validate
 ↓
Upload
 ↓
Success
 ↓
Refresh
```

Validasi:

* MIME
* extension
* size
* upload error

---

# PHASE 23 — REAL-TIME / NEW ORDER

Jika merchant membutuhkan notification real-time:

**Gunakan mechanism yang benar-benar disediakan backend.**

Kemungkinan:

```text
Polling
WebSocket
SSE
Firebase Cloud Messaging
```

Jangan mengarang implementation.

Jika backend belum mendukung real-time:

Jangan membuat fake websocket.

---

# NEW ORDER UX

Jika backend mendukung notification:

```text
New Order
 ↓
Notification
 ↓
Open Order Detail
 ↓
Accept / Reject
```

Jika app menggunakan polling:

Gunakan interval yang wajar.

Jangan request API setiap beberapa milidetik.

---

# PHASE 24 — ERROR HANDLING

Semua API screen wajib memiliki:

```text
Initial
Loading
Success
Empty
Error
Retry
```

Network error:

```text
Tidak dapat terhubung ke server.
Periksa koneksi internet dan coba lagi.
```

Jangan tampilkan raw exception.

---

# HTTP ERROR

Tangani:

```text
400
401
403
404
409
422
429
500
```

Contoh:

```text
401 → Login
403 → Tidak memiliki akses
404 → Data tidak ditemukan
409 → Data/status sudah berubah
422 → Validation error
429 → Terlalu banyak request
500 → Server error
```

Gunakan pesan dari backend jika tersedia.

---

# PHASE 25 — FORM

Semua form:

```text
Input
 ↓
Validation
 ↓
Submit
 ↓
Disable
 ↓
Loading
 ↓
API
 ↓
Success/Error
```

Cegah double submit.

---

# PHASE 26 — PAGINATION

Untuk list besar:

```text
Pagination
Pull to Refresh
Load More
Empty
Error
Retry
```

Ikuti pagination Laravel.

---

# PHASE 27 — RESPONSIVE UI

Support:

```text
small phone
normal phone
large phone
tablet
```

Gunakan:

```text
SafeArea
Expanded
Flexible
LayoutBuilder
MediaQuery
```

jika diperlukan.

Hindari fixed width berlebihan.

---

# PHASE 28 — DEPENDENCY RULE

Sebelum install package:

1. cek Flutter built-in
2. cek package existing
3. cek compatibility
4. cek maintenance
5. gunakan hanya jika diperlukan

Setelah perubahan:

```bash
flutter pub get
flutter analyze
flutter test
```

---

# PHASE 29 — ANDROID STABILITY

Jangan mengubah random:

```text
Gradle
AGP
Kotlin
Java
compileSdk
targetSdk
minSdk
```

Jika ada error:

```text
Flutter
 ↓
Dart
 ↓
Java
 ↓
Gradle
 ↓
AGP
 ↓
Kotlin
 ↓
Android SDK
```

Cari root cause.

Jangan upgrade semua dependency sekaligus.

---

# PHASE 30 — TEST SETIAP FEATURE

Setelah setiap feature:

```bash
flutter analyze
flutter test
```

Jika error:

```text
STOP
 ↓
Read error
 ↓
Root cause
 ↓
Smallest fix
 ↓
Analyze
 ↓
Test
 ↓
Continue
```

Jangan menumpuk error.

---

# PHASE 31 — SECURITY

Jangan menyimpan:

```text
Database credentials
Laravel APP_KEY
Admin credentials
Private API secrets
```

di Flutter.

Backend harus menentukan:

```text
merchant ownership
store ownership
order ownership
product ownership
permissions
prices
stock
payment
```

Flutter hanya client.

---

# PHASE 32 — MERCHANT OWNERSHIP

Merchant tidak boleh dapat:

```text
melihat toko merchant lain
mengedit produk merchant lain
mengakses order merchant lain
mengubah stock merchant lain
```

hanya dengan mengganti ID.

Backend wajib melakukan authorization.

Flutter hanya mengikuti response backend.

---

# PHASE 33 — CUSTOMER / DRIVER RELATIONSHIP

Jika schema/application memiliki relationship:

```text
Customer
 ↓
Order
 ↓
Merchant
 ↓
Driver
```

pastikan Merchant App menampilkan status yang sesuai.

Contoh:

```text
Order
 ↓
Merchant Processing
 ↓
Driver Assigned
 ↓
Delivery
 ↓
Completed
```

Tetapi hanya jika flow tersebut memang ada.

---

# PHASE 34 — API AUDIT

Setelah implementation selesai, buat matrix:

```text
SCREEN / ACTION
API
METHOD
AUTH
REQUEST
RESPONSE
STATUS
```

Pastikan setiap API benar-benar tersedia di Laravel.

---

# PHASE 35 — FEATURE AUDIT

Bandingkan:

```text
app-info.md
      ↓
Merchant Feature
      ↓
Flutter Screen
      ↓
State
      ↓
Repository
      ↓
Laravel API
      ↓
Database
```

Tidak boleh ada feature yang hanya berupa UI dummy.

---

# PHASE 36 — BRANDING AUDIT

Pastikan:

```text
✓ Application name
✓ Laravel primary color
✓ Laravel secondary color
✓ Laravel font
✓ Laravel logo
✓ Android launcher icon
✓ logo.xml / adaptive icon
✓ Splash screen
✓ No Flutter branding
✓ No random colors
✓ No random fonts
```

---

# PHASE 37 — FINAL TEST

Masuk:

```bash
cd app-merchant
```

Jalankan:

```bash
flutter clean
flutter pub get
flutter analyze
flutter test
flutter build apk --debug
```

Semua harus berhasil.

Jika release diperlukan:

```bash
flutter build apk --release
```

Jangan membuat signing key tanpa instruksi.

---

# FINAL PROJECT STRUCTURE

Target:

```text
backend/
│
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
│
├── app-info.md
├── schema.sql
├── composer.json
│
├── app-customer/
│   ├── android/
│   ├── ios/
│   ├── lib/
│   ├── test/
│   └── pubspec.yaml
│
├── app-driver/
│   ├── android/
│   ├── ios/
│   ├── lib/
│   ├── test/
│   └── pubspec.yaml
│
└── app-merchant/
    │
    ├── android/
    ├── ios/
    │
    ├── lib/
    │   ├── app/
    │   │   ├── router/
    │   │   ├── theme/
    │   │   └── config/
    │   │
    │   ├── core/
    │   │   ├── constants/
    │   │   ├── errors/
    │   │   ├── network/
    │   │   ├── storage/
    │   │   ├── permissions/
    │   │   ├── utils/
    │   │   └── widgets/
    │   │
    │   ├── features/
    │   │   ├── auth/
    │   │   ├── dashboard/
    │   │   ├── orders/
    │   │   ├── products/
    │   │   ├── categories/
    │   │   ├── inventory/
    │   │   ├── sales/
    │   │   ├── notifications/
    │   │   ├── profile/
    │   │   └── ...
    │   │
    │   └── shared/
    │       ├── models/
    │       ├── widgets/
    │       └── services/
    │
    ├── assets/
    │   ├── images/
    │   ├── icons/
    │   └── fonts/
    │
    ├── test/
    └── pubspec.yaml
```

---

# DEFINISI SELESAI

`app-merchant` hanya boleh dianggap selesai jika:

```text
✓ Laravel tetap berjalan
✓ app-customer tidak rusak
✓ app-driver tidak rusak
✓ Flutter project valid
✓ Flutter berada di app-merchant/
✓ flutter pub get berhasil
✓ flutter analyze tidak memiliki error
✓ flutter test berhasil
✓ Android debug build berhasil
✓ Merchant authentication berjalan
✓ Secure token storage berjalan
✓ Merchant authorization mengikuti backend
✓ Dashboard berjalan
✓ Store status berjalan jika tersedia
✓ Orders berjalan jika tersedia
✓ Order action berjalan jika tersedia
✓ Products berjalan jika tersedia
✓ Categories berjalan jika tersedia
✓ Inventory berjalan jika tersedia
✓ Sales berjalan jika tersedia
✓ Reports berjalan jika tersedia
✓ Notifications berjalan jika tersedia
✓ Profile/store settings berjalan jika tersedia
✓ Loading state tersedia
✓ Empty state tersedia
✓ Error state tersedia
✓ Retry tersedia
✓ Form validation tersedia
✓ Pagination tersedia jika diperlukan
✓ Image upload berjalan jika diperlukan
✓ Tidak ada dummy API
✓ Tidak ada fake response
✓ Tidak ada hardcoded credential
✓ Tidak ada endpoint yang dikarang
✓ Tidak ada database connection langsung
✓ Branding sesuai Laravel
✓ Logo sesuai Laravel
✓ Android icon sesuai Laravel
✓ Splash sesuai Laravel
```

---

# ANTI-ERROR RULE

Jangan:

```text
buat semua file sekaligus
```

Jangan:

```text
install banyak dependency sekaligus
```

Jangan:

```text
ubah Gradle secara random
```

Jangan:

```text
mengarang API
```

Jangan:

```text
mengarang database field
```

Jangan:

```text
membuat dummy order/product/sales
```

Jangan:

```text
menyelesaikan compile error dengan menghapus functionality
```

Jangan:

```text
menggunakan ! secara sembarangan
```

Jangan:

```text
mengubah app-customer atau app-driver
```

tanpa alasan yang jelas.

Gunakan:

```text
Understand
 ↓
Plan
 ↓
Implement small part
 ↓
Analyze
 ↓
Test
 ↓
Fix
 ↓
Verify
 ↓
Continue
```

---

# DEVELOPMENT FLOW FINAL

WAJIB mengikuti:

```text
READ app-info.md
        ↓
READ schema.sql
        ↓
AUDIT DATABASE
        ↓
AUDIT LARAVEL API
        ↓
AUDIT MERCHANT ROLE
        ↓
AUDIT CUSTOMER / DRIVER RELATIONSHIP
        ↓
AUDIT LARAVEL LAYOUT
        ↓
AUDIT COLORS
        ↓
AUDIT FONT
        ↓
AUDIT LOGO
        ↓
AUDIT BLADE LOGO TAG
        ↓
AUDIT app-customer
        ↓
AUDIT app-driver
        ↓
CHECK FLUTTER / DART VERSION
        ↓
CREATE app-merchant/
        ↓
CREATE FLUTTER PROJECT
        ↓
BASELINE flutter analyze
        ↓
BASELINE flutter test
        ↓
CREATE MERCHANT BRAND THEME
        ↓
CREATE LOGO / APP ICON
        ↓
CREATE SPLASH
        ↓
ROUTING
        ↓
API CLIENT
        ↓
AUTHENTICATION
        ↓
MERCHANT SESSION
        ↓
DASHBOARD
        ↓
STORE STATUS
        ↓
ORDERS
        ↓
ANALYZE + TEST
        ↓
PRODUCTS
        ↓
ANALYZE + TEST
        ↓
INVENTORY
        ↓
ANALYZE + TEST
        ↓
SALES / REPORTS
        ↓
ANALYZE + TEST
        ↓
PROFILE
        ↓
NOTIFICATION
        ↓
ANALYZE + TEST
        ↓
FINAL API AUDIT
        ↓
FINAL FEATURE AUDIT
        ↓
FINAL BRANDING AUDIT
        ↓
FINAL SECURITY AUDIT
        ↓
flutter clean
        ↓
flutter pub get
        ↓
flutter analyze
        ↓
flutter test
        ↓
flutter build apk --debug
```

# FINAL INSTRUCTION

**Mulai HANYA dari PHASE 0.**

Jangan langsung membuat seluruh aplikasi.

Pertama:

1. baca `app-info.md`
2. baca `schema.sql`
3. audit Laravel API
4. audit merchant role
5. audit database relationship
6. audit Laravel layout
7. audit warna
8. audit font
9. audit logo
10. audit Blade/HTML logo
11. audit `app-customer`
12. audit `app-driver`
13. periksa Flutter/Dart version

Setelah audit selesai, buat:

```text
app-merchant/
```

dan Flutter project di dalamnya.

**Jangan merusak Laravel, `app-customer`, atau `app-driver`.**

Kerjakan secara incremental.

Setelah setiap phase jalankan:

```bash
flutter analyze
flutter test
```

**Jangan lanjut ke phase berikutnya jika phase sebelumnya masih memiliki error.**
