# TASK: Build Laravel API for Mobile Application

Saya sudah memiliki project **Laravel yang sudah terinstall dan bisa dijalankan**.

Di root project terdapat dua file utama:

* `app-info.md` → berisi spesifikasi lengkap aplikasi mobile, fitur, flow, kebutuhan data, dan aturan bisnis.
* `schema.sql` → berisi struktur database yang harus digunakan.

## TUJUAN

Baca dan pahami **SELURUH isi `app-info.md` dan `schema.sql` terlebih dahulu**.

Setelah itu, implementasikan **REST API Laravel** yang akan digunakan oleh mobile application berdasarkan spesifikasi tersebut.

Selain API untuk mobile app, buat juga **API Documentation yang dapat diakses dari halaman Admin Laravel**.

---

# ATURAN UTAMA

### 1. Jangan mengarang spesifikasi

`app-info.md` adalah sumber utama untuk:

* fitur
* flow aplikasi
* endpoint yang dibutuhkan
* request
* response
* authentication
* authorization
* business logic
* validasi
* status/error
* relasi data
* kebutuhan mobile application

`schema.sql` adalah sumber utama untuk:

* tabel
* kolom
* tipe data
* primary key
* foreign key
* index
* relasi database

Jangan membuat struktur database baru jika sebenarnya sudah tersedia di `schema.sql`.

Jika ada informasi yang tidak jelas atau konflik antara `app-info.md` dan `schema.sql`, jangan diam-diam membuat asumsi. Identifikasi konflik tersebut dan pilih implementasi yang paling aman serta konsisten dengan struktur project.

---

# LANGKAH 1 — ANALISIS PROJECT

Sebelum melakukan perubahan kode:

1. Periksa struktur project Laravel.
2. Tentukan versi Laravel yang digunakan.
3. Periksa konfigurasi database.
4. Periksa model yang sudah tersedia.
5. Periksa migration yang sudah tersedia.
6. Periksa controller yang sudah tersedia.
7. Periksa routes yang sudah tersedia.
8. Periksa middleware.
9. Periksa authentication system.
10. Periksa package yang sudah terinstall.
11. Periksa apakah sudah ada admin panel.
12. Periksa apakah sudah ada API sebelumnya.

Jangan merusak functionality yang sudah ada.

Gunakan architecture dan convention yang sudah digunakan project jika memang masih layak.

---

# LANGKAH 2 — BACA SPESIFIKASI

Baca:

```text
app-info.md
schema.sql
```

secara penuh.

Jangan hanya membaca bagian awal atau mencari keyword tertentu.

Setelah memahami keduanya, buat internal implementation plan yang mencakup:

* database entities
* relationships
* mobile app features
* authentication flow
* authorization
* API endpoints
* request parameters
* validation
* response format
* pagination
* filtering
* sorting
* error handling
* business rules
* file/image handling jika ada
* notification jika ada
* admin requirements

Jangan mengubah `app-info.md` atau `schema.sql` kecuali memang diperlukan dan dijelaskan alasannya.

---

# LANGKAH 3 — IMPLEMENTASI API

Buat REST API Laravel untuk mobile application sesuai `app-info.md`.

Gunakan struktur API yang rapi dan konsisten.

Minimal perhatikan:

```text
/routes/api.php

app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   ├── Resources/
│   └── Middleware/
├── Models/
├── Services/
└── ...
```

Namun jangan memaksakan struktur tersebut jika project sudah memiliki architecture yang berbeda dan architecture existing lebih sesuai.

---

# API DESIGN

Semua endpoint mobile harus:

* konsisten
* mudah digunakan oleh Flutter/mobile application
* menggunakan HTTP status code yang benar
* memiliki response JSON yang konsisten
* memiliki validation
* memiliki authentication/authorization jika diperlukan
* tidak mengekspos data database yang tidak diperlukan

Gunakan format response yang konsisten, misalnya:

```json
{
    "success": true,
    "message": "Success",
    "data": {}
}
```

