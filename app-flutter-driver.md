# MASTER TASK — BUILD FLUTTER DRIVER APP FROM ZERO

Saya memiliki project **Laravel backend yang sudah terinstall dan berjalan**.

Flutter CLI sudah terinstall di Windows dan dapat digunakan melalui terminal.

Saya ingin membuat **Flutter Driver Mobile Application** dari awal.

Aplikasi driver harus dibuat sebagai project Flutter terpisah di dalam folder:

```text
app-driver/
```

Folder tersebut berada di dalam root Laravel backend.

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
└── app-driver/
    ├── android/
    ├── ios/
    ├── lib/
    ├── test/
    ├── pubspec.yaml
    └── ...
```

**SEMUA kode Flutter Driver harus berada di dalam `app-driver/`.**

Jangan membuat Flutter files di root Laravel.

Jangan merusak:

```text
app-customer/
```

Jangan mengubah Flutter Customer App kecuali memang ada dependency/API contract yang secara eksplisit membutuhkan perubahan.

---

# PERAN APLIKASI

`app-driver` adalah aplikasi untuk **driver/operator/provider/service personnel**, bukan customer.

Konsep UX boleh menggunakan aplikasi driver modern seperti platform ride-hailing/delivery sebagai referensi.

Tetapi:

**JANGAN menyalin Grab, Gojek, atau aplikasi lain.**

Jangan menyalin:

* logo
* warna
* branding
* icon
* asset
* layout secara identik
* nama fitur
* business logic

Fitur aktual harus berasal dari:

```text
app-info.md
schema.sql
Laravel API
```

---

# SUMBER INFORMASI UTAMA

Sebelum coding, baca:

```text
app-info.md
schema.sql
```

secara penuh.

Kemudian audit Laravel:

```text
routes/
app/Http/Controllers/
app/Http/Requests/
app/Http/Resources/
app/Models/
app/Services/
resources/views/
public/
```

Jika API documentation sudah tersedia, baca juga API documentation.

Prioritas:

```text
1. app-info.md
2. schema.sql
3. Laravel API implementation
4. Laravel API documentation
5. Existing Laravel UI / branding
6. Flutter best practices
```

---

# JANGAN MENGARANG FITUR DRIVER

Jangan otomatis mengasumsikan aplikasi memiliki:

```text
Ride
Food Delivery
Courier
Wallet
Maps
Navigation
Earnings
Rating
```

kecuali memang ada di specification/backend.

Namun jika `app-info.md` dan `schema.sql` memang menunjukkan adanya fitur tersebut, implementasikan secara lengkap.

---

# PHASE 0 — AUDIT SPECIFICATION

Sebelum membuat Flutter project:

Baca seluruh:

```text
app-info.md
schema.sql
```

Kemudian identifikasi:

## Driver Role

Cari:

```text
driver
drivers
provider
courier
operator
merchant
worker
```

dan role/permission yang berhubungan.

Identifikasi:

* bagaimana driver login
* bagaimana driver didaftarkan
* bagaimana driver diverifikasi
* status driver
* status online/offline
* pekerjaan/order yang dapat diterima
* pekerjaan aktif
* history
* earnings
* notification
* profile
* rating jika ada
* lokasi jika ada
* document verification jika ada

Jangan mengarang jika tidak ada.

---

# PHASE 0.1 — DRIVER USER FLOW

Buat internal flow sebelum coding.

Flow dasar:

```text
App Start
    ↓
Splash
    ↓
Check Driver Authentication
    ↓
┌────────────────────┐
│ Authenticated?     │
└─────────┬──────────┘
      NO  │  YES
          │
          ↓
       Login
          │
          ↓
     Driver Home
```

Jika driver belum diverifikasi:

```text
Login
 ↓
Driver Verification Status
 ↓
Pending / Rejected / Approved
```

Sesuaikan dengan backend.

---

# PHASE 0.2 — DRIVER WORK FLOW

Jika driver menerima pekerjaan/order:

```text
Driver Home
      ↓
Online
      ↓
Available Order
      ↓
Order Detail
      ↓
Accept / Reject
      ↓
Active Job
      ↓
Update Status
      ↓
Complete
      ↓
Result
```

Jika ada status tertentu di `app-info.md`, gunakan status tersebut.

Jangan membuat status baru hanya berdasarkan asumsi.

---

# PHASE 0.3 — DRIVER STATUS FLOW

Jika backend memiliki driver availability:

```text
OFFLINE
   ↓
