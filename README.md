# RideSip

**RideSip** is a multi-platform ride-hailing and transportation platform consisting of a Laravel backend and three dedicated Flutter mobile applications for customers, drivers, and merchants.

The project is organized as a monorepo so that the backend, mobile applications, API documentation, branding guidelines, and CI/CD workflows can be maintained in a single repository.

---

## 🏗️ Project Architecture

```text
ridesipwithmanus/
│
├── app-customer/              # Flutter Customer App
├── app-driver/                # Flutter Driver App
├── app-merchant/              # Flutter Merchant App
│
├── .github/
│   └── workflows/
│       └── build-apk.yml      # Flutter APK CI/CD
│
├── api-build.md               # API / backend build documentation
│
├── app-flutter-customer.md    # Customer app specification
├── app-flutter-driver.md      # Driver app specification
├── app-flutter-merchant.md    # Merchant app specification
├── app-flutter-branding.md    # Mobile application branding
│
├── .env.example               # Environment configuration template
└── README.md
```

---

# 📱 Mobile Applications

RideSip contains three separate Flutter applications.

## 1. Customer App

Location:

```text
app-customer/
```

The Customer App is used by passengers/customers to access RideSip transportation services.

Main technology stack:

- Flutter
- Dart
- Flutter InAppWebView
- HTTP API
- SharedPreferences
- Flutter Map
- Geolocator
- LatLong2

The customer application includes location and map functionality.

---

## 2. Driver App

Location:

```text
app-driver/
```

The Driver App is designed for RideSip drivers.

Main technology stack:

- Flutter
- Dart
- Flutter InAppWebView
- HTTP API
- SharedPreferences

---

## 3. Merchant App

Location:

```text
app-merchant/
```

The Merchant App is designed for merchants/business partners operating within the RideSip ecosystem.

Main technology stack:

- Flutter
- Dart
- Flutter InAppWebView
- HTTP API
- SharedPreferences

---

# 🔗 Application Communication

The Flutter applications communicate with the RideSip backend through HTTP/API services.

The mobile applications also use WebView functionality for web-based application components.

The general architecture is:

```text
                 ┌──────────────────┐
                 │   RideSip Backend  │
                 │     Laravel      │
                 └────────┬─────────┘
                          │
                     HTTP / API
                          │
          ┌───────────────┼───────────────┐
          │               │               │
          ▼               ▼               ▼
   ┌────────────┐  ┌────────────┐  ┌────────────┐
   │  Customer  │  │   Driver   │  │  Merchant  │
   │    App     │  │    App     │  │    App     │
   └────────────┘  └────────────┘  └────────────┘
        Flutter         Flutter         Flutter
```

---

# 🧰 Technology Stack

## Backend

- Laravel
- PHP
- REST API
- Database
- Authentication
- Server-side application logic

## Mobile

- Flutter
- Dart
- Android
- Flutter InAppWebView
- HTTP
- SharedPreferences

## Customer Location Services

- Flutter Map
- Geolocator
- LatLong2

## CI/CD

- GitHub Actions
- Flutter Stable
- Java 17
- Gradle
- Android APK Release Build

---

# ⚙️ Flutter Requirements

The current mobile applications use:

```text
Dart SDK: >=3.3.0 <4.0.0
Flutter: 3.32.x stable
Java: 17
```

It is recommended to use the same Flutter version locally as the CI workflow to minimize differences between local builds and GitHub Actions builds.

---

# 🚀 Running the Flutter Applications

Each application is an independent Flutter project.

## Customer

```bash
cd app-customer
flutter pub get
flutter run
```

## Driver

```bash
cd app-driver
flutter pub get
flutter run
```

## Merchant

```bash
cd app-merchant
flutter pub get
flutter run
```

---

# 🧹 Clean Build

If Flutter or Gradle behaves unexpectedly:

```bash
flutter clean
flutter pub get
flutter run
```

For a release APK:

```bash
flutter clean
flutter pub get
flutter build apk --release
```

---

# 📦 APK Output

After a successful release build:

```text
build/app/outputs/flutter-apk/app-release.apk
```

For example:

```text
app-customer/build/app/outputs/flutter-apk/app-release.apk
app-driver/build/app/outputs/flutter-apk/app-release.apk
app-merchant/build/app/outputs/flutter-apk/app-release.apk
```

---

# 🤖 GitHub Actions

The repository automatically builds all three Flutter applications through GitHub Actions.

Workflow:

```text
.github/workflows/build-apk.yml
```

The workflow builds:

```text
Customer APK
Driver APK
Merchant APK
```

The three applications are built in parallel using a GitHub Actions matrix.

## Build Flow

```text
Git Push
   │
   ▼
GitHub Actions
   │
   ├── Customer
   │     ├── Flutter Clean
   │     ├── Pub Get
   │     └── Build Release APK
   │
   ├── Driver
   │     ├── Flutter Clean
   │     ├── Pub Get
   │     └── Build Release APK
   │
   └── Merchant
         ├── Flutter Clean
         ├── Pub Get
         └── Build Release APK
```

The resulting APK files are uploaded as GitHub Actions artifacts.

