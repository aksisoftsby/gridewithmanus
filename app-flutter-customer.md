# MASTER TASK — BUILD FLUTTER CUSTOMER APP INSIDE LARAVEL PROJECT

Saya memiliki project **Laravel backend yang sudah terinstall dan berjalan**.

Flutter CLI juga sudah terinstall di Windows dan dapat digunakan melalui terminal.

Sekarang saya ingin membuat **Flutter Customer Mobile Application** dari awal.

## STRUKTUR PROJECT WAJIB

Flutter application HARUS dibuat di dalam folder:

```text
app-customer/
```

Folder tersebut berada di dalam root Laravel backend.

Contoh struktur:

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
├── app-info.md
├── schema.sql
├── composer.json
│
└── app-customer/
    ├── android/
    ├── ios/
    ├── lib/
    ├── test/
    ├── pubspec.yaml
    └── ...
```

**SEMUA kode Flutter Customer App harus berada di dalam `app-customer/`.**

Jangan membuat:

```text
backend/lib/
backend/android/
backend/ios/
backend/pubspec.yaml
```

Flutter TIDAK BOLEH dibuat di root Laravel.

---

# IMPORTANT — LARAVEL DAN FLUTTER ADALAH PROJECT TERPISAH

Anggap struktur ini sebagai:

```text
Laravel Backend
       │
       │ HTTPS REST API
       ↓
app-customer/
Flutter Customer App
```

Laravel tetap menjadi backend/API.

Flutter menjadi client/mobile application.

Flutter tidak boleh mengakses database secara langsung.

Flow:

```text
Flutter Customer
       ↓
HTTPS
       ↓
Laravel API
       ↓
Database
```

---

# SUMBER SPESIFIKASI

Di root Laravel tersedia:

```text
app-info.md
schema.sql
```

Baca kedua file tersebut SECARA PENUH.

Selain itu, jika Laravel API sudah tersedia, baca:

* routes API
* API controllers
* API Resources
* Form Requests
* API Documentation
* authentication implementation

Prioritas:

```text
1. app-info.md
2. schema.sql
3. Laravel API implementation
4. Laravel API Documentation
5. Flutter best practices
```

Jangan mengarang fitur atau endpoint.

---

# TUJUAN

Buat aplikasi Flutter Customer dengan konsep:

**modern superapp seperti Grab**

Tetapi:

> Grab hanya menjadi REFERENSI UX/PRODUCT PATTERN.

Jangan menyalin:

* logo
* branding
* warna khas
* asset
* icon
* UI secara identik
* fitur yang tidak dibutuhkan

Fitur aplikasi HARUS berdasarkan `app-info.md` dan `schema.sql`.

---

# ATURAN PALING PENTING

Jangan langsung membuat seluruh aplikasi.

Gunakan sistem PHASE.

Setiap phase:

```text
PLAN
 ↓
IMPLEMENT
 ↓
flutter analyze
 ↓
flutter test
 ↓
FIX
 ↓
VERIFY
 ↓
NEXT PHASE
```

Jika phase sebelumnya masih error:

**JANGAN lanjut ke phase berikutnya.**

---

# PHASE 0 — AUDIT PROJECT

Sebelum membuat Flutter project:

Periksa:

```text
Current directory
Laravel root
app-info.md
schema.sql
Flutter version
Dart version
```

Jalankan:

```bash
flutter --version
flutter doctor
```

Kemudian baca:

```text
app-info.md
schema.sql
```

secara penuh.

Periksa Laravel API:

```bash
php artisan route:list
```

Jika API sudah tersedia, identifikasi seluruh endpoint yang diperlukan mobile customer.

---

# PHASE 1 — CREATE FLUTTER PROJECT

Masuk ke root Laravel.

Buat folder:

```text
app-customer
```

Jika folder belum ada.

Kemudian buat Flutter project DI DALAM folder tersebut.

Contoh:

```bash
flutter create app-customer
```

Jika folder sudah dibuat kosong:

```bash
cd app-customer
flutter create .
```

Gunakan command yang aman berdasarkan kondisi folder.

**Jangan overwrite Laravel files.**

Setelah project dibuat:

```bash
cd app-customer
flutter pub get
flutter analyze
flutter test
```

Pastikan baseline Flutter project berhasil.

---

# PHASE 2 — ARCHITECTURE

Semua Flutter code berada di:

```text
app-customer/
```

Recommended structure:

```text
app-customer/
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
│   │   ├── utils/
│   │   └── widgets/
│   │
│   ├── features/
│   │   ├── auth/
│   │   ├── home/
│   │   ├── profile/
│   │   ├── notification/
│   │   └── ...
│   │
│   └── shared/
│       ├── models/
│       ├── widgets/
│       └── services/
│
└── pubspec.yaml
```

Jangan membuat architecture terlalu kompleks.

Jika specification membutuhkan struktur berbeda, gunakan struktur yang lebih sesuai.

---

# PHASE 3 — DESIGN SYSTEM

Buat:

* App Theme
* colors
* typography
* spacing
* buttons
* text fields
* cards
* dialogs
* loading
* empty state
* error state
* bottom navigation
* app bar

Gunakan Material 3 jika tidak ada design system khusus.

Semua reusable component diletakkan di:

```text
lib/core/widgets/
```

atau lokasi yang sesuai architecture.

Jangan membuat widget duplicate.

---

# PHASE 4 — ROUTING

Buat routing yang terstruktur.

Contoh:

```text
/splash
/login
/register
/home
/profile
/notifications
/feature
/feature/:id
```

Sesuaikan dengan feature aktual.

Authentication guard harus tersedia jika dibutuhkan.

Flow:

```text
App Start
    ↓