ONLINE
   ↓
AVAILABLE
   ↓
BUSY
   ↓
AVAILABLE
   ↓
OFFLINE
```

Status aktual harus mengikuti backend.

Jangan hanya menyimpan status online/offline secara lokal jika backend membutuhkan status tersebut.

---

# PHASE 1 — AUDIT LARAVEL API

Periksa:

```bash
php artisan route:list
```

Identifikasi endpoint khusus driver.

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

Contoh:

```text
Driver Login
→ POST /api/v1/driver/login

Driver Profile
→ GET /api/v1/driver/profile

Driver Status
→ POST /api/v1/driver/status

Available Orders
→ GET /api/v1/driver/orders/available

Accept Order
→ POST /api/v1/driver/orders/{id}/accept

Active Order
→ GET /api/v1/driver/orders/active
```

**Contoh di atas hanya ilustrasi.**

Gunakan endpoint aktual dari Laravel.

---

# API CONTRACT

Flutter Driver harus mengikuti Laravel API secara persis.

Jangan mengarang:

* endpoint
* HTTP method
* request field
* response field
* status
* authentication
* pagination
* error format

Jika backend mengatakan:

```text
POST /api/v1/driver/login
```

gunakan itu.

Jangan mengganti menjadi:

```text
POST /api/driver/auth/login
```

karena menurut AI lebih bagus.

---

# PHASE 2 — CREATE FLUTTER PROJECT

Pastikan berada di root Laravel.

Buat:

```text
app-driver/
```

Jika belum ada:

```bash
flutter create app-driver
```

Kemudian:

```bash
cd app-driver
flutter pub get
flutter analyze
flutter test
```

Baseline Flutter harus bersih.

Jika `app-driver` sudah berisi Flutter project:

**Jangan menjalankan `flutter create` secara sembarangan yang dapat overwrite file.**

Audit terlebih dahulu.

---

# PHASE 3 — DRIVER ARCHITECTURE

Gunakan architecture sederhana dan scalable:

```text
app-driver/
│
├── android/
├── ios/
├── test/
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
│   │   ├── location/
│   │   ├── utils/
│   │   └── widgets/
│   │
│   ├── features/
│   │   ├── auth/
│   │   ├── home/
│   │   ├── orders/
│   │   ├── active_job/
│   │   ├── earnings/
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

Folder hanya dibuat jika memang diperlukan.

Jangan membuat folder kosong hanya untuk terlihat kompleks.

---

# PHASE 4 — DRIVER BRANDING

Driver App harus memiliki branding yang konsisten dengan Laravel.

**Baca layout Laravel terlebih dahulu.**

Periksa:

```text
resources/views/
resources/views/layouts/
resources/views/components/
resources/views/partials/
public/
resources/css/
```

Cari:

* primary color
* secondary color
* accent
* background
* text
* button
* card
* font
* logo
* favicon
* application name

---

# BRAND COLOR

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

Contoh:

```css
--primary: #123456;
```

Gunakan warna tersebut di Flutter.

Buat:

```text
lib/app/theme/app_colors.dart
lib/app/theme/app_theme.dart
lib/app/theme/app_typography.dart
```

Jangan menggunakan warna random.

Jangan menggunakan default Flutter blue sebagai branding.

---

# FONT

Cari font Laravel:

```text
font-family
@font-face
Google Fonts
Tailwind config
CSS import
```

Gunakan font yang sama jika tersedia dan dapat digunakan.

Jika custom font tersedia:

```text
public/fonts/
resources/fonts/
```

analisis dan masukkan ke:

```text
app-driver/assets/fonts/
```

jika diperlukan.

Daftarkan di `pubspec.yaml`.

---

# DRIVER LOGO

Cari logo aplikasi Laravel:

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

Baca Blade:

```blade
<img src="{{ asset('...') }}">
```

atau:

```blade
<x-logo />
```

atau SVG langsung.

---

# JIKA AI TIDAK BISA MEMBACA GAMBAR LOGO

Jangan mengarang logo.

Gunakan:

```text
Blade HTML
        ↓
<img>
<svg>
<x-logo>
alt
title
aria-label
src
href
class
id
        ↓
Cari asset
        ↓
Gunakan asset asli
```

Jika logo adalah SVG dan dapat dibaca sebagai XML/text, baca isi SVG.

---

# ANDROID LOGO.XML

Buat launcher icon Android berdasarkan logo Laravel.