---

# 🔄 Development Workflow

Recommended development flow:

```text
1. Pull latest main
        │
        ▼
2. Select application
        │
        ├── Customer
        ├── Driver
        └── Merchant
        │
        ▼
3. Install dependencies
        │
        ▼
4. Develop / modify code
        │
        ▼
5. Test locally
        │
        ▼
6. flutter analyze
        │
        ▼
7. flutter build apk --release
        │
        ▼
8. Commit changes
        │
        ▼
9. Push to GitHub
        │
        ▼
10. GitHub Actions
```

---

# 🧪 Recommended Flutter Checks

Before pushing changes:

```bash
flutter pub get
flutter analyze
flutter test
flutter build apk --release
```

When modifying only one application, test that application first.

For example:

```bash
cd app-driver

flutter pub get
flutter analyze
flutter test
flutter build apk --release
```

---

# 📚 Project Documentation

Additional project documentation is available in the repository.

### Customer Application

See:

```text
app-flutter-customer.md
```

Contains the functional and technical specification for the Customer App.

### Driver Application

See:

```text
app-flutter-driver.md
```

Contains the functional and technical specification for the Driver App.

### Merchant Application

See:

```text
app-flutter-merchant.md
```

Contains the functional and technical specification for the Merchant App.

### Branding

See:

```text
app-flutter-branding.md
```

Contains branding, visual identity, and application design requirements.

### API / Build Documentation

See:

```text
api-build.md
```

---

# 🎨 Branding

The three mobile applications should follow the common RideSip branding system.

Branding-related decisions should be documented in:

```text
app-flutter-branding.md
```

When modifying application UI, avoid introducing colors, typography, icons, or visual patterns that conflict with the established RideSip branding guidelines.

---

# 🔐 Environment Configuration

Environment configuration should never contain production secrets in Git.

Use:

```text
.env.example
```

as the template for required environment variables.

Never commit:

```text
.env
```

or any other file containing production credentials, API secrets, private keys, passwords, or tokens.

---

# 📁 Flutter Code Organization

The Flutter applications currently use a relatively centralized application structure.

As the applications grow, new functionality should preferably be organized into separate files/directories rather than continuously expanding `main.dart`.

A recommended future structure is:

```text
lib/
├── main.dart
│
├── core/
│   ├── config/
│   ├── constants/
│   ├── network/
│   ├── storage/
│   └── utils/
│
├── models/
│
├── services/
│
├── screens/
│
├── widgets/
│
└── features/
    ├── authentication/
    ├── profile/
    ├── rides/
    ├── maps/
    └── notifications/
```

This structure is recommended for future development and does not require an immediate rewrite of the existing applications.

---

# 🛠️ Troubleshooting

## Flutter dependency problems

Run:

```bash
flutter clean
flutter pub get
```

Then retry the build.

## Gradle problems

From the relevant application:

```bash
cd app-customer/android
./gradlew --stop
```

Then return to the Flutter project:

```bash
cd ..
flutter clean
flutter pub get
flutter build apk --release
```

## WebView problems

When debugging WebView-related functionality, verify:

1. Android permissions
2. Internet connectivity
3. HTTPS/HTTP configuration
4. WebView initialization
5. JavaScript requirements
6. API endpoint availability
7. Android WebView compatibility

---

# 🚨 Production Safety

Before deploying a production build, verify:

- API endpoints
- Authentication
- Android application ID
- App version
- Signing configuration
- Production environment variables
- WebView URLs
- Location permissions
- Network permissions
- API security
- Release signing keys

Production credentials and signing keys must never be committed to the repository.

---

# 📌 Repository Principles

When contributing to this repository:

### 1. Keep the three applications independent

Changes to one application should not unintentionally modify another application.

### 2. Keep API contracts consistent

Changes to backend API responses should be checked against:

- Customer App
- Driver App
- Merchant App

### 3. Test the affected application

A successful GitHub build does not necessarily mean the application functionality is correct.

Build validation and functional validation are separate concerns.

### 4. Avoid unnecessary dependency changes

Before adding a Flutter package, verify that the existing Flutter SDK and Android configuration support it.

### 5. Keep secrets out of Git

Never commit credentials, tokens, private keys, or production environment files.

---

# 📋 Application Matrix

| Application | Directory | Platform | Main Purpose |
|---|---|---|---|
| Customer | `app-customer` | Flutter / Android | Customer ride experience |
| Driver | `app-driver` | Flutter / Android | Driver operations |
| Merchant | `app-merchant` | Flutter / Android | Merchant operations |

---

# 🏁 Quick Start

Clone the repository:

```bash
git clone https://github.com/your-org/ridesip.git
cd ridesipwithmanus
```

Choose an application:

```bash
cd app-customer
```

Install dependencies:

```bash
flutter pub get
```

Run:

```bash
flutter run
```

Build:

```bash
flutter build apk --release
```

---

# 📄 License

This project is proprietary software unless otherwise specified by the project owner.

Unauthorized copying, distribution, modification, or commercial use is not permitted without permission from the project owner.

---

## RideSip

**Customer. Driver. Merchant. One platform.**