Splash
    ↓
Check Token
    ↓
┌───────────────┐
│ Authenticated?│
└───────┬───────┘
    YES │ NO
        │
        ├──────────────→ Login
        ↓
       Home
```

---

# PHASE 5 — API CLIENT

Flutter harus berkomunikasi dengan Laravel melalui REST API.

Buat centralized API client.

Contoh:

```text
lib/core/network/
├── api_client.dart
├── api_exception.dart
├── api_response.dart
└── ...
```

Base URL harus berada di satu tempat.

Contoh:

```text
API_BASE_URL
```

Jangan hard-code URL di setiap service.

---

# API CONTRACT

API Laravel adalah contract.

Flutter HARUS mengikuti:

```text
HTTP Method
Endpoint
Headers
Authentication
Request
Response
Validation
Error
Pagination
```

berdasarkan API implementation/documentation.

Jangan mengarang endpoint.

Contoh:

Jika backend memiliki:

```text
POST /api/v1/login
```

Flutter menggunakan endpoint tersebut.

Jangan membuat:

```text
POST /api/login-user
```

hanya karena menurut AI lebih bagus.

---

# PHASE 6 — AUTHENTICATION

Implementasikan:

```text
Splash
 ↓
Check stored token
 ↓
Authenticated?
 ├── YES → Home
 └── NO → Login
```

Login:

```text
Login Form
 ↓
Validation
 ↓
POST Laravel API
 ↓
Receive Token
 ↓
Secure Storage
 ↓
Load Profile
 ↓
Home
```

Logout:

```text
Logout
 ↓
Clear token
 ↓
Clear local user data
 ↓
Login
```

Gunakan secure storage untuk authentication token.

---

# TOKEN HANDLING

Authenticated API request otomatis menggunakan:

```text
Authorization: Bearer TOKEN
```

Jangan menambahkan token secara manual di setiap request.

Gunakan centralized API client/interceptor.

Jika server memberikan:

```text
401 Unauthorized
```

dan tidak ada refresh-token mechanism:

```text
401
 ↓
Clear authentication
 ↓
Redirect Login
```

Jika backend memiliki refresh token, implementasikan sesuai backend.

Jangan membuat refresh token sendiri jika backend tidak mendukungnya.

---

# PHASE 7 — MODELS

Buat model berdasarkan API response aktual.

Contoh hanya jika diperlukan:

```text
User
Product
Category
Order
OrderItem
Payment
Notification
```

Jangan membuat model hanya berdasarkan tebakan dari nama tabel.

Model harus sesuai API.

Gunakan:

```text
fromJson()
toJson()
```

jika diperlukan.

Pastikan null safety benar.

---

# PHASE 8 — DATA FLOW

Gunakan pola:

```text
UI
 ↓
Controller / State
 ↓
Repository
 ↓
API Client
 ↓
Laravel API
 ↓
Database
```

Jangan melakukan HTTP request langsung dari widget/page untuk feature utama.

Contoh yang harus dihindari:

```dart
onPressed: () async {
  final response = await http.get(...);
}
```

Gunakan repository/service layer.

---

# PHASE 9 — STATE MANAGEMENT

Gunakan SATU state management solution.

Jika tidak ditentukan:

Pilih solusi yang:

* stabil
* simple
* maintainable
* cocok dengan Flutter
* cocok untuk API-driven application

Jangan menggunakan:

```text
Provider + Riverpod + Bloc
```

secara bersamaan.

Pilih satu.

---

# PHASE 10 — APP SHELL

Setelah authentication stabil, buat app shell.

Jika sesuai specification:

```text
Home
Activity
Notifications
Profile
```

Gunakan bottom navigation.

Contoh:

```text
┌─────────────────────────────┐
│                             │
│          CONTENT            │
│                             │
├─────────────────────────────┤
│ Home | Activity | Profile   │
└─────────────────────────────┘
```

Navigation item harus mengikuti feature aktual.

---

# PHASE 11 — HOME / SUPERAPP DASHBOARD

Buat home dengan pola superapp modern.

Contoh:

```text
Profile / Greeting
        ↓