Periksa terlebih dahulu Android project Flutter.

Jangan mengarang struktur resource.

Jika menggunakan adaptive icon:

```xml
<adaptive-icon>
```

gunakan struktur adaptive icon Android yang benar.

Buat resource yang diperlukan seperti:

```text
mipmap-anydpi-v26/
mipmap-hdpi/
mipmap-mdpi/
mipmap-xhdpi/
mipmap-xxhdpi/
mipmap-xxxhdpi/
```

jika memang diperlukan oleh project.

Jika membutuhkan:

```text
ic_launcher.xml
ic_launcher_round.xml
```

buat dengan format yang benar.

Jika diminta membuat:

```text
logo.xml
```

buat `logo.xml` yang valid untuk resource Android yang benar-benar digunakan.

Jangan membuat file XML yang tidak direferensikan Android.

---

# SPLASH SCREEN

Splash Driver menggunakan:

* Laravel application logo
* Laravel primary color
* Laravel font/branding

Jangan menggunakan:

```text
Flutter logo
Flutter blue
Default Flutter branding
```

---

# PHASE 5 — DRIVER THEME

Gunakan Material 3 jika tidak ada specification lain.

Theme harus berasal dari branding Laravel.

Gunakan:

```dart
Theme.of(context)
```

atau:

```dart
AppColors.primary
```

Jangan hard-code warna di setiap screen.

---

# PHASE 6 — ROUTING

Buat route terstruktur.

Contoh:

```text
/splash
/login
/verification
/home
/orders
/orders/:id
/active-job
/earnings
/notifications
/profile
/settings
```

Hanya buat route yang benar-benar diperlukan.

---

# AUTHENTICATION FLOW

```text
App Start
 ↓
Splash
 ↓
Read Secure Token
 ↓
Validate Session
 ↓
┌───────────────────┐
│ Valid?             │
└────────┬──────────┘
     YES │ NO
         │
         ├────────→ Login
         ↓
    Driver Home
```

Token disimpan menggunakan secure storage.

---

# DRIVER AUTHORIZATION

Driver API harus menggunakan:

```text
Authorization: Bearer TOKEN
```

Jika endpoint membutuhkan driver role:

Backend yang menentukan authorization.

Jangan hanya mengandalkan role yang disimpan lokal.

---

# PHASE 7 — API CLIENT

Buat centralized API client:

```text
lib/core/network/
├── api_client.dart
├── api_exception.dart
├── api_response.dart
└── ...
```

Semua request menggunakan API client.

Base URL berada di satu tempat:

```text
API_BASE_URL
```

Jangan hard-code URL di setiap service.

---

# 401 HANDLING

Jika:

```text
401 Unauthorized
```

dan backend tidak memiliki refresh token:

```text
401
 ↓
Clear token
 ↓
Clear driver session
 ↓
Redirect Login
```

Jika backend memiliki refresh token:

```text
401
 ↓
Refresh
 ↓
Retry
```

Ikuti backend.

---

# PHASE 8 — DRIVER MODELS

Buat model berdasarkan API aktual.

Kemungkinan model:

```text
Driver
DriverProfile
DriverStatus
Order
OrderItem
Customer
ActiveJob
Earning
Notification
```

Hanya buat model yang benar-benar ada/diperlukan.

Jangan membuat model hanya karena "aplikasi driver biasanya punya itu".

---

# PHASE 9 — STATE MANAGEMENT

Gunakan satu state-management solution.

Jangan menggunakan beberapa framework sekaligus.

Contoh:

```text
Provider
```

atau:

```text
Riverpod
```

atau:

```text
Bloc
```

Pilih satu berdasarkan project.

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

Jangan melakukan HTTP request langsung dari Widget.

---

# PHASE 11 — DRIVER HOME

Driver Home harus berorientasi pada pekerjaan.

Jika sesuai specification, pola UI dapat berupa:

```text
┌─────────────────────────────┐
│ Profile       Notification  │
├─────────────────────────────┤
│                             │
│     DRIVER STATUS           │
│                             │
│      ONLINE / OFFLINE       │
│                             │
├─────────────────────────────┤
│ Current / Active Job        │
├─────────────────────────────┤
│ Available Jobs              │
├─────────────────────────────┤
│ Today's Summary             │
├─────────────────────────────┤
│ Earnings / Activity         │
├─────────────────────────────┤
│ Home | Jobs | Earnings      │
└─────────────────────────────┘
```

Ini hanya UX reference.