Untuk error:

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {}
}
```

Sesuaikan format tersebut jika `app-info.md` sudah menentukan format response sendiri.

**Jangan override spesifikasi yang sudah ada di `app-info.md`.**

---

# AUTHENTICATION

Jika `app-info.md` membutuhkan authentication:

Implementasikan authentication yang sesuai dengan kebutuhan mobile app.

Perhatikan:

* login
* logout
* token
* token expiration
* refresh token jika diperlukan
* password hashing
* authorization
* protected endpoints
* guest endpoints

Gunakan package/authentication mechanism yang sudah tersedia di project jika sesuai.

Jangan menambahkan package baru jika sebenarnya functionality tersebut sudah tersedia.

Jika harus menambahkan dependency, jelaskan alasannya.

---

# VALIDATION

Gunakan Laravel Form Request atau mekanisme validation Laravel yang sesuai.

Semua input dari mobile application harus divalidasi.

Perhatikan:

* required
* nullable
* string
* integer
* numeric
* boolean
* email
* date
* enum/status
* min/max
* exists
* unique
* authorization
* file/image validation jika diperlukan

Jangan hanya melakukan validation di sisi mobile.

---

# DATABASE

Gunakan `schema.sql` sebagai referensi utama database.

Jangan mengubah nama:

* table
* column
* foreign key

tanpa alasan yang kuat.

Gunakan Eloquent Model dan relationship Laravel jika memungkinkan.

Buat relationship yang sesuai seperti:

```text
hasOne
hasMany
belongsTo
belongsToMany
```

sesuai struktur database.

Perhatikan N+1 query.

Gunakan eager loading jika diperlukan.

---

# BUSINESS LOGIC

Business logic yang dijelaskan dalam `app-info.md` harus benar-benar diimplementasikan di backend.

Jangan hanya membuat CRUD sederhana jika spesifikasi membutuhkan proses khusus.

Pisahkan business logic ke Service/Action class jika kompleks.

Controller sebaiknya tetap sederhana.

Contoh:

```text
Controller
    ↓
Request Validation
    ↓
Service / Action
    ↓
Model / Database
    ↓
Resource
    ↓
JSON Response
```

Tidak wajib menggunakan struktur ini jika architecture project existing berbeda, tetapi hindari controller yang terlalu besar.

---

# SECURITY

Pastikan API aman.

Perhatikan:

* authentication
* authorization
* mass assignment
* SQL injection
* validation
* IDOR
* sensitive data exposure
* password hashing
* token security
* rate limiting jika diperlukan
* file upload validation
* ownership checking

User mobile tidak boleh dapat mengakses atau memodifikasi data milik user lain hanya dengan mengganti ID pada request.

---

# API DOCUMENTATION

Selain API, buat **API Documentation yang dapat dibuka dari halaman Admin**.

Documentation harus mengambil informasi dari API implementation sehingga dokumentasi tidak mudah berbeda dengan endpoint sebenarnya.

Minimal documentation harus menampilkan:

### Endpoint

Contoh:

```text
POST /api/v1/login
GET  /api/v1/profile
GET  /api/v1/products
GET  /api/v1/products/{id}
POST /api/v1/products
```

### Informasi setiap endpoint

Tampilkan:

* HTTP method
* URL
* authentication requirement
* description
* parameters
* request body
* headers
* validation rules
* example request
* example success response
* example error response
* HTTP status codes

Contoh:

```text
POST /api/v1/login

Description:
Login mobile user.

Authentication:
Not required.

Request:

{
    "email": "user@example.com",
    "password": "password"
}

Response:

{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "...",
        "user": {}
    }
}
```

---

# ADMIN API DOCUMENTATION

Tambahkan menu pada Admin:

```text
Admin
 └── API Documentation
