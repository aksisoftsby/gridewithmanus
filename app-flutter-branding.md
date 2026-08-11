# ADDITIONAL REQUIREMENT — BRANDING & VISUAL CONSISTENCY

Flutter Customer App harus memiliki **visual identity yang konsisten dengan aplikasi Laravel existing**.

Jangan membuat warna, font, atau logo secara sembarangan.

Laravel existing adalah **sumber referensi utama untuk branding visual aplikasi**.

---

# PHASE — AUDIT LARAVEL UI & BRANDING

Sebelum membuat Flutter UI, baca dan analisis layout Laravel existing.

Cari dan periksa:

```text
resources/views/
resources/views/layouts/
resources/views/components/
resources/views/partials/
public/
```

Periksa juga:

```text
Blade templates
CSS
SCSS
Tailwind configuration
Bootstrap configuration
JavaScript UI configuration
```

Cari informasi mengenai:

* primary color
* secondary color
* accent color
* background color
* text color
* heading color
* button color
* border color
* card color
* font family
* font weight
* logo
* favicon
* application name
* brand identity

---

# CARI BRAND COLOR DARI LARAVEL

Jangan menebak warna aplikasi.

Cari warna yang benar-benar digunakan Laravel.

Periksa:

```text
CSS variables
Tailwind config
theme config
style files
Blade inline styles
button classes
navigation classes
header classes
logo area
```

Contoh jika ditemukan:

```css
:root {
    --primary: #123456;
    --secondary: #abcdef;
}
```

atau Tailwind:

```text
primary-500
primary-600
```

gunakan warna tersebut sebagai dasar Flutter Theme.

Jika terdapat beberapa variasi warna:

```text
primary-50
primary-100
primary-500
primary-600
primary-700
```

buat color palette Flutter yang sesuai.

Contoh konsep:

```dart
class AppColors {
  static const primary = Color(...);
  static const primaryDark = Color(...);
  static const primaryLight = Color(...);
  static const secondary = Color(...);
  static const background = Color(...);
}
```

Jangan hanya mengambil satu warna jika Laravel sudah memiliki palette yang lebih lengkap.

---

# CARI FONT DARI LARAVEL

Periksa font yang digunakan Laravel.

Cari:

```text
font-family
@font-face
Google Fonts
Tailwind font configuration
CSS imports
Blade layout
```

Contoh:

```css
font-family: 'Poppins';
```

atau:

```css
font-family: 'Inter';
```

Flutter harus menggunakan font yang sama atau font yang paling mendekati.

Jika font tersebut tersedia melalui package yang sesuai atau dapat digunakan secara legal, gunakan font tersebut.

Jika font custom tersedia di Laravel:

```text
public/fonts/
storage/
resources/
```

periksa apakah font tersebut dapat digunakan oleh Flutter.

Jika perlu memasukkan font ke Flutter:

```text
app-customer/assets/fonts/
```

dan daftarkan pada:

```text
pubspec.yaml
```

Jangan mengganti font branding hanya karena font default Flutter lebih mudah digunakan.

---

# LOGO APPLICATION

Cari logo aplikasi Laravel.

Periksa:

```text
public/
public/images/
public/assets/
resources/
resources/images/
resources/assets/
storage/
Blade templates
```

Cari file seperti:

```text
logo.png
logo.svg
logo.webp
logo.jpg
brand.svg
brand-logo.svg
favicon.ico
favicon.png
```

Periksa juga Blade template untuk mengetahui logo yang sebenarnya digunakan.

Contoh:

```blade
<img src="{{ asset('images/logo.svg') }}">
```

atau:

```blade
<img src="{{ asset('images/logo.png') }}">
```

atau:

```blade
<x-logo />
```

atau:

```blade
<img src="..." alt="APP NAME">
```

---

# JIKA TIDAK BISA MEMBACA GAMBAR

Jika environment AI tidak dapat membuka atau memahami file gambar logo:

**JANGAN membuat logo baru berdasarkan tebakan.**

Gunakan informasi dari Blade/HTML.