Gunakan elemen aktual dari `app-info.md`.

---

# DRIVER ONLINE/OFFLINE

Jika backend mendukung status driver:

UI harus memperlihatkan status aktual.

Flow:

```text
OFFLINE
   ↓
Tap Online
   ↓
API
   ↓
Success
   ↓
ONLINE
```

Jika API gagal:

```text
OFFLINE
   ↓
API Failed
   ↓
Remain OFFLINE
```

Jangan mengubah status UI menjadi online sebelum server berhasil.

---

# STATUS CONSISTENCY

Jangan:

```text
User taps Online
 ↓
Immediately show Online
 ↓
API fails
```

Gunakan:

```text
Tap Online
 ↓
Loading
 ↓
API
 ↓
Success
 ↓
Online
```

---

# PHASE 12 — AVAILABLE JOBS / ORDERS

Jika driver menerima order/job:

```text
Home
 ↓
Available Jobs
 ↓
List
 ↓
Job Detail
```

Setiap item minimal menampilkan data yang memang disediakan backend.

Contoh:

```text
Order number
Customer
Pickup
Destination
Distance
Price
Status
Created time
```

Hanya tampilkan field yang tersedia.

---

# ORDER DETAIL

Flow:

```text
Available Job
 ↓
Detail
 ↓
Accept / Reject
```

Jika accept:

```text
Accept
 ↓
Loading
 ↓
API
 ↓
Success
 ↓
Active Job
```

Jika gagal:

```text
Error
 ↓
Remain Available
```

---

# CONCURRENCY / DOUBLE ACCEPT

Jika backend memiliki kemungkinan beberapa driver menerima job yang sama:

Jangan menganggap job selalu tersedia.

Backend adalah sumber kebenaran.

Jika API memberikan:

```text
409 Conflict
```

atau status equivalent:

Tampilkan:

```text
Order sudah diambil driver lain.
```

Kemudian refresh available jobs.

---

# PHASE 13 — ACTIVE JOB

Jika ada active job:

```text
Active Job
 ↓
Job Detail
 ↓
Current Status
 ↓
Action
 ↓
Update Status
 ↓
Next Status
```

Contoh:

```text
Accepted
 ↓
Arrived
 ↓
Started
 ↓
Completed
```

**Jangan mengarang status.**

Gunakan status dari `app-info.md` / backend.

---

# STATUS TRANSITION

Jangan membiarkan driver memilih status sembarangan.

Jika backend menentukan:

```text
accepted → started → completed
```

Flutter hanya menyediakan action yang valid untuk status saat ini.

Contoh:

```text
accepted
   ↓
"Start Job"
```

setelah berhasil:

```text
started
   ↓
"Complete Job"
```

---

# PHASE 14 — LOCATION

Jika aplikasi membutuhkan lokasi driver berdasarkan specification:

Gunakan location service yang sesuai.

Flow:

```text
App
 ↓
Check Location Permission
 ↓
Permission Granted?
 ├── YES → Get Location
 └── NO → Explain Permission
```

Jangan meminta permission tanpa alasan yang jelas.

---

# LOCATION PERMISSION

Jika permission ditolak:

Tampilkan pesan yang jelas:

```text
Aplikasi membutuhkan akses lokasi
untuk menjalankan fitur pekerjaan driver.
```

Jangan membuat aplikasi crash.

---

# LOCATION UPDATE

Jika backend membutuhkan live location:

```text
GPS
 ↓
Location Service
 ↓
Throttle / Optimize
 ↓
Laravel API
```

Jangan mengirim request setiap perubahan GPS tanpa kontrol.

Gunakan interval/distance threshold sesuai kebutuhan.

Jangan membuat battery drain yang tidak perlu.

---

# BACKGROUND LOCATION

Jangan mengimplementasikan background location hanya karena aplikasi driver "biasanya" membutuhkannya.

Implementasikan hanya jika:

```text
app-info.md
```

atau backend benar-benar membutuhkan.

Jika diperlukan:

* periksa Android permission
* periksa foreground service requirement
* periksa iOS background mode
* periksa privacy implications
* gunakan package yang maintained
* test foreground
* test background
* test app terminated

---

# PHASE 15 — EARNINGS

Jika schema/API menyediakan earnings:

Buat:

```text
Earnings
```

dengan flow:

```text
Earnings
 ↓
Summary
 ↓
Period Filter
 ↓
Transaction List
 ↓
Detail
```