Search
        ↓
Service / Feature Grid
        ↓
Banner / Important Info
        ↓
Recommended / Recent
        ↓
Bottom Navigation
```

Feature grid harus berdasarkan feature dari `app-info.md`.

Jangan menambahkan:

```text
Food
Ride
Delivery
Mart
```

jika fitur tersebut tidak ada di specification.

---

# PHASE 12 — IMPLEMENT FEATURE SATU PER SATU

Jangan membuat semua feature sekaligus.

Gunakan:

```text
Feature
 ↓
UI
 ↓
Model
 ↓
Repository
 ↓
API
 ↓
State
 ↓
Loading
 ↓
Empty
 ↓
Error
 ↓
Test
 ↓
Verify
```

Kemudian lanjut feature berikutnya.

---

# STANDARD FEATURE FLOW

### LIST

```text
Open Page
 ↓
Loading
 ↓
API
 ↓
Success
 ↓
List
```

Jika tidak ada data:

```text
Empty State
```

Jika error:

```text
Error State
 ↓
Retry
```

---

### DETAIL

```text
List
 ↓
Tap Item
 ↓
Detail
 ↓
Load API
 ↓
Display
```

---

### FORM

```text
Form
 ↓
Input
 ↓
Client Validation
 ↓
Submit
 ↓
Disable Button
 ↓
Loading
 ↓
API
 ↓
Success / Error
```

---

### TRANSACTION

Jika aplikasi memiliki transaction/order:

```text
Browse
 ↓
Select
 ↓
Detail
 ↓
Action
 ↓
Confirmation
 ↓
Submit API
 ↓
Processing
 ↓
Success
 ↓
Transaction Detail
```

Sesuaikan dengan `app-info.md`.

---

# STATE WAJIB

Setiap API-driven page harus menangani:

```text
Initial
Loading
Success
Empty
Error
Retry
```

Jangan hanya membuat successful state.

---

# PHASE 13 — NETWORK ERROR

Jika internet gagal:

Tampilkan user-friendly message.

Jangan menampilkan:

```text
SocketException
Failed host lookup
DioException
Stack trace
```

langsung kepada user.

Contoh:

```text
Tidak dapat terhubung ke server.
Periksa koneksi internet Anda dan coba lagi.
```

Gunakan error handling centralized.

---

# PHASE 14 — FORM VALIDATION

Semua form harus melakukan:

```text
Required
Email
Password
Number
Date
Length
Format
```

sesuai backend validation.

Client validation tidak menggantikan backend validation.

---

# PHASE 15 — LIST / PAGINATION

Untuk data besar:

```text
Pagination
Pull to Refresh
Loading More
Empty
Error
Retry
```

Gunakan pagination yang sesuai API Laravel.

Jangan membuat infinite request.

---

# PHASE 16 — FILE / IMAGE

Jika specification membutuhkan upload:

```text
Select
 ↓
Preview
 ↓
Validate
 ↓
Upload
 ↓
Progress
 ↓
Success
 ↓
Error
```

Validasi:

* MIME/type
* size
* extension
* upload failure

---

# PHASE 17 — TEST SETIAP FEATURE

Setelah setiap feature:

```bash
flutter analyze
flutter test
```

Jika error:

```text
STOP
 ↓
Read complete error
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

# DEPENDENCY RULE

Sebelum menambahkan package:

1. cek Flutter built-in solution
2. cek package existing
3. cek compatibility
4. cek maintenance
5. pilih package yang benar-benar diperlukan

Setelah menambahkan package:

```bash
flutter pub get
flutter analyze
flutter test
```

harus tetap berhasil.

---

# ANDROID RULE

Jangan melakukan perubahan random pada:

```text
android/build.gradle
android/settings.gradle
android/gradle.properties
gradle wrapper
Kotlin version
Android Gradle Plugin
compileSdk
Java
```

Jika ada error Android:

Periksa compatibility matrix:

```text
Flutter version
↓
Dart
↓
Java
↓
Gradle
↓
Android Gradle Plugin
↓
Kotlin
↓
compileSdk
```

Lakukan perubahan seminimal mungkin.

Jangan upgrade semua dependency hanya untuk menghilangkan satu error.

---

# IOS RULE

Untuk tahap pertama:

**Fokus Android terlebih dahulu.**

Jangan melakukan perubahan iOS yang tidak diperlukan.

Setelah Android stabil, iOS dapat diuji dan diperbaiki pada phase terpisah.

---

# CODE QUALITY

Jangan meninggalkan:

```text
TODO
FIXME
dummy API
fake response
hardcoded token
hardcoded credential
duplicate model
duplicate API client
duplicate theme
dead code
```

kecuali memang diperlukan secara eksplisit.

Jangan menggunakan:

```dart
!
```

secara sembarangan hanya untuk menghilangkan null-safety error.

---

# SECURITY

Jangan pernah memasukkan ke Flutter:

```text
Database username
Database password
Laravel APP_KEY
Admin password
Private API secret
```

Flutter application adalah client dan dapat dibongkar.

Security dan authorization harus dilakukan Laravel.

---

# FINAL API AUDIT

Setelah semua feature selesai:

Buat tabel:

```text
SCREEN | ENDPOINT | METHOD | AUTH | STATUS
```

Contoh:

```text
Login       | /api/v1/login      | POST | No   | OK
Profile     | /api/v1/profile    | GET  | Yes  | OK
Products    | /api/v1/products   | GET  | Yes  | OK
Order       | /api/v1/orders     | POST | Yes  | OK
```

Gunakan endpoint aktual.

---

# FINAL FEATURE AUDIT

Bandingkan:

```text
app-info.md
      ↓
Required Feature
      ↓
Flutter Screen
      ↓
Flutter State
      ↓
Repository
      ↓
Laravel API
      ↓
Database
```

Pastikan tidak ada feature yang hanya dibuat UI-nya tetapi belum terhubung API.

---

# FINAL BUILD

Sebelum menyatakan selesai:

Masuk ke:

```bash
cd app-customer
```

Kemudian:

```bash
flutter clean
flutter pub get
flutter analyze
flutter test
flutter build apk --debug
```

Semua harus berhasil.

Jika diminta release:

```bash
flutter build apk --release
```

Jangan membuat signing key tanpa instruksi.

---

# DEFINISI SELESAI

Project hanya boleh dianggap selesai jika:

```text
✓ Laravel tetap berjalan
✓ Flutter berada di app-customer/
✓ Flutter project valid
✓ flutter pub get berhasil
✓ flutter analyze tidak memiliki error
✓ flutter test berhasil
✓ Android debug build berhasil
✓ Authentication berjalan
✓ Token storage aman
✓ API integration berjalan
✓ Navigation berjalan
✓ Semua feature app-info.md tersedia
✓ API sesuai Laravel
✓ Database sesuai schema.sql
✓ Loading state tersedia
✓ Empty state tersedia
✓ Error state tersedia
✓ Retry tersedia
✓ Form validation tersedia
✓ Network error ditangani
✓ Tidak ada dummy API
✓ Tidak ada hardcoded credential
✓ Tidak ada endpoint yang dikarang
```

---

# FINAL PROJECT STRUCTURE

Target akhirnya:

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
└── app-customer/
    │
    ├── android/
    ├── ios/
    ├── lib/
    │   ├── app/
    │   ├── core/
    │   ├── features/
    │   └── shared/
    │
    ├── test/
    ├── pubspec.yaml
    └── ...
```

---

# DEVELOPMENT FLOW FINAL

WAJIB mengikuti flow:

```text
READ app-info.md
        ↓
READ schema.sql
        ↓
AUDIT LARAVEL API
        ↓
CHECK FLUTTER / DART VERSION
        ↓
CREATE app-customer/
        ↓
CREATE FLUTTER PROJECT
        ↓
BASELINE flutter analyze
        ↓
BASELINE flutter test
        ↓
ARCHITECTURE
        ↓
THEME
        ↓
ROUTING
        ↓
API CLIENT
        ↓
AUTHENTICATION
        ↓
APP SHELL
        ↓
HOME
        ↓
FEATURE #1
        ↓
ANALYZE + TEST
        ↓
FEATURE #2
        ↓
ANALYZE + TEST
        ↓
FEATURE #3
        ↓
ANALYZE + TEST
        ↓
TRANSACTION
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

Mulai **HANYA dari PHASE 0**.

Jangan langsung membuat seluruh aplikasi.

Pertama-tama baca dan pahami:

```text
app-info.md
schema.sql
```

kemudian audit Laravel API dan Flutter environment.

Setelah itu mulai membuat:

```text
app-customer/
```

dan Flutter project di dalamnya.

**Laravel root tidak boleh dirusak.**

Kerjakan secara incremental dan selalu pastikan phase sebelumnya bersih sebelum lanjut.