Cari:

```text
<img>
<svg>
alt=""
title=""
aria-label=""
class=""
id=""
```

Cari nama/tag logo yang digunakan.

Contoh:

```html
<img src="/images/my-app-logo.svg" alt="MyApp">
```

atau:

```html
<x-application-logo />
```

atau:

```html
<svg id="app-logo">
```

Gunakan nama/tag tersebut untuk mengidentifikasi logo aplikasi.

Jika logo berupa SVG yang bisa dibaca sebagai text/XML, baca isi SVG tersebut.

---

# BUAT LOGO.XML UNTUK ANDROID

Buat Android launcher/application logo yang sesuai dengan logo aplikasi Laravel.

Gunakan logo Laravel sebagai sumber utama.

Flutter project:

```text
app-customer/
```

Android resources:

```text
app-customer/android/app/src/main/res/
```

Buat asset/logo Android yang diperlukan, termasuk:

```text
mipmap-*/
drawable/
```

dan jika sesuai dengan struktur Android modern:

```text
mipmap-anydpi-v26/
```

Buat:

```text
logo.xml
```

jika memang sesuai dengan resource structure Android yang digunakan.

Namun perhatikan:

**Jangan mengarang format `logo.xml` yang tidak kompatibel dengan Android.**

Periksa terlebih dahulu bagaimana launcher icon/application icon Flutter Android existing didefinisikan.

Jika Android menggunakan adaptive icon:

```xml
<adaptive-icon>
    ...
</adaptive-icon>
```

gunakan struktur adaptive icon yang benar.

Jika membutuhkan:

```text
ic_launcher.xml
ic_launcher_round.xml
```

gunakan nama resource yang benar dan integrasikan dengan AndroidManifest/build configuration.

Tujuannya:

> Icon aplikasi Flutter harus secara visual berasal dari logo aplikasi Laravel.

---

# LOGO ASSET RULE

Jangan membuat logo dengan desain berbeda hanya karena Flutter membutuhkan ukuran berbeda.

Gunakan source logo Laravel.

Jika tersedia SVG:

```text
SVG → Android drawable/vector/adaptive icon
```

Jika hanya tersedia PNG:

```text
PNG → generate required Android densities
```

Jika logo memiliki background transparan, pertahankan transparansinya jika sesuai dengan branding.

Jika adaptive icon membutuhkan foreground/background terpisah, pisahkan dengan hati-hati berdasarkan logo asli.

Jangan mengubah bentuk logo.

---

# FLUTTER APP ICON

Pastikan logo yang digunakan juga menjadi:

```text
Flutter App Icon
Android Launcher Icon
```

Jika package seperti `flutter_launcher_icons` diperlukan, boleh digunakan **hanya jika memang membantu dan kompatibel**.

Setelah membuat icon:

```bash
flutter pub get
flutter analyze
flutter test
```

Kemudian:

```bash
flutter build apk --debug
```

pastikan Android build berhasil.

---

# SPLASH SCREEN

Splash screen harus menggunakan branding aplikasi Laravel.

Gunakan:

* logo aplikasi
* primary color
* background color
* font/brand identity jika diperlukan

Jangan membuat splash screen dengan branding default Flutter.

Jangan menggunakan:

```text
Flutter logo
Flutter blue
Default Flutter splash
```

sebagai branding aplikasi.

---

# FLUTTER THEME

Setelah audit Laravel selesai, buat:

```text
lib/app/theme/
```

Contoh:

```text
app_colors.dart
app_theme.dart
app_typography.dart
```

Theme Flutter harus berasal dari hasil audit Laravel.

Contoh:

```dart
ThemeData(
  useMaterial3: true,
  colorScheme: ColorScheme(...),
  textTheme: ...,
)
```

Semua halaman harus menggunakan theme ini.

Jangan hard-code warna seperti:

```dart
Colors.blue
Colors.red
Colors.green
```

di berbagai halaman jika warna tersebut sebenarnya merupakan bagian dari branding.

Gunakan:

```dart
Theme.of(context)
```