Gunakan data backend.

Jangan menghitung earnings sendiri jika backend sudah menyediakan hasil final.

---

# PHASE 16 — HISTORY

Jika tersedia:

```text
History
 ↓
Filter
 ↓
List
 ↓
Detail
```

Gunakan pagination jika backend mendukung.

---

# PHASE 17 — NOTIFICATIONS

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

Notification badge harus mencerminkan data backend jika backend menyediakan unread count.

---

# PHASE 18 — PROFILE

Driver profile:

```text
Profile
 ↓
Driver Information
 ↓
Edit
 ↓
Validation
 ↓
API
 ↓
Success
```

Jika ada document verification:

```text
Profile
 ↓
Documents
 ↓
Status
```

Gunakan data backend.

---

# PHASE 19 — FILE / DOCUMENT UPLOAD

Jika driver harus mengupload dokumen:

```text
Select File
 ↓
Preview
 ↓
Validate
 ↓
Upload
 ↓
Server
 ↓
Status
```

Validasi:

* file type
* size
* extension
* upload error

Jangan menyimpan credential atau sensitive API secret di mobile.

---

# PHASE 20 — DRIVER NOTIFICATION / NEW JOB

Jika backend memiliki mechanism untuk job notification:

Gunakan mechanism yang benar-benar tersedia.

Jangan mengarang websocket/push notification implementation jika backend belum mendukungnya.

Jika API menggunakan polling:

Implementasikan polling secara aman.

Jika menggunakan WebSocket:

Ikuti backend contract.

Jika menggunakan Firebase Cloud Messaging:

Gunakan konfigurasi yang benar.

---

# PHASE 21 — ERROR HANDLING

Semua API-driven page wajib menangani:

```text
Initial
Loading
Success
Empty
Error
Retry
```

Network error harus user-friendly.

Jangan tampilkan:

```text
SocketException
DioException
Stack trace
```

kepada user.

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

sesuai response backend.

Contoh:

```text
401
→ Login

403
→ Tidak memiliki akses

404
→ Data tidak ditemukan

409
→ Status/order sudah berubah

422
→ Validation error

429
→ Terlalu banyak request

500
→ Server error
```

---

# PHASE 22 — FORM

Semua form:

```text
Input
 ↓
Validate
 ↓
Submit
 ↓
Disable button
 ↓
Loading
 ↓
API
 ↓
Success / Error
```

Jangan izinkan double-submit.

---

# PHASE 23 — DRIVER BOTTOM NAVIGATION

Jika specification membutuhkan:

Contoh:

```text
Home
Jobs
History
Earnings
Profile
```

Tetapi jangan membuat item yang tidak ada.

Gunakan feature aktual.

---

# PHASE 24 — RESPONSIVE UI

Pastikan UI bekerja pada:

```text
small phone
normal phone
large phone
tablet
```

Hindari fixed width berlebihan.

Gunakan:

```text
Expanded
Flexible
LayoutBuilder
MediaQuery
SafeArea
```

jika diperlukan.

---

# PHASE 25 — DEPENDENCY RULE

Sebelum menambahkan package:

1. cek Flutter built-in
2. cek package existing
3. cek compatibility
4. cek maintenance
5. tambahkan hanya jika benar-benar diperlukan

Jangan menambahkan package secara random.

Setelah dependency berubah:

```bash
flutter pub get
flutter analyze
flutter test
```

---

# PHASE 26 — ANDROID BUILD STABILITY

Jangan mengubah Android configuration secara random.

Jika ada error:

Periksa:

```text
Flutter version
Dart version
Java version
Gradle
Android Gradle Plugin
Kotlin
compileSdk
targetSdk
minSdk
```

Jangan langsung:

```text
upgrade everything
```

atau:

```text
downgrade everything
```

Cari root cause terlebih dahulu.

---

# PHASE 27 — TEST SETIAP FEATURE

Setelah setiap feature:

```bash
flutter analyze
flutter test
```

Jika ada error:

```text
STOP
 ↓
Read full error
 ↓
Identify root cause
 ↓
Smallest fix
 ↓
flutter analyze
 ↓
flutter test
 ↓
Continue
```

Jangan menumpuk error.

---

# PHASE 28 — DRIVER SECURITY

Jangan menyimpan:

```text
Database credentials
Laravel APP_KEY
Admin credentials
Private API secret
```

di Flutter.

Semua authorization harus divalidasi backend.

Jangan percaya:

```text
driver_id
role
price
earning
order ownership
```

yang dikirim client.

Backend harus menjadi sumber kebenaran.

---

# DRIVER OWNERSHIP

Driver hanya boleh melihat/mengubah data yang memang menjadi haknya.

Jangan mengandalkan:

```text
GET /orders/123
```

hanya karena user mengetahui ID.

Backend harus melakukan authorization.

Flutter hanya mengikuti API.

---

# PHASE 29 — API AUDIT

Setelah semua feature:

Buat matrix:

```text
SCREEN / ACTION
API
METHOD
AUTH
REQUEST
RESPONSE
STATUS
```

Pastikan semua endpoint benar-benar tersedia di Laravel.

---

# PHASE 30 — FEATURE AUDIT

Bandingkan:

```text
app-info.md
      ↓
Driver Feature
      ↓
Flutter Screen
      ↓
State
      ↓
Repository
      ↓
API
      ↓
Database
```

Pastikan tidak ada feature yang hanya dibuat UI-nya.

---

# PHASE 31 — BRANDING AUDIT

Periksa:

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

# PHASE 32 — FINAL TEST

Masuk ke:

```bash
cd app-driver
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
└── app-driver/
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
    │   │   ├── location/
    │   │   ├── utils/
    │   │   └── widgets/
    │   │
    │   ├── features/
    │   │   ├── auth/
    │   │   ├── home/
    │   │   ├── orders/
    │   │   ├── active_job/
    │   │   ├── earnings/
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

`app-driver` hanya boleh dianggap selesai jika:

```text
✓ Laravel tetap berjalan
✓ app-customer tidak rusak
✓ app-driver merupakan Flutter project valid
✓ Flutter berada di app-driver/
✓ flutter pub get berhasil
✓ flutter analyze tidak memiliki error
✓ flutter test berhasil
✓ Android debug build berhasil
✓ Driver authentication berjalan
✓ Secure token storage berjalan
✓ Driver authorization mengikuti backend
✓ Driver profile berjalan
✓ Driver status berjalan jika tersedia
✓ Available job/order berjalan jika tersedia
✓ Accept/reject berjalan jika tersedia
✓ Active job berjalan jika tersedia
✓ Status transition sesuai backend
✓ Location berjalan jika diperlukan
✓ Earnings berjalan jika tersedia
✓ History berjalan jika tersedia
✓ Notification berjalan jika tersedia
✓ Loading state tersedia
✓ Empty state tersedia
✓ Error state tersedia
✓ Retry tersedia
✓ Network error ditangani
✓ Form validation tersedia
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
install banyak package sekaligus
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
membuat dummy driver/order
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
mengubah app-customer
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

WAJIB mengikuti urutan:

```text
READ app-info.md
        ↓
READ schema.sql
        ↓
AUDIT LARAVEL DATABASE
        ↓
AUDIT LARAVEL API
        ↓
AUDIT DRIVER ROLE
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
AUDIT EXISTING app-customer
        ↓
CHECK FLUTTER / DART VERSION
        ↓
CREATE app-driver/
        ↓
CREATE FLUTTER PROJECT
        ↓
BASELINE flutter analyze
        ↓
BASELINE flutter test
        ↓
CREATE DRIVER BRAND THEME
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
DRIVER SESSION
        ↓
APP SHELL
        ↓
DRIVER HOME
        ↓
DRIVER STATUS
        ↓
FEATURE #1
        ↓
ANALYZE + TEST
        ↓
FEATURE #2
        ↓
ANALYZE + TEST
        ↓
ACTIVE JOB
        ↓
ANALYZE + TEST
        ↓
LOCATION IF REQUIRED
        ↓
ANALYZE + TEST
        ↓
EARNINGS / HISTORY
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

Jangan langsung coding seluruh aplikasi.

Pertama:

1. baca `app-info.md`
2. baca `schema.sql`
3. audit Laravel API
4. audit driver role
5. audit Laravel layout
6. audit warna
7. audit font
8. audit logo
9. audit Blade/HTML yang menggunakan logo
10. audit existing `app-customer`
11. periksa Flutter/Dart version

Setelah audit selesai, buat:

```text
app-driver/
```

dan Flutter project di dalamnya.

**Jangan merusak Laravel atau `app-customer`.**

Kerjakan secara incremental.

Setelah setiap phase, jalankan:

```bash
flutter analyze
flutter test
```

dan **jangan lanjut jika phase sebelumnya masih memiliki error.**