```

Halaman tersebut harus:

* mudah dibaca
* memiliki daftar endpoint
* dapat dikelompokkan berdasarkan feature/module
* menampilkan method HTTP
* menampilkan request/response example
* memiliki syntax highlighting jika memungkinkan
* responsive
* tidak dapat diakses public
* hanya dapat diakses oleh admin/authorized user

Jika project sudah memiliki admin authentication, gunakan authentication tersebut.

Jangan membuat authentication admin baru tanpa alasan.

---

# API VERSIONING

Jika belum ditentukan oleh project, gunakan:

```text
/api/v1/
```

Contoh:

```text
/api/v1/login
/api/v1/profile
/api/v1/products
```

Semua endpoint mobile API sebaiknya berada di bawah version tersebut.

Jika `app-info.md` menentukan format lain, ikuti `app-info.md`.

---

# ERROR HANDLING

Buat error response yang konsisten.

Tangani minimal:

```text
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
422 Validation Error
429 Too Many Requests
500 Internal Server Error
```

Jangan mengirim stack trace atau informasi sensitif ke mobile app pada production.

---

# PAGINATION

Untuk endpoint list yang berpotensi memiliki banyak data:

Gunakan pagination Laravel.

Response harus mudah diproses oleh mobile application.

Jika `app-info.md` menentukan format pagination tertentu, ikuti format tersebut.

---

# PERFORMANCE

Perhatikan:

* N+1 query
* eager loading
* unnecessary queries
* pagination
* selecting only required columns
* caching jika memang diperlukan
* database indexes dari `schema.sql`

Jangan melakukan premature optimization yang tidak diperlukan.

---

# TESTING

Setelah implementasi:

1. Jalankan Laravel test.
2. Buat/update Feature Test untuk endpoint penting.
3. Test authentication.
4. Test validation.
5. Test authorization.
6. Test CRUD jika ada.
7. Test business logic.
8. Test error response.
9. Test endpoint dengan database.

Jika project belum memiliki testing infrastructure, buat test minimal yang relevan.

---

# ROUTE AUDIT

Setelah selesai, periksa seluruh API route.

Pastikan:

* tidak ada duplicate route
* HTTP method benar
* middleware benar
* authentication benar
* endpoint sesuai `app-info.md`
* route parameter benar
* route naming konsisten

Gunakan:

```bash
php artisan route:list
```

untuk memeriksa hasil akhirnya.

---

# API DOCUMENTATION AUDIT

Setelah API selesai, lakukan pengecekan:

```text
app-info.md
       ↓
API Endpoint
       ↓
Controller
       ↓
Request Validation
       ↓
Response
       ↓
API Documentation
```

Pastikan dokumentasi menggambarkan implementation yang sebenarnya.

Jangan membuat dokumentasi endpoint yang sebenarnya tidak ada.

---

# IMPORTANT: JANGAN MERUSAK PROJECT EXISTING

Project Laravel sudah terinstall dan mungkin sudah memiliki functionality.

Sebelum mengubah file:

* baca file existing
* pahami implementation
* pertahankan functionality yang sudah bekerja
* jangan overwrite file secara membabi buta
* jangan menghapus dependency tanpa alasan
* jangan menghapus route existing
* jangan menghapus controller existing
* jangan mengganti architecture existing tanpa alasan

Lakukan perubahan seminimal mungkin tetapi tetap menghasilkan API yang lengkap.

---

# OUTPUT YANG SAYA INGINKAN

Setelah selesai, berikan laporan:

## 1. API yang dibuat

Daftar seluruh endpoint:

```text
METHOD | ENDPOINT | AUTH | DESCRIPTION
```

## 2. File yang dibuat

Contoh:

```text
app/Http/Controllers/Api/...
app/Http/Requests/...
app/Http/Resources/...
app/Services/...
routes/api.php
...
```

## 3. File yang diubah

Jelaskan perubahan setiap file.

## 4. Authentication

Jelaskan:

* login mechanism
* token mechanism
* protected routes
* logout
* expiration/refresh jika ada

## 5. Database

Jelaskan model dan relationship yang digunakan berdasarkan `schema.sql`.

## 6. API Documentation

Jelaskan URL/menu admin untuk membuka API Documentation.

Contoh:

```text
/admin/api-documentation
```

Sesuaikan dengan admin system existing.

## 7. Testing

Tampilkan test yang dijalankan dan hasilnya.

Contoh:

```text
PHPUnit: PASS
Feature Tests: PASS
API Tests: PASS
```

## 8. Remaining Issues

Jika ada bagian `app-info.md` yang tidak dapat diimplementasikan karena informasi kurang atau conflict dengan project existing, jelaskan secara eksplisit.

---

# FINAL RULE

**Jangan mulai coding sebelum membaca dan memahami `app-info.md` dan `schema.sql`.**

Keduanya adalah specification utama.

Jangan membuat fitur berdasarkan asumsi.

Jika ada sesuatu yang tidak dijelaskan, gunakan architecture Laravel yang paling standar dan aman, tetapi dokumentasikan asumsi tersebut.

Prioritas:

```text
app-info.md
    ↓
schema.sql
    ↓
existing Laravel project
    ↓
Laravel best practices
```

Tujuan akhir adalah:

**Laravel backend API yang siap digunakan oleh mobile application + API Documentation yang tersedia di Admin.**