atau:

```dart
AppColors.primary
```

---

# UI CONSISTENCY

Flutter harus terasa sebagai bagian dari ekosistem aplikasi yang sama dengan Laravel.

Perhatikan:

### Color

```text
Laravel Primary
        ↓
Flutter Primary
```

### Font

```text
Laravel Font
        ↓
Flutter Font
```

### Logo

```text
Laravel Logo
        ↓
Flutter Logo
        ↓
Android Launcher Icon
```

### UI style

Perhatikan layout Laravel:

* border radius
* card style
* button style
* spacing
* shadows
* input style
* navigation style
* icon style

Gunakan sebagai referensi.

Namun jangan menyalin halaman web secara pixel-perfect.

Flutter harus tetap menggunakan UX mobile yang natural.

---

# BRAND AUDIT REPORT

Sebelum membuat UI utama, identifikasi hasil audit:

```text
APPLICATION NAME:
...

PRIMARY COLOR:
...

SECONDARY COLOR:
...

ACCENT COLOR:
...

BACKGROUND:
...

TEXT COLOR:
...

FONT:
...

LOGO FILE:
...

LOGO BLADE TAG:
...

FAVICON:
...

BORDER RADIUS STYLE:
...

BUTTON STYLE:
...
```

Jika suatu informasi tidak ditemukan:

```text
Not found
```

Jangan mengarang seolah-olah ditemukan.

---

# BRANDING PRIORITY

Jika terdapat perbedaan antara:

```text
Flutter default
vs
Laravel existing branding
```

prioritaskan:

```text
Laravel branding
```

Jika terdapat perbedaan antara:

```text
app-info.md
vs
Laravel visual implementation
```

gunakan:

```text
app-info.md → functional specification
Laravel UI → visual branding reference
```

---

# FINAL BRANDING CHECK

Sebelum final build, periksa:

```text
✓ Application name benar
✓ Primary color sesuai Laravel
✓ Secondary color sesuai Laravel
✓ Font sesuai Laravel
✓ Logo sesuai Laravel
✓ App icon sesuai Laravel
✓ Splash screen sesuai Laravel
✓ Tidak ada Flutter logo
✓ Tidak ada default Flutter branding
✓ Tidak ada warna random
✓ Tidak ada font random
✓ Android launcher icon berhasil
✓ flutter analyze berhasil
✓ flutter test berhasil
✓ flutter build apk --debug berhasil
```

---

# IMPORTANT

Jangan mengganti logo Laravel dengan logo hasil AI.

Jangan membuat logo baru kecuali:

1. logo asli benar-benar tidak tersedia
2. Blade/HTML juga tidak dapat mengidentifikasi logo
3. dan pembuatan logo baru memang diperlukan

Jika logo asli tersedia, **gunakan logo asli**.

Jika AI tidak dapat membaca gambar:

```text
READ IMAGE PATH
        ↓
READ BLADE HTML
        ↓
IDENTIFY LOGO TAG
        ↓
IDENTIFY ASSET FILE
        ↓
READ SVG/XML IF AVAILABLE
        ↓
USE ORIGINAL LOGO
```

Bukan:

```text
Cannot read image
↓
Invent new logo
```

---

# UPDATED DEVELOPMENT FLOW

Flow lengkap menjadi:

```text
READ app-info.md
        ↓
READ schema.sql
        ↓
AUDIT LARAVEL API
        ↓
AUDIT LARAVEL LAYOUT
        ↓
AUDIT COLORS
        ↓
AUDIT FONTS
        ↓
AUDIT LOGO
        ↓
AUDIT BLADE LOGO TAG
        ↓
AUDIT ASSETS
        ↓
CREATE app-customer/
        ↓
CREATE FLUTTER PROJECT
        ↓
BASELINE TEST
        ↓
CREATE BRAND THEME
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
FINAL API AUDIT
        ↓
FINAL BRANDING AUDIT
        ↓
FINAL UI AUDIT
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

**Mulai dari audit terlebih dahulu. Jangan langsung coding UI.**
