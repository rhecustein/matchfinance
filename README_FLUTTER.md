# MatchFinance Flutter Desktop - Getting Started Guide

<div align="center">

![Flutter](https://img.shields.io/badge/Flutter-3.24+-02569B?logo=flutter)
![Dart](https://img.shields.io/badge/Dart-3.5+-0175C2?logo=dart)
![Platform](https://img.shields.io/badge/Platform-Windows%20%7C%20macOS%20%7C%20Linux-lightgrey)
![License](https://img.shields.io/badge/License-Proprietary-red)

**Panduan Lengkap Migrasi dari Laravel ke Flutter Desktop**

[Quick Start](#quick-start) • [Architecture](#architecture) • [Features](#features) • [Implementation](#implementation) • [FAQ](#faq)

</div>

---

## 📋 Table of Contents

1. [Introduction](#introduction)
2. [Prerequisites](#prerequisites)
3. [Project Setup](#project-setup)
4. [Architecture Overview](#architecture-overview)
5. [Database Design](#database-design)
6. [Feature Implementation](#feature-implementation)
7. [Testing Strategy](#testing-strategy)
8. [Deployment](#deployment)
9. [Troubleshooting](#troubleshooting)
10. [Resources](#resources)

---

## 🎯 Introduction

MatchFinance Flutter Desktop adalah reimplementasi dari aplikasi web Laravel MatchFinance sebagai **offline-first desktop application** menggunakan Flutter framework.

### Why Flutter Desktop?

- ✅ **True Native Performance**: Compiled ke native code (C++ runtime)
- ✅ **Single Codebase**: Satu kode untuk Windows, macOS, dan Linux
- ✅ **Offline First**: Tidak memerlukan koneksi internet untuk operasi inti
- ✅ **Beautiful UI**: Material Design 3 dengan animasi smooth
- ✅ **SQLite Local DB**: Database embedded, no server required
- ✅ **Fast Development**: Hot reload, rich widget library
- ✅ **Easy Distribution**: Single executable per platform

### Key Differences from Web Version

| Feature | Web (Laravel) | Desktop (Flutter) |
|---------|---------------|-------------------|
| **Database** | MySQL (server) | SQLite (local) |
| **Authentication** | Session + OAuth | Local authentication |
| **OCR Processing** | External API | Tesseract (offline) |
| **Multi-tenancy** | Company_id scoping | Multi-company database |
| **Queue Jobs** | Laravel Queue | Dart Isolates |
| **File Storage** | Server storage | Local file system |
| **Updates** | Instant (web) | App update mechanism |
| **Internet Required** | Yes | No (offline-first) |

---

## 🔧 Prerequisites

### System Requirements

#### Development Machine

- **OS**: Windows 10+, macOS 12+, atau Linux (Ubuntu 20.04+)
- **RAM**: Minimum 8GB (recommended 16GB)
- **Storage**: 10GB free space
- **CPU**: 64-bit processor

#### Software Requirements

1. **Flutter SDK 3.24 atau lebih tinggi**
   ```bash
   # Install Flutter
   # Windows: https://docs.flutter.dev/get-started/install/windows
   # macOS: https://docs.flutter.dev/get-started/install/macos
   # Linux: https://docs.flutter.dev/get-started/install/linux

   # Verify installation
   flutter --version
   flutter doctor
   ```

2. **Dart SDK 3.5+ (included with Flutter)**
   ```bash
   dart --version
   ```

3. **IDE dengan Flutter Plugin**
   - **VS Code** + Flutter extension (recommended)
   - **Android Studio** / IntelliJ IDEA + Flutter plugin

4. **Platform-specific Tools**

   **Windows**:
   ```powershell
   # Visual Studio 2022 with Desktop C++ workload
   # Download: https://visualstudio.microsoft.com/downloads/
   ```

   **macOS**:
   ```bash
   # Xcode 14+
   xcode-select --install

   # CocoaPods
   sudo gem install cocoapods
   ```

   **Linux**:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install clang cmake ninja-build pkg-config libgtk-3-dev

   # Fedora
   sudo dnf install clang cmake ninja-build gtk3-devel
   ```

5. **Git**
   ```bash
   git --version
   ```

### Knowledge Requirements

- **Dart Language**: Basic to intermediate level
- **Flutter Widgets**: StatelessWidget, StatefulWidget, Hooks
- **State Management**: Riverpod atau Bloc (pilih salah satu)
- **SQLite/SQL**: Basic queries
- **Async Programming**: Future, Stream, async/await

### Recommended Learning Resources

**Before Starting**:
1. [Dart Language Tour](https://dart.dev/guides/language/language-tour) (2-3 hari)
2. [Flutter Codelabs](https://docs.flutter.dev/codelabs) (1 minggu)
3. [Riverpod Documentation](https://riverpod.dev/) (2-3 hari)
4. [Drift Documentation](https://drift.simonbinder.eu/) (2 hari)

---

## 🚀 Project Setup

### Step 1: Create Flutter Project

```bash
# Create project with desktop platforms
flutter create --org com.matchfinance \
               --platforms windows,macos,linux \
               matchfinance_desktop

cd matchfinance_desktop

# Test run (will open on your platform)
flutter run -d windows  # or macos, linux
```

### Step 2: Project Structure Setup

Create the following folder structure:

```
matchfinance_desktop/
├── lib/
│   ├── main.dart
│   ├── app.dart
│   │
│   ├── core/                          # Core utilities
│   │   ├── config/
│   │   │   ├── app_config.dart
│   │   │   └── theme_config.dart
│   │   ├── constants/
│   │   │   ├── app_constants.dart
│   │   │   └── db_constants.dart
│   │   ├── errors/
│   │   │   ├── exceptions.dart
│   │   │   └── failures.dart
│   │   ├── network/
│   │   │   └── network_info.dart
│   │   └── utils/
│   │       ├── date_utils.dart
│   │       ├── string_utils.dart
│   │       └── validators.dart
│   │
│   ├── data/                          # Data layer
│   │   ├── database/
│   │   │   ├── app_database.dart      # Drift database
│   │   │   ├── app_database.g.dart
│   │   │   └── tables/
│   │   │       ├── companies.dart
│   │   │       ├── users.dart
│   │   │       ├── banks.dart
│   │   │       ├── transactions.dart
│   │   │       └── ... (all tables)
│   │   │
│   │   ├── models/
│   │   │   ├── company.dart
│   │   │   ├── user.dart
│   │   │   ├── transaction.dart
│   │   │   └── ... (all models)
│   │   │
│   │   ├── repositories/
│   │   │   ├── auth_repository.dart
│   │   │   ├── company_repository.dart
│   │   │   ├── transaction_repository.dart
│   │   │   └── ...
│   │   │
│   │   └── datasources/
│   │       ├── local/
│   │       │   ├── auth_local_datasource.dart
│   │       │   └── ...
│   │       └── remote/ (optional untuk sync)
│   │
│   ├── domain/                        # Business logic
│   │   ├── entities/
│   │   │   ├── company.dart
│   │   │   ├── transaction.dart
│   │   │   └── ...
│   │   │
│   │   ├── repositories/ (abstract)
│   │   │   ├── auth_repository.dart
│   │   │   └── ...
│   │   │
│   │   └── usecases/
│   │       ├── auth/
│   │       │   ├── login_usecase.dart
│   │       │   ├── logout_usecase.dart
│   │       │   └── ...
│   │       ├── transactions/
│   │       │   ├── match_transaction_usecase.dart
│   │       │   ├── verify_transaction_usecase.dart
│   │       │   └── ...
│   │       └── ...
│   │
│   ├── services/                      # Business services
│   │   ├── ocr/
│   │   │   ├── ocr_service.dart
│   │   │   └── tesseract_ocr_impl.dart
│   │   ├── matching/
│   │   │   ├── transaction_matching_service.dart
│   │   │   ├── account_matching_service.dart
│   │   │   └── keyword_matcher.dart
│   │   ├── parsers/
│   │   │   ├── base_bank_parser.dart
│   │   │   ├── bca_parser.dart
│   │   │   ├── mandiri_parser.dart
│   │   │   └── ... (6 bank parsers)
│   │   ├── export/
│   │   │   ├── excel_export_service.dart
│   │   │   └── pdf_export_service.dart
│   │   └── backup/
│   │       └── backup_service.dart
│   │
│   ├── presentation/                  # UI layer
│   │   ├── providers/                 # Riverpod providers
│   │   │   ├── auth_provider.dart
│   │   │   ├── company_provider.dart
│   │   │   └── ...
│   │   │
│   │   ├── screens/
│   │   │   ├── auth/
│   │   │   │   ├── login_screen.dart
│   │   │   │   └── ...
│   │   │   ├── dashboard/
│   │   │   │   └── dashboard_screen.dart
│   │   │   ├── statements/
│   │   │   │   ├── statements_list_screen.dart
│   │   │   │   ├── statement_detail_screen.dart
│   │   │   │   └── upload_statement_screen.dart
│   │   │   ├── transactions/
│   │   │   ├── master_data/
│   │   │   └── reports/
│   │   │
│   │   ├── widgets/
│   │   │   ├── common/
│   │   │   │   ├── app_button.dart
│   │   │   │   ├── app_text_field.dart
│   │   │   │   ├── loading_indicator.dart
│   │   │   │   └── ...
│   │   │   ├── transaction/
│   │   │   │   ├── transaction_card.dart
│   │   │   │   └── ...
│   │   │   └── ...
│   │   │
│   │   └── routes/
│   │       ├── app_router.dart
│   │       └── route_names.dart
│   │
│   └── generated/                     # Generated files
│       └── l10n/                      # Localization (optional)
│
├── test/
│   ├── unit/
│   ├── widget/
│   └── integration/
│
├── assets/
│   ├── images/
│   ├── fonts/
│   └── data/                          # Seed data
│
├── windows/
├── macos/
├── linux/
├── pubspec.yaml
├── analysis_options.yaml
└── README.md
```

**Create Structure**:
```bash
# Run this script to create all folders
mkdir -p lib/{core/{config,constants,errors,network,utils},data/{database/tables,models,repositories,datasources/{local,remote}},domain/{entities,repositories,usecases/{auth,transactions}},services/{ocr,matching,parsers,export,backup},presentation/{providers,screens/{auth,dashboard,statements,transactions,master_data,reports},widgets/{common,transaction},routes}}
mkdir -p test/{unit,widget,integration}
mkdir -p assets/{images,fonts,data}
```

### Step 3: Dependencies Setup

Edit `pubspec.yaml`:

```yaml
name: matchfinance_desktop
description: MatchFinance Desktop Application - Offline Bank Statement Processing
publish_to: 'none'

version: 1.0.0+1

environment:
  sdk: '>=3.5.0 <4.0.0'

dependencies:
  flutter:
    sdk: flutter

  # ========== State Management ==========
  flutter_riverpod: ^2.5.1
  riverpod_annotation: ^2.3.5

  # ========== Database ==========
  drift: ^2.20.0
  drift_flutter: ^0.2.0
  sqlite3_flutter_libs: ^0.5.24
  path_provider: ^2.1.4
  path: ^1.9.0

  # ========== Security ==========
  encrypt: ^5.0.3              # For password hashing
  crypto: ^3.0.3               # Cryptographic functions
  bcrypt: ^1.1.3               # Password hashing

  # ========== PDF Processing ==========
  file_picker: ^8.1.2          # File selection dialog
  syncfusion_flutter_pdf: ^27.2.3  # PDF parsing
  native_pdf_view: ^6.0.0      # PDF preview (optional)

  # ========== OCR (Choose one) ==========
  # Option 1: Tesseract (Recommended for offline)
  # tesseract_ocr_windows: ^0.0.5  # Windows only
  # For macOS/Linux: Use FFI bindings (see OCR implementation guide)

  # Option 2: Google ML Kit (requires model download, but easier)
  google_mlkit_text_recognition: ^0.13.1

  # ========== Export ==========
  excel: ^4.0.6                # Excel export
  pdf: ^3.11.1                 # PDF generation
  printing: ^5.13.2            # Print & PDF export

  # ========== Charts ==========
  fl_chart: ^0.70.0            # Charts for dashboard
  syncfusion_flutter_charts: ^27.2.3

  # ========== UI ==========
  gap: ^3.0.1                  # Spacing widget
  flutter_animate: ^4.5.0      # Animations
  dropdown_search: ^6.0.1      # Advanced dropdowns
  data_table_2: ^2.5.15        # Enhanced data tables
  badges: ^3.1.2               # Badge widgets
  shimmer: ^3.0.0              # Loading shimmer effect

  # ========== Utilities ==========
  uuid: ^4.5.0                 # UUID generation
  intl: ^0.19.0                # Internationalization & date formatting
  collection: ^1.18.0          # Collection utilities
  equatable: ^2.0.5            # Value equality
  dartz: ^0.10.1               # Functional programming (Either, Option)
  freezed_annotation: ^2.4.4   # Immutable data classes

  # ========== Routing ==========
  go_router: ^14.6.1           # Routing

  # ========== Logging ==========
  logger: ^2.4.0               # Logging

  # ========== String Processing ==========
  string_similarity: ^2.0.0    # Levenshtein distance

  # ========== HTTP (Optional, for cloud sync) ==========
  dio: ^5.7.0                  # HTTP client
  connectivity_plus: ^6.0.5    # Network connectivity check

  # ========== Storage ==========
  shared_preferences: ^2.3.2   # Simple key-value storage
  hive: ^2.2.3                 # Fast key-value database
  hive_flutter: ^1.1.0

dev_dependencies:
  flutter_test:
    sdk: flutter

  # ========== Code Generation ==========
  build_runner: ^2.4.13
  drift_dev: ^2.20.0
  riverpod_generator: ^2.4.3
  freezed: ^2.5.7
  json_serializable: ^6.8.0

  # ========== Linting ==========
  flutter_lints: ^5.0.0

  # ========== Testing ==========
  mockito: ^5.4.4
  faker: ^2.2.0
  integration_test:
    sdk: flutter

  # ========== Icons ==========
  flutter_launcher_icons: ^0.13.1

flutter:
  uses-material-design: true

  # Assets
  assets:
    - assets/images/
    - assets/data/

  # Fonts (optional custom fonts)
  # fonts:
  #   - family: Roboto
  #     fonts:
  #       - asset: assets/fonts/Roboto-Regular.ttf
  #       - asset: assets/fonts/Roboto-Bold.ttf
  #         weight: 700

# Platform-specific configuration
flutter_launcher_icons:
  android: false
  ios: false
  windows:
    generate: true
    image_path: "assets/images/icon.png"
  macos:
    generate: true
    image_path: "assets/images/icon.png"
  linux:
    generate: true
    image_path: "assets/images/icon.png"
```

**Install Dependencies**:
```bash
flutter pub get
flutter pub run build_runner build --delete-conflicting-outputs
```

### Step 4: Configure Platform-Specific Settings

#### Windows Configuration

Edit `windows/runner/main.cpp` untuk set window size:

```cpp
// Set initial window size
Win32Window::Size size(1280, 800);
```

Edit `windows/runner/Runner.rc` untuk app metadata.

#### macOS Configuration

Edit `macos/Runner/Configs/AppInfo.xcconfig`:

```
// Bundle identifier
PRODUCT_BUNDLE_IDENTIFIER = com.matchfinance.desktop

// App name
PRODUCT_NAME = MatchFinance

// Minimum macOS version
MACOSX_DEPLOYMENT_TARGET = 12.0
```

Enable file access in `macos/Runner/DebugProfile.entitlements`:

```xml
<key>com.apple.security.files.user-selected.read-write</key>
<true/>
```

#### Linux Configuration

Edit `linux/my_application.cc` untuk window size:

```cpp
gtk_window_set_default_size(window, 1280, 800);
```

---

## 🏗️ Architecture Overview

### Clean Architecture Layers

```
┌─────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                    │
│  ┌────────────┐  ┌──────────┐  ┌──────────────────┐    │
│  │  Screens   │  │ Widgets  │  │  Riverpod        │    │
│  │            │  │          │  │  Providers       │    │
│  └────────────┘  └──────────┘  └──────────────────┘    │
└───────────────────────────┬─────────────────────────────┘
                            │
                            │ Calls
                            ▼
┌─────────────────────────────────────────────────────────┐
│                     DOMAIN LAYER                         │
│  ┌────────────┐  ┌──────────────┐  ┌────────────────┐  │
│  │  Entities  │  │   UseCases   │  │  Repositories  │  │
│  │  (Models)  │  │ (Business    │  │  (Abstract)    │  │
│  │            │  │   Logic)     │  │                │  │
│  └────────────┘  └──────────────┘  └────────────────┘  │
└───────────────────────────┬─────────────────────────────┘
                            │
                            │ Implements
                            ▼
┌─────────────────────────────────────────────────────────┐
│                      DATA LAYER                          │
│  ┌─────────────────┐  ┌────────────┐  ┌──────────────┐ │
│  │  Repositories   │  │ DataSources│  │   Database   │ │
│  │  (Implementation│  │  (Local)   │  │   (Drift)    │ │
│  │      )          │  │            │  │              │ │
│  └─────────────────┘  └────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────┘
                            │
                            │ Uses
                            ▼
┌─────────────────────────────────────────────────────────┐
│                    SERVICES LAYER                        │
│  ┌────────────┐  ┌──────────┐  ┌─────────────────────┐ │
│  │    OCR     │  │ Matching │  │   Bank Parsers      │ │
│  │  Service   │  │ Service  │  │  (BCA, Mandiri...)  │ │
│  └────────────┘  └──────────┘  └─────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Data Flow Example: Upload & Process Bank Statement

```
User Action
    │
    ├─> 1. Pick PDF file (file_picker)
    │
    ├─> 2. Create BankStatement record in DB
    │
    ├─> 3. Background Task (Isolate)
    │      │
    │      ├─> 3a. Convert PDF → Images
    │      │
    │      ├─> 3b. Run OCR (Tesseract)
    │      │       └─> Extract text blocks
    │      │
    │      ├─> 3c. Parse with BankParser
    │      │       ├─> Identify bank type
    │      │       ├─> Extract transactions
    │      │       └─> Parse dates, amounts
    │      │
    │      ├─> 3d. Save transactions to DB
    │      │
    │      ├─> 3e. Run TransactionMatching
    │      │       ├─> Load keywords from DB
    │      │       ├─> Match each transaction
    │      │       ├─> Calculate confidence
    │      │       └─> Update transaction categories
    │      │
    │      └─> 3f. Run AccountMatching
    │              ├─> Load account keywords
    │              └─> Match to GL accounts
    │
    └─> 4. Update UI with progress
            └─> Show completion notification
```

### State Management Strategy (Riverpod)

**Provider Types**:

```dart
// 1. Provider - For read-only values
final databaseProvider = Provider<AppDatabase>((ref) {
  return AppDatabase();
});

// 2. StateProvider - For simple state
final selectedCompanyIdProvider = StateProvider<int?>((ref) => null);

// 3. FutureProvider - For async data
final companiesProvider = FutureProvider<List<Company>>((ref) async {
  final db = ref.watch(databaseProvider);
  return db.getAllCompanies();
});

// 4. StreamProvider - For real-time updates
final transactionsStreamProvider = StreamProvider<List<Transaction>>((ref) {
  final db = ref.watch(databaseProvider);
  final statementId = ref.watch(selectedStatementIdProvider);
  return db.watchTransactions(statementId);
});

// 5. StateNotifierProvider - For complex state
final authNotifierProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(ref.watch(authRepositoryProvider));
});
```

---

## 💾 Database Design

### Drift Setup

**1. Create Database Class** (`lib/data/database/app_database.dart`):

```dart
import 'dart:io';
import 'package:drift/drift.dart';
import 'package:drift_flutter/drift_flutter.dart';
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as p;

// Import all table definitions
import 'tables/companies.dart';
import 'tables/users.dart';
import 'tables/banks.dart';
import 'tables/types.dart';
import 'tables/categories.dart';
import 'tables/sub_categories.dart';
import 'tables/keywords.dart';
import 'tables/bank_statements.dart';
import 'tables/statement_transactions.dart';
import 'tables/accounts.dart';
import 'tables/account_keywords.dart';
import 'tables/matching_logs.dart';
import 'tables/account_matching_logs.dart';
import 'tables/background_tasks.dart';
import 'tables/app_settings.dart';
import 'tables/activity_logs.dart';

part 'app_database.g.dart';

@DriftDatabase(tables: [
  Companies,
  Users,
  Banks,
  Types,
  Categories,
  SubCategories,
  Keywords,
  BankStatements,
  StatementTransactions,
  Accounts,
  AccountKeywords,
  MatchingLogs,
  AccountMatchingLogs,
  BackgroundTasks,
  AppSettings,
  ActivityLogs,
])
class AppDatabase extends _$AppDatabase {
  AppDatabase() : super(_openConnection());

  @override
  int get schemaVersion => 1;

  @override
  MigrationStrategy get migration => MigrationStrategy(
    onCreate: (Migrator m) async {
      await m.createAll();

      // Insert default data
      await _insertDefaultData();
    },
    onUpgrade: (Migrator m, int from, int to) async {
      // Handle schema upgrades
      if (from < 2) {
        // Migration logic for version 2
      }
    },
  );

  // Helper method to insert default data
  Future<void> _insertDefaultData() async {
    // Insert default banks
    await batch((batch) {
      batch.insertAll(banks, [
        BanksCompanion.insert(
          uuid: 'bca-default',
          companyId: 0, // System default
          bankName: 'BCA',
          code: 'BCA',
          isActive: true,
        ),
        BanksCompanion.insert(
          uuid: 'mandiri-default',
          companyId: 0,
          bankName: 'Mandiri',
          code: 'MANDIRI',
          isActive: true,
        ),
        BanksCompanion.insert(
          uuid: 'bni-default',
          companyId: 0,
          bankName: 'BNI',
          code: 'BNI',
          isActive: true,
        ),
        BanksCompanion.insert(
          uuid: 'bri-default',
          companyId: 0,
          bankName: 'BRI',
          code: 'BRI',
          isActive: true,
        ),
        BanksCompanion.insert(
          uuid: 'btn-default',
          companyId: 0,
          bankName: 'BTN',
          code: 'BTN',
          isActive: true,
        ),
        BanksCompanion.insert(
          uuid: 'cimb-default',
          companyId: 0,
          bankName: 'CIMB Niaga',
          code: 'CIMB',
          isActive: true,
        ),
      ]);
    });
  }
}

// Connection configuration
LazyDatabase _openConnection() {
  return LazyDatabase(() async {
    final dbFolder = await getApplicationDocumentsDirectory();
    final file = File(p.join(dbFolder.path, 'matchfinance.db'));

    return driftDatabase(
      name: file.path,
      // For production, enable encryption:
      // native: () => NativeDatabase.createInBackground(
      //   file,
      //   // setup: (db) {
      //   //   db.execute("PRAGMA key = 'your-encryption-key';");
      //   // },
      // ),
    );
  });
}
```

**2. Example Table Definition** (`lib/data/database/tables/companies.dart`):

```dart
import 'package:drift/drift.dart';

@DataClassName('Company')
class Companies extends Table {
  // Primary key
  IntColumn get id => integer().autoIncrement()();

  // UUID for sync
  TextColumn get uuid => text().unique()();

  // Basic info
  TextColumn get name => text().withLength(min: 1, max: 255)();
  TextColumn get slug => text().unique().nullable()();

  // Branding
  TextColumn get logoPath => text().nullable()();

  // Settings stored as JSON
  TextColumn get settings => text().nullable()();

  // Status: active, suspended, cancelled
  TextColumn get status => text().withDefault(const Constant('active'))();

  // Timestamps (stored as milliseconds since epoch)
  IntColumn get createdAt => integer()();
  IntColumn get updatedAt => integer()();
  IntColumn get deletedAt => integer().nullable()();

  @override
  List<Set<Column>> get uniqueKeys => [
    {slug},
  ];
}
```

See **[IMPLEMENTATION_GUIDE.md](./IMPLEMENTATION_GUIDE.md)** for complete table definitions.

---

## ✨ Feature Implementation

This section provides overview. **Detailed implementation** ada di dokumen terpisah:

### [📘 Complete Implementation Guide](./IMPLEMENTATION_GUIDE.md)
Berisi step-by-step implementation untuk setiap fitur:
- Authentication & User Management
- Master Data Management
- PDF Upload & OCR Processing
- Transaction Matching Algorithm
- Account Mapping
- Reports & Export
- Backup & Restore

### Quick Feature Checklist

- [ ] **Phase 1: Foundation**
  - [ ] Database setup (Drift)
  - [ ] Authentication system
  - [ ] Company management
  - [ ] Master data CRUD

- [ ] **Phase 2: Core Processing**
  - [ ] PDF upload & preview
  - [ ] OCR integration (Tesseract/ML Kit)
  - [ ] Bank parsers (6 banks)
  - [ ] Transaction matching
  - [ ] Account mapping

- [ ] **Phase 3: UI & Reports**
  - [ ] Dashboard with charts
  - [ ] Transaction list & detail
  - [ ] Verification workflow
  - [ ] Report generation
  - [ ] Excel/PDF export

- [ ] **Phase 4: Advanced**
  - [ ] Learning system
  - [ ] Keyword suggestions
  - [ ] Backup/restore
  - [ ] Data import/export

---

## 🧪 Testing Strategy

### Unit Tests

**Test Coverage Target**: >80%

```dart
// test/unit/services/matching/transaction_matching_service_test.dart
import 'package:flutter_test/flutter_test.dart';
import 'package:matchfinance_desktop/services/matching/transaction_matching_service.dart';

void main() {
  late TransactionMatchingService service;

  setUp(() {
    service = TransactionMatchingService();
  });

  group('TransactionMatchingService', () {
    test('should match exact keyword', () async {
      final keywords = [
        Keyword(
          id: 1,
          keyword: 'INDOMARET',
          matchType: MatchType.exact,
          priority: 10,
        ),
      ];

      final result = await service.matchTransaction(
        'INDOMARET QR',
        keywords,
      );

      expect(result.isMatched, true);
      expect(result.confidence, 100);
      expect(result.matchedKeyword!.id, 1);
    });

    test('should calculate Levenshtein distance correctly', () {
      final distance = service.calculateLevenshtein('kitten', 'sitting');
      expect(distance, 3);
    });

    // Add more test cases...
  });
}
```

**Run Unit Tests**:
```bash
flutter test test/unit
```

### Widget Tests

```dart
// test/widget/screens/login_screen_test.dart
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:matchfinance_desktop/presentation/screens/auth/login_screen.dart';

void main() {
  testWidgets('Login screen renders correctly', (tester) async {
    await tester.pumpWidget(
      const ProviderScope(
        child: MaterialApp(
          home: LoginScreen(),
        ),
      ),
    );

    expect(find.text('Login'), findsOneWidget);
    expect(find.byType(TextField), findsNWidgets(2)); // Email & Password
    expect(find.byType(ElevatedButton), findsOneWidget);
  });

  testWidgets('Login button triggers auth', (tester) async {
    // Test login flow
  });
}
```

### Integration Tests

```dart
// integration_test/app_test.dart
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:matchfinance_desktop/main.dart' as app;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  group('End-to-end test', () {
    testWidgets('Complete workflow: Login -> Upload -> Process -> Verify',
        (tester) async {
      app.main();
      await tester.pumpAndSettle();

      // 1. Login
      await tester.enterText(find.byKey(Key('emailField')), 'admin@test.com');
      await tester.enterText(find.byKey(Key('passwordField')), 'password');
      await tester.tap(find.byKey(Key('loginButton')));
      await tester.pumpAndSettle();

      // 2. Navigate to upload
      await tester.tap(find.text('Bank Statements'));
      await tester.pumpAndSettle();

      // 3. Upload file
      // ... (file upload test)

      // 4. Wait for processing
      // ...

      // 5. Verify transactions
      // ...
    });
  });
}
```

**Run Integration Tests**:
```bash
flutter test integration_test
```

---

## 📦 Deployment

### Windows Deployment

#### Option 1: MSIX Package (Recommended)

**Configure** `pubspec.yaml`:

```yaml
msix_config:
  display_name: MatchFinance Desktop
  publisher_display_name: Your Company Name
  identity_name: com.yourcompany.matchfinance
  msix_version: 1.0.0.0
  logo_path: assets/images/logo.png
  capabilities: fileSystemAccessAllFiles, internetClient
  install_certificate: false
```

**Build**:
```bash
# Build release
flutter build windows --release

# Create MSIX
dart run msix:create

# Output: build/windows/runner/Release/matchfinance_desktop.msix
```

#### Option 2: Inno Setup Installer

Create `installer.iss`:

```ini
[Setup]
AppName=MatchFinance Desktop
AppVersion=1.0.0
DefaultDirName={pf}\MatchFinance
DefaultGroupName=MatchFinance
OutputDir=build\windows\installer
OutputBaseFilename=matchfinance_setup
Compression=lzma2
SolidCompression=yes

[Files]
Source: "build\windows\runner\Release\*"; DestDir: "{app}"; Flags: recursesubdirs

[Icons]
Name: "{group}\MatchFinance"; Filename: "{app}\matchfinance_desktop.exe"
Name: "{commondesktop}\MatchFinance"; Filename: "{app}\matchfinance_desktop.exe"
```

**Build**:
```bash
flutter build windows --release
iscc installer.iss
```

### macOS Deployment

**Build App Bundle**:
```bash
flutter build macos --release
```

**Create DMG**:
```bash
# Install create-dmg
brew install create-dmg

# Create DMG
create-dmg \
  --volname "MatchFinance" \
  --volicon "assets/images/icon.icns" \
  --window-pos 200 120 \
  --window-size 800 400 \
  --icon-size 100 \
  --icon "MatchFinance.app" 200 190 \
  --hide-extension "MatchFinance.app" \
  --app-drop-link 600 185 \
  "MatchFinance-1.0.0.dmg" \
  "build/macos/Build/Products/Release/matchfinance_desktop.app"
```

**Code Signing** (for distribution):
```bash
# Sign the app
codesign --force --deep --sign "Developer ID Application: Your Name" \
  build/macos/Build/Products/Release/matchfinance_desktop.app

# Notarize
xcrun notarytool submit MatchFinance-1.0.0.dmg \
  --apple-id your@email.com \
  --password "app-specific-password" \
  --team-id YOUR_TEAM_ID \
  --wait
```

### Linux Deployment

#### Option 1: AppImage

```bash
# Install appimagetool
wget "https://github.com/AppImage/AppImageKit/releases/download/continuous/appimagetool-x86_64.AppImage"
chmod +x appimagetool-x86_64.AppImage

# Build release
flutter build linux --release

# Create AppDir structure
mkdir -p AppDir/usr/bin
cp -r build/linux/x64/release/bundle/* AppDir/usr/bin/
cp assets/images/icon.png AppDir/matchfinance.png

# Create desktop entry
cat > AppDir/matchfinance.desktop << EOF
[Desktop Entry]
Type=Application
Name=MatchFinance
Exec=matchfinance_desktop
Icon=matchfinance
Categories=Office;Finance;
EOF

# Build AppImage
./appimagetool-x86_64.AppImage AppDir MatchFinance-1.0.0-x86_64.AppImage
```

#### Option 2: Snap

Create `snap/snapcraft.yaml`:

```yaml
name: matchfinance
version: '1.0.0'
summary: Bank Statement Processing
description: Offline bank statement processing and categorization

base: core22
confinement: strict
grade: stable

apps:
  matchfinance:
    command: matchfinance_desktop
    extensions: [gnome]
    plugs:
      - home
      - network

parts:
  matchfinance:
    plugin: flutter
    source: .
    flutter-target: lib/main.dart
```

**Build**:
```bash
snapcraft
```

---

## 🐛 Troubleshooting

### Common Issues

#### 1. **Database locked error**

```
SqliteException: database is locked
```

**Solution**:
```dart
// Ensure single database instance
final databaseProvider = Provider<AppDatabase>((ref) {
  return AppDatabase(); // Singleton
});

// Don't create multiple instances
```

#### 2. **OCR accuracy low**

**Solutions**:
- Improve image quality before OCR
- Adjust Tesseract configuration
- Preprocess image (contrast, denoise)
- Use ML Kit for better results

```dart
// Image preprocessing
Future<File> preprocessImage(File imageFile) async {
  final img.Image? image = img.decodeImage(await imageFile.readAsBytes());

  // Increase contrast
  final processed = img.contrast(image!, 150);

  // Convert to grayscale
  final grayscale = img.grayscale(processed);

  // Save processed image
  final processedFile = File('${imageFile.path}_processed.png');
  await processedFile.writeAsBytes(img.encodePng(grayscale));

  return processedFile;
}
```

#### 3. **Slow matching performance**

**Solutions**:
- Add database indexes
- Use isolates for heavy computation
- Cache active keywords in memory
- Batch operations

```dart
// Use isolate for matching
Future<void> matchInBackground(List<Transaction> transactions) async {
  await Isolate.run(() {
    // Heavy computation here
    return matchTransactions(transactions);
  });
}
```

#### 4. **Build errors on Windows**

```
Error: The Flutter SDK is not up to date
```

**Solution**:
```bash
flutter upgrade
flutter doctor -v
flutter clean
flutter pub get
```

#### 5. **Memory issues with large PDFs**

**Solution**: Process pages in chunks

```dart
Future<void> processPdfInChunks(File pdfFile) async {
  const chunkSize = 10; // Process 10 pages at a time

  for (int i = 0; i < totalPages; i += chunkSize) {
    final endPage = min(i + chunkSize, totalPages);
    await processPages(i, endPage);

    // Allow garbage collection
    await Future.delayed(Duration(milliseconds: 100));
  }
}
```

### Debug Mode

**Enable detailed logging**:

```dart
// lib/core/config/app_config.dart
class AppConfig {
  static const bool debugMode = true;
  static const bool verboseLogging = true;

  static void log(String message, {String? tag}) {
    if (debugMode) {
      print('${tag ?? 'APP'}: $message');
    }
  }
}
```

### Performance Profiling

```bash
# Profile app performance
flutter run --profile

# Analyze build size
flutter build windows --analyze-size

# Check memory usage
flutter run --profile --trace-startup
```

---

## 📚 Resources

### Official Documentation

- [Flutter Desktop](https://docs.flutter.dev/platform-integration/desktop)
- [Drift Database](https://drift.simonbinder.eu/)
- [Riverpod](https://riverpod.dev/)
- [Go Router](https://pub.dev/packages/go_router)

### Code Examples

See `examples/` directory (to be created) for:
- Complete login flow
- PDF upload & processing
- Transaction matching examples
- Report generation examples

### Additional Guides

1. **[IMPLEMENTATION_GUIDE.md](./IMPLEMENTATION_GUIDE.md)** - Detailed feature implementation
2. **[API_REFERENCE.md](./API_REFERENCE.md)** - API documentation (to be created)
3. **[TESTING_GUIDE.md](./TESTING_GUIDE.md)** - Comprehensive testing guide (to be created)
4. **[DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md)** - Detailed deployment instructions (to be created)

### Community

- [Flutter Discord](https://discord.gg/flutter)
- [Drift Discord](https://discord.gg/drift)
- [Stack Overflow - Flutter](https://stackoverflow.com/questions/tagged/flutter)

---

## ❓ FAQ

### Q: Berapa lama waktu development?

**A**: Estimasi 5-7 bulan untuk MVP offline-first. Breakdown:
- Phase 1 (Foundation): 4-6 minggu
- Phase 2 (Core Processing): 6-8 minggu
- Phase 3 (UI & Reports): 4-6 minggu
- Phase 4 (Advanced): 4-6 minggu
- Phase 5 (Polish): 3-4 minggu

### Q: Apakah bisa sync dengan web version?

**A**: Ya, bisa ditambahkan di Phase 6 (optional). Perlu backend API untuk sync.

### Q: OCR offline akurat?

**A**: Tesseract offline accuracy ~85-90%, sedikit lebih rendah dari API eksternal (~95%). Untuk hasil terbaik gunakan Google ML Kit.

### Q: Apakah perlu internet?

**A**: **Tidak**. Aplikasi fully offline. Internet hanya diperlukan untuk:
- Cloud backup (optional)
- App updates
- AI chat features (optional)

### Q: Database size limit?

**A**: SQLite mendukung hingga 281 terabytes. Untuk practical use: 10GB+ tanpa masalah.

### Q: Bagaimana update aplikasi?

**A**: Implementasi auto-update mechanism:
- Windows: MSIX auto-update atau custom updater
- macOS: Sparkle framework
- Linux: Package manager updates

### Q: Licensing model?

**A**: Bisa implementasikan license key validation (online check saat startup pertama, kemudian offline grace period).

---

## 🎓 Next Steps

1. **Read** [IMPLEMENTATION_GUIDE.md](./IMPLEMENTATION_GUIDE.md) untuk detail implementasi
2. **Complete** prerequisites dan setup development environment
3. **Start** dengan Phase 1: Database setup dan authentication
4. **Join** Flutter community untuk support
5. **Test** frequently dengan unit & integration tests

---

## 📄 License

Proprietary - All rights reserved

---

## 👥 Contributors

- **Lead Developer**: [Your Name]
- **Contributors**: [Team Members]

---

<div align="center">

**[⬆ Back to Top](#matchfinance-flutter-desktop---getting-started-guide)**

Made with ❤️ using Flutter

</div>
