# Flutter Desktop Migration Plan - MatchFinance

## Executive Summary

Rencana migrasi aplikasi **MatchFinance** dari Laravel Web Application (SaaS multi-tenant) menjadi **Flutter Desktop Application** dengan **Offline Mode**.

**Target Platform**: Windows, macOS, Linux Desktop
**Architecture**: Offline-first dengan optional cloud sync
**Database**: SQLite (local) dengan optional PostgreSQL sync
**Scope**: ~37,700 lines PHP code → Flutter/Dart application

---

## 1. Analisis Aplikasi Saat Ini

### Karakteristik Utama
- **Type**: Multi-tenant SaaS Web Application
- **Backend**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL 8.0+
- **External Dependencies**:
  - OCR API Service (eksternal)
  - OpenAI API (chat AI)
  - Google OAuth
  - Queue processing (Redis/Database)

### Fitur Inti (Harus Dimigrasikan)
1. ✅ Upload & Process PDF bank statements
2. ✅ OCR processing (perlu alternatif offline)
3. ✅ Transaction categorization (keyword matching)
4. ✅ Account mapping (GL accounts)
5. ✅ Master data management (Banks, Types, Categories, Keywords)
6. ✅ Reports & Analytics
7. ✅ Transaction verification & feedback
8. ✅ Multi-user management (per company)
9. ⚠️ AI Chat integration (optional, perlu internet)
10. ⚠️ Multi-tenant (disederhanakan untuk desktop)

### Fitur yang Perlu Adaptasi
- **Multi-tenancy**: Ganti dengan multi-company database lokal
- **Queue Jobs**: Ganti dengan isolate/background tasks
- **OCR API**: Ganti dengan library OCR offline (Tesseract)
- **AI Chat**: Optional feature, hanya jalan saat online
- **Email notifications**: Ganti dengan in-app notifications

---

## 2. Target Architecture - Flutter Desktop

### Technology Stack

#### Frontend Layer
```
Flutter Desktop (Windows, macOS, Linux)
├── UI Framework: Flutter 3.24+
├── State Management: Riverpod 2.5+ / Bloc 8.0+
├── Routing: go_router 14.0+
├── Local Database: drift (SQLite wrapper) 2.20+
└── Styling: Material Design 3 / Fluent Design
```

#### Data Layer
```
Local Database
├── Primary: SQLite (via drift package)
├── File Storage: Local file system
├── Cache: Hive / SharedPreferences
└── Backup: Export to JSON/SQL files
```

#### Business Logic Layer
```
Services & Repositories
├── Transaction Matching Service (Dart implementation)
├── OCR Processing Service (Tesseract OCR)
├── PDF Parser Service (pdf, syncfusion_flutter_pdf)
├── Export Service (Excel, PDF generation)
├── Backup & Restore Service
└── Optional: Sync Service (cloud backup)
```

#### External Integrations (Optional, Online Mode)
```
Optional Services (Require Internet)
├── OpenAI API (AI Chat) - via http/dio
├── Cloud Backup (Google Drive, Dropbox API)
└── License Validation (online check)
```

---

## 3. Database Migration Strategy

### From MySQL to SQLite

#### Current MySQL Schema: 24 Tables
```
Core:
- companies, users, plans, company_subscriptions

Master Data:
- banks, types, categories, sub_categories, keywords

Processing:
- bank_statements, statement_transactions
- matching_logs, account_matching_logs

Accounts:
- accounts, account_keywords, transaction_categories

AI (Optional):
- chat_sessions, chat_messages, document_collections
- document_items, chat_knowledge_snapshots

System:
- jobs (queue), personal_access_tokens
```

#### Target SQLite Schema

**Simplifikasi untuk Desktop**:

```sql
-- Core Tables (Simplified Multi-tenancy)
CREATE TABLE companies (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  name TEXT NOT NULL,
  logo_path TEXT,
  settings TEXT, -- JSON
  status TEXT DEFAULT 'active',
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  deleted_at INTEGER
);

CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  company_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL, -- owner, admin, manager, staff
  is_active INTEGER DEFAULT 1,
  avatar_path TEXT,
  preferences TEXT, -- JSON
  last_login_at INTEGER,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  deleted_at INTEGER,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  UNIQUE(company_id, email)
);

-- Master Data Tables
CREATE TABLE banks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  company_id INTEGER NOT NULL,
  bank_name TEXT NOT NULL,
  code TEXT NOT NULL,
  logo_path TEXT,
  is_active INTEGER DEFAULT 1,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  deleted_at INTEGER,
  FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE types (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  company_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  color TEXT,
  priority INTEGER DEFAULT 0,
  is_active INTEGER DEFAULT 1,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  deleted_at INTEGER,
  FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  company_id INTEGER NOT NULL,
  type_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  color TEXT,
  priority INTEGER DEFAULT 0,
  is_active INTEGER DEFAULT 1,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  deleted_at INTEGER,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (type_id) REFERENCES types(id)
);

CREATE TABLE sub_categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  company_id INTEGER NOT NULL,
  category_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  color TEXT,
  priority INTEGER DEFAULT 1, -- 1-10
  is_active INTEGER DEFAULT 1,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  deleted_at INTEGER,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE keywords (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  company_id INTEGER NOT NULL,
  sub_category_id INTEGER NOT NULL,
  keyword TEXT NOT NULL,
  is_regex INTEGER DEFAULT 0,
  case_sensitive INTEGER DEFAULT 0,
  match_type TEXT DEFAULT 'contains', -- exact, contains, starts_with, ends_with, regex
  min_amount REAL,
  max_amount REAL,
  priority INTEGER DEFAULT 5, -- 1-10
  is_active INTEGER DEFAULT 1,
  match_count INTEGER DEFAULT 0,
  last_matched_at INTEGER,
  effectiveness_score INTEGER DEFAULT 0, -- 0-100
  false_positive_count INTEGER DEFAULT 0,
  true_positive_count INTEGER DEFAULT 0,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  deleted_at INTEGER,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (sub_category_id) REFERENCES sub_categories(id)
);

CREATE INDEX idx_keywords_active ON keywords(company_id, is_active, priority);

-- Bank Statements & Transactions
CREATE TABLE bank_statements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  company_id INTEGER NOT NULL,
  bank_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  file_path TEXT NOT NULL,
  file_hash TEXT UNIQUE NOT NULL,
  original_filename TEXT NOT NULL,
  file_size INTEGER NOT NULL,

  -- OCR Processing Status
  ocr_status TEXT DEFAULT 'pending', -- pending, processing, completed, failed
  ocr_response TEXT, -- JSON
  ocr_error TEXT,
  ocr_started_at INTEGER,
  ocr_completed_at INTEGER,

  -- Statement Info
  bank_name TEXT,
  account_number TEXT,
  account_holder_name TEXT,
  period_from INTEGER,
  period_to INTEGER,
  opening_balance REAL,
  closing_balance REAL,

  -- Statistics
  total_transactions INTEGER DEFAULT 0,
  matched_transactions INTEGER DEFAULT 0,
  unmatched_transactions INTEGER DEFAULT 0,
  verified_transactions INTEGER DEFAULT 0,

  -- Matching Status
  matching_status TEXT DEFAULT 'pending',
  matching_started_at INTEGER,
  matching_completed_at INTEGER,

  status TEXT DEFAULT 'active',
  uploaded_at INTEGER NOT NULL,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  deleted_at INTEGER,

  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (bank_id) REFERENCES banks(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE INDEX idx_statements_company ON bank_statements(company_id, deleted_at);
CREATE INDEX idx_statements_status ON bank_statements(company_id, ocr_status, matching_status);
CREATE INDEX idx_statements_period ON bank_statements(company_id, period_from, period_to);

CREATE TABLE statement_transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  company_id INTEGER NOT NULL,
  bank_statement_id INTEGER NOT NULL,

  -- Transaction Data
  transaction_date INTEGER NOT NULL,
  description TEXT NOT NULL,
  normalized_description TEXT,
  reference_no TEXT,
  debit_amount REAL DEFAULT 0,
  credit_amount REAL DEFAULT 0,
  balance REAL,
  transaction_type TEXT, -- debit, credit
  amount REAL,

  -- Categorization
  type_id INTEGER,
  category_id INTEGER,
  sub_category_id INTEGER,
  matched_keyword_id INTEGER,
  confidence_score INTEGER DEFAULT 0, -- 0-100
  is_manual_category INTEGER DEFAULT 0,
  matching_reason TEXT,
  match_method TEXT, -- exact_match, contains, regex, similarity
  alternative_categories TEXT, -- JSON array of suggestions

  -- Account Mapping
  account_id INTEGER,
  matched_account_keyword_id INTEGER,
  account_confidence_score INTEGER DEFAULT 0,
  is_manual_account INTEGER DEFAULT 0,

  -- Verification
  is_verified INTEGER DEFAULT 0,
  verified_by INTEGER,
  verified_at INTEGER,
  feedback_status TEXT, -- pending, correct, incorrect, partial
  feedback_notes TEXT,

  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  deleted_at INTEGER,

  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (bank_statement_id) REFERENCES bank_statements(id),
  FOREIGN KEY (type_id) REFERENCES types(id),
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FOREIGN KEY (sub_category_id) REFERENCES sub_categories(id),
  FOREIGN KEY (matched_keyword_id) REFERENCES keywords(id),
  FOREIGN KEY (account_id) REFERENCES accounts(id),
  FOREIGN KEY (verified_by) REFERENCES users(id)
);

CREATE INDEX idx_transactions_statement ON statement_transactions(bank_statement_id, deleted_at);
CREATE INDEX idx_transactions_date ON statement_transactions(company_id, transaction_date);
CREATE INDEX idx_transactions_category ON statement_transactions(company_id, sub_category_id);
CREATE INDEX idx_transactions_verified ON statement_transactions(company_id, is_verified);

-- Accounts (GL Mapping)
CREATE TABLE accounts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  company_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  code TEXT NOT NULL,
  account_type TEXT NOT NULL, -- Asset, Liability, Equity, Revenue, Expense
  description TEXT,
  color TEXT,
  priority INTEGER DEFAULT 5,
  is_active INTEGER DEFAULT 1,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  deleted_at INTEGER,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  UNIQUE(company_id, code)
);

CREATE TABLE account_keywords (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  company_id INTEGER NOT NULL,
  account_id INTEGER NOT NULL,
  keyword TEXT NOT NULL,
  is_regex INTEGER DEFAULT 0,
  case_sensitive INTEGER DEFAULT 0,
  match_type TEXT DEFAULT 'contains',
  min_amount REAL,
  max_amount REAL,
  priority INTEGER DEFAULT 5,
  is_active INTEGER DEFAULT 1,
  match_count INTEGER DEFAULT 0,
  last_matched_at INTEGER,
  effectiveness_score INTEGER DEFAULT 0,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  deleted_at INTEGER,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (account_id) REFERENCES accounts(id)
);

-- Matching Logs (Audit Trail)
CREATE TABLE matching_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  company_id INTEGER NOT NULL,
  statement_transaction_id INTEGER NOT NULL,
  keyword_id INTEGER,
  sub_category_id INTEGER,
  confidence_score INTEGER,
  is_matched INTEGER,
  match_reason TEXT,
  match_method TEXT,
  match_details TEXT, -- JSON
  created_at INTEGER NOT NULL,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (statement_transaction_id) REFERENCES statement_transactions(id)
);

CREATE TABLE account_matching_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  company_id INTEGER NOT NULL,
  statement_transaction_id INTEGER NOT NULL,
  account_id INTEGER,
  account_keyword_id INTEGER,
  confidence_score INTEGER,
  is_matched INTEGER,
  match_reason TEXT,
  match_details TEXT, -- JSON
  created_at INTEGER NOT NULL,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (statement_transaction_id) REFERENCES statement_transactions(id)
);

-- Background Tasks (Replace Queue Jobs)
CREATE TABLE background_tasks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  uuid TEXT UNIQUE NOT NULL,
  company_id INTEGER NOT NULL,
  task_type TEXT NOT NULL, -- ocr_processing, transaction_matching, account_matching
  reference_type TEXT, -- bank_statement, transaction
  reference_id INTEGER,
  status TEXT DEFAULT 'pending', -- pending, processing, completed, failed
  progress INTEGER DEFAULT 0, -- 0-100
  error_message TEXT,
  started_at INTEGER,
  completed_at INTEGER,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL,
  FOREIGN KEY (company_id) REFERENCES companies(id)
);

-- Settings & Configuration
CREATE TABLE app_settings (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  key TEXT UNIQUE NOT NULL,
  value TEXT, -- JSON or plain text
  description TEXT,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL
);

-- Activity Log (Optional)
CREATE TABLE activity_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  company_id INTEGER NOT NULL,
  user_id INTEGER,
  action TEXT NOT NULL,
  entity_type TEXT,
  entity_id INTEGER,
  description TEXT,
  metadata TEXT, -- JSON
  ip_address TEXT,
  user_agent TEXT,
  created_at INTEGER NOT NULL,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### Migration Strategy

**Data Migration Tools**:
```dart
// Create migration script: MySQL → SQLite
class DatabaseMigration {
  // Export dari Laravel MySQL
  Future<void> exportFromLaravel() async {
    // Generate JSON export dari Laravel
    // Endpoint: /api/export/full-data
  }

  // Import ke SQLite
  Future<void> importToSQLite(String jsonPath) async {
    // Parse JSON
    // Insert ke SQLite dengan drift
  }
}
```

---

## 4. Feature Implementation Roadmap

### Phase 1: Core Foundation (4-6 Weeks)

#### Week 1-2: Project Setup & Database
- [ ] Setup Flutter desktop project (Windows, macOS, Linux)
- [ ] Initialize drift database (SQLite)
- [ ] Create database schema & migrations
- [ ] Setup state management (Riverpod/Bloc)
- [ ] Create app architecture (clean architecture)
- [ ] Setup routing (go_router)
- [ ] Create base UI components (Material 3)

**Key Packages**:
```yaml
dependencies:
  flutter:
    sdk: flutter

  # State Management
  flutter_riverpod: ^2.5.1
  riverpod_annotation: ^2.3.5

  # Database
  drift: ^2.20.0
  drift_flutter: ^0.2.0
  sqlite3_flutter_libs: ^0.5.0

  # Routing
  go_router: ^14.6.1

  # Utilities
  uuid: ^4.5.0
  intl: ^0.19.0
  path_provider: ^2.1.0
  path: ^1.9.0

  # UI
  flutter_animate: ^4.5.0
  gap: ^3.0.1

dev_dependencies:
  # Code Generation
  build_runner: ^2.4.0
  drift_dev: ^2.20.0
  riverpod_generator: ^2.4.3

  # Testing
  flutter_test:
    sdk: flutter
  mockito: ^5.4.0
```

#### Week 3-4: Authentication & Multi-Company
- [ ] User authentication (local, no OAuth untuk start)
- [ ] Password hashing (bcrypt/argon2)
- [ ] Session management
- [ ] Company selection/switching
- [ ] User management CRUD
- [ ] Role-based access control
- [ ] Settings management

**Features**:
- Login/Logout
- Change password
- Multi-company support (switch company)
- User roles: Owner, Admin, Manager, Staff
- Permission checks

#### Week 5-6: Master Data Management
- [ ] Banks management
- [ ] Types management
- [ ] Categories management
- [ ] Sub-categories management
- [ ] Keywords management (CRUD)
- [ ] Keyword priority & configuration
- [ ] Import/Export master data (CSV/JSON)

**UI Screens**:
- Master data list views (DataTable)
- Create/Edit forms
- Search & filter
- Bulk operations
- Import/Export dialogs

---

### Phase 2: Core Processing (6-8 Weeks)

#### Week 7-9: PDF Processing & OCR
- [ ] PDF file picker & upload
- [ ] PDF preview (syncfusion_flutter_pdf / native_pdf_view)
- [ ] Integrate Tesseract OCR (offline)
  - Windows: tesseract_ocr_windows
  - macOS/Linux: ffigen + tesseract C bindings
- [ ] Bank-specific PDF parsers (6 banks)
  - BCA, Mandiri, BNI, BRI, BTN, CIMB
- [ ] Transaction extraction logic
- [ ] Background processing (Isolate)
- [ ] Progress tracking UI

**Key Packages**:
```yaml
dependencies:
  # PDF
  syncfusion_flutter_pdf: ^27.2.2
  native_pdf_view: ^6.0.0
  file_picker: ^8.1.2

  # OCR (Offline)
  # Option 1: Tesseract (Recommended)
  tesseract_ocr_windows: ^0.0.5  # Windows
  # macOS/Linux: Custom FFI integration

  # Option 2: ML Kit (Google, requires model download)
  google_mlkit_text_recognition: ^0.13.0

  # Background Processing
  isolate: ^2.1.2
```

**OCR Implementation**:
```dart
class OfflineOcrService {
  // Tesseract OCR processing
  Future<OcrResult> processStatement(File pdfFile, String bankCode) async {
    // 1. Convert PDF to images
    final images = await pdfToImages(pdfFile);

    // 2. Run OCR on each page
    final textBlocks = await runTesseract(images);

    // 3. Parse dengan bank parser
    final parser = BankParserFactory.getParser(bankCode);
    final transactions = parser.extractTransactions(textBlocks);

    return OcrResult(
      transactions: transactions,
      metadata: metadata,
    );
  }
}
```

#### Week 10-12: Transaction Matching
- [ ] Implement matching algorithm (Dart port dari PHP)
  - Exact match
  - Contains match
  - Fuzzy match (Levenshtein distance)
  - Regex match
- [ ] Confidence scoring
- [ ] Alternative suggestions (top 5)
- [ ] Background processing dengan Isolate
- [ ] Match result preview
- [ ] Manual override capabilities

**Matching Algorithm**:
```dart
class TransactionMatchingService {
  Future<MatchResult> matchTransaction(
    String description,
    List<Keyword> keywords,
  ) async {
    // 1. Normalize text
    final normalized = normalizeText(description);

    // 2. Try exact match
    final exactMatch = await checkExactMatch(normalized, keywords);
    if (exactMatch != null) {
      return MatchResult(
        keyword: exactMatch,
        confidence: 100,
        method: MatchMethod.exact,
      );
    }

    // 3. Try contains match
    final containsMatch = await checkContainsMatch(normalized, keywords);
    if (containsMatch != null && containsMatch.confidence >= 70) {
      return MatchResult(
        keyword: containsMatch.keyword,
        confidence: containsMatch.confidence,
        method: MatchMethod.contains,
      );
    }

    // 4. Try fuzzy match
    final fuzzyMatch = await checkFuzzyMatch(normalized, keywords);
    if (fuzzyMatch != null && fuzzyMatch.confidence >= 70) {
      return MatchResult(
        keyword: fuzzyMatch.keyword,
        confidence: fuzzyMatch.confidence,
        method: MatchMethod.fuzzy,
      );
    }

    // 5. Generate alternatives
    final alternatives = await generateAlternatives(normalized, keywords);

    return MatchResult(
      keyword: null,
      confidence: 0,
      alternatives: alternatives,
    );
  }

  int calculateLevenshteinDistance(String s1, String s2) {
    // Implementation
  }
}
```

**Packages**:
```yaml
dependencies:
  string_similarity: ^2.0.0  # Levenshtein distance
```

#### Week 13-14: Account Matching
- [ ] GL Account management
- [ ] Account keywords
- [ ] Account matching service
- [ ] Parallel matching untuk transactions
- [ ] Matching logs

---

### Phase 3: UI & Reporting (4-6 Weeks)

#### Week 15-17: Main UI
- [ ] Dashboard (statistics, charts)
  - Total transactions
  - Matched vs unmatched
  - Category breakdown (pie chart)
  - Timeline chart (line chart)
- [ ] Bank statements list
  - Upload button
  - Status indicators
  - Progress bars
  - Actions menu
- [ ] Transaction list & detail
  - DataTable with sorting/filtering
  - Confidence indicators
  - Category badges
  - Edit/verify actions
- [ ] Verification workflow
  - Review low-confidence transactions
  - Approve/reject/modify
  - Bulk operations
- [ ] Search & filters

**Charting Packages**:
```yaml
dependencies:
  fl_chart: ^0.70.0
  syncfusion_flutter_charts: ^27.2.2
```

#### Week 18-20: Reports & Export
- [ ] Report types:
  1. Monthly by Bank
  2. By Keyword
  3. By Category
  4. By Sub-Category
  5. By Account
  6. Comparison View
- [ ] Date range filtering
- [ ] Excel export (excel package)
- [ ] PDF export (pdf package)
- [ ] Print functionality
- [ ] Report templates

**Export Packages**:
```yaml
dependencies:
  excel: ^4.0.2
  pdf: ^3.11.1
  printing: ^5.13.2
```

---

### Phase 4: Advanced Features (4-6 Weeks)

#### Week 21-23: Learning & Optimization
- [ ] User feedback system
- [ ] Keyword effectiveness tracking
- [ ] Auto-suggest new keywords
- [ ] Pattern learning
- [ ] False positive tracking
- [ ] Keyword optimization UI

#### Week 24-26: Data Management
- [ ] Backup & Restore
  - Export to JSON/SQL
  - Import from backup
  - Scheduled auto-backup
- [ ] Data import/export
  - CSV import (transactions, keywords)
  - Excel import
  - Template downloads
- [ ] Company data isolation
- [ ] Data cleanup tools

**Backup Implementation**:
```dart
class BackupService {
  Future<File> createBackup(int companyId) async {
    // Export entire company database to JSON
    final data = await _exportCompanyData(companyId);
    final json = jsonEncode(data);

    final appDir = await getApplicationDocumentsDirectory();
    final backupFile = File(
      '${appDir.path}/backups/backup_${DateTime.now().millisecondsSinceEpoch}.json',
    );

    await backupFile.writeAsString(json);
    return backupFile;
  }

  Future<void> restoreBackup(File backupFile) async {
    final json = await backupFile.readAsString();
    final data = jsonDecode(json);
    await _importCompanyData(data);
  }
}
```

---

### Phase 5: Polish & Distribution (3-4 Weeks)

#### Week 27-28: Testing & QA
- [ ] Unit tests (drift queries, matching logic)
- [ ] Integration tests (workflows)
- [ ] Widget tests (UI components)
- [ ] Performance testing
  - Large PDF processing
  - 10,000+ transactions matching
  - Database query optimization
- [ ] Bug fixes
- [ ] UI polish

#### Week 29-30: Distribution & Documentation
- [ ] Windows installer (Inno Setup / MSIX)
- [ ] macOS DMG
- [ ] Linux AppImage / Snap
- [ ] User documentation
- [ ] Admin guide
- [ ] Release notes
- [ ] License management (optional)

**Windows Packaging**:
```yaml
# pubspec.yaml
flutter_launcher_icons: ^0.13.1
msix: ^3.16.7

msix_config:
  display_name: MatchFinance
  publisher_display_name: Your Company
  identity_name: com.yourcompany.matchfinance
  msix_version: 1.0.0.0
  capabilities: fileSystemAccessAllFiles
```

---

## 5. Optional Features (Post-Launch)

### Phase 6: Cloud Sync (Optional, 4-6 Weeks)
- [ ] Backend API (Laravel/Node.js/Supabase)
- [ ] Sync protocol (delta sync)
- [ ] Conflict resolution
- [ ] Online/offline detection
- [ ] Background sync
- [ ] Cloud backup
- [ ] Multi-device support

**Sync Architecture**:
```dart
class SyncService {
  // Push local changes to cloud
  Future<void> pushChanges() async {
    final changes = await _getUnsynced Changes();
    await _apiClient.post('/sync/push', changes);
  }

  // Pull remote changes
  Future<void> pullChanges() async {
    final lastSync = await _getLastSyncTimestamp();
    final changes = await _apiClient.get('/sync/pull?since=$lastSync');
    await _applyChanges(changes);
  }

  // Auto sync every 5 minutes if online
  void startAutoSync() {
    Timer.periodic(Duration(minutes: 5), (_) async {
      if (await isOnline()) {
        await sync();
      }
    });
  }
}
```

### AI Features (Requires Internet)
- [ ] OpenAI integration (chat)
- [ ] AI-powered keyword suggestions
- [ ] Natural language queries
- [ ] Transaction insights
- [ ] Anomaly detection

---

## 6. Technical Considerations

### Performance Optimization

1. **Database Optimization**
   - Proper indexing (company_id, dates, status)
   - Query optimization (EXPLAIN QUERY PLAN)
   - Connection pooling
   - Batch operations

2. **Background Processing**
   - Use Dart Isolates untuk OCR & matching
   - Progress reporting via SendPort
   - Cancellable operations
   - Error handling

3. **Memory Management**
   - Stream-based PDF processing
   - Paginated lists (lazy loading)
   - Image caching
   - Dispose controllers properly

4. **Startup Time**
   - Lazy initialization
   - Splash screen dengan progress
   - Preload critical data only

### Security Considerations

1. **Data Protection**
   - Encrypt SQLite database (sqlcipher)
   - Secure password hashing (bcrypt)
   - Session timeout
   - Auto-lock feature

2. **File Security**
   - Store PDFs in app directory (protected)
   - Encrypt sensitive files
   - Secure delete (overwrite)

3. **Access Control**
   - Role-based permissions
   - Action logging
   - Audit trail

**Encryption**:
```yaml
dependencies:
  sqlcipher_flutter_libs: ^0.6.1
  encrypt: ^5.0.3
  bcrypt: ^1.1.3
```

### Cross-Platform Considerations

**Windows**:
- Use windows_path_provider
- Handle Windows-specific file paths
- MSI/MSIX installer

**macOS**:
- App sandboxing
- Notarization for distribution
- DMG packaging

**Linux**:
- AppImage / Snap / Flatpak
- Different distro testing

---

## 7. Team & Resource Requirements

### Development Team
- **1 Senior Flutter Developer**: Architecture & core features
- **1-2 Flutter Developers**: UI & features
- **1 QA Tester**: Testing & bug tracking
- **Optional: 1 DevOps**: Deployment & CI/CD

### Skills Required
- Flutter Desktop development
- SQLite & drift
- Dart isolates & concurrency
- OCR integration
- State management (Riverpod/Bloc)
- PDF processing
- UI/UX design

### Tools & Services
- **IDE**: VS Code / Android Studio
- **Version Control**: Git + GitHub/GitLab
- **CI/CD**: GitHub Actions / Codemagic
- **Testing**: Flutter Test + integration_test
- **Analytics** (optional): Sentry, Firebase Crashlytics

---

## 8. Timeline Summary

| Phase | Duration | Deliverables |
|-------|----------|-------------|
| **Phase 1**: Foundation | 4-6 weeks | Project setup, database, auth, master data |
| **Phase 2**: Core Processing | 6-8 weeks | PDF/OCR, matching, accounts |
| **Phase 3**: UI & Reporting | 4-6 weeks | Dashboard, lists, reports |
| **Phase 4**: Advanced | 4-6 weeks | Learning, backup, optimization |
| **Phase 5**: Polish | 3-4 weeks | Testing, packaging, docs |
| **Phase 6**: Cloud (Optional) | 4-6 weeks | Sync, multi-device |
| **Total** | **21-30 weeks** | **Full application** |

**Realistic Timeline**: 5-7 bulan untuk MVP, 8-10 bulan untuk production-ready dengan cloud sync.

---

## 9. Risk Assessment & Mitigation

### Technical Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| OCR accuracy offline < online API | High | Test multiple OCR engines, allow manual correction, hybrid approach |
| Performance with large PDFs | Medium | Streaming, chunked processing, progress indicators |
| Cross-platform compatibility | Medium | Test on all platforms early, CI/CD for each platform |
| Database migration complexity | Low | Well-tested migration scripts, backup/restore |
| State management complexity | Medium | Choose proven solution (Riverpod), clear architecture |

### Business Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Feature parity with web version | High | Prioritize core features, phase optional features |
| User adoption (new UI/workflow) | Medium | User testing, training materials, migration guide |
| Licensing/distribution | Low | Clear license model, update mechanism |
| Support & maintenance | Medium | Good documentation, error logging, update system |

---

## 10. Success Criteria

### Functional Requirements
- ✅ Upload & process PDF bank statements (6 banks)
- ✅ OCR accuracy ≥ 90% (vs 95% with online API)
- ✅ Transaction matching accuracy ≥ 85%
- ✅ Support 10,000+ transactions per company
- ✅ Report generation < 5 seconds
- ✅ Full offline functionality
- ✅ Multi-company support

### Non-Functional Requirements
- ✅ App startup < 3 seconds
- ✅ PDF processing: 1 page per 5-10 seconds (offline OCR)
- ✅ Transaction matching: 1000 transactions per 10 seconds
- ✅ Responsive UI (60 FPS)
- ✅ Database size: Support up to 10GB
- ✅ Cross-platform compatibility (Windows, macOS, Linux)
- ✅ Memory usage < 500MB idle, < 2GB processing

### User Experience
- ✅ Intuitive UI (Material Design 3)
- ✅ Keyboard shortcuts
- ✅ Drag & drop file upload
- ✅ Real-time progress indicators
- ✅ Clear error messages
- ✅ Undo/redo support (where applicable)

---

## 11. Next Steps

### Immediate Actions (Week 1)

1. **Decision Making**:
   - [ ] Confirm technology stack (Riverpod vs Bloc?)
   - [ ] Choose OCR solution (Tesseract vs ML Kit vs hybrid?)
   - [ ] Decide on cloud sync requirement (now or later?)
   - [ ] Select design system (Material 3 vs Fluent vs custom?)

2. **Project Setup**:
   - [ ] Create Flutter project: `flutter create --platforms=windows,macos,linux matchfinance_desktop`
   - [ ] Setup Git repository
   - [ ] Initialize project structure (clean architecture)
   - [ ] Setup CI/CD pipeline

3. **Prototype**:
   - [ ] Create SQLite schema with drift
   - [ ] Build simple login screen
   - [ ] Test PDF upload & preview
   - [ ] POC: Tesseract OCR integration
   - [ ] POC: Transaction matching algorithm

4. **Documentation**:
   - [ ] Technical architecture document
   - [ ] API design (if cloud sync needed)
   - [ ] UI/UX mockups (Figma)
   - [ ] Database schema documentation

---

## 12. Budget Estimate (Optional)

### Development Costs

**Assumptions**:
- 1 Senior Flutter Dev: $80/hour
- 1 Flutter Dev: $50/hour
- 1 QA: $40/hour

| Item | Hours | Cost |
|------|-------|------|
| Senior Dev (30 weeks) | 1200 | $96,000 |
| Developer (30 weeks) | 1200 | $60,000 |
| QA (20 weeks) | 800 | $32,000 |
| **Total Development** | | **$188,000** |

### Additional Costs
- Design/UI: $5,000 - $10,000
- Code signing certificates: $300/year
- Cloud hosting (if sync): $50-200/month
- Tools & licenses: $1,000
- **Total Additional**: **$6,300 - $11,300**

**Total Project Cost**: **$194,300 - $199,300** (outsourced)

**Or Internal Team**: 6-8 bulan development time

---

## 13. Alternatives Considered

### Option 1: Electron + React/Vue (NOT Recommended)
**Pros**: Reuse web code, faster development
**Cons**: Large bundle size (150MB+), poor performance, high memory usage
**Verdict**: ❌ Not suitable untuk offline desktop app

### Option 2: .NET MAUI (C#)
**Pros**: Native performance, good Windows support
**Cons**: Weaker macOS/Linux support, less mature than Flutter
**Verdict**: ⚠️ Possible but Flutter is better for cross-platform

### Option 3: Tauri + Svelte/React (Rust + Web)
**Pros**: Small bundle, fast performance, modern
**Cons**: Less mature, smaller ecosystem, learning curve
**Verdict**: ⚠️ Good option but riskier than Flutter

### Option 4: Flutter Desktop (Recommended) ✅
**Pros**:
- True native performance
- Single codebase for all platforms
- Large ecosystem & community
- Mature desktop support
- Beautiful UI out of the box
- Excellent offline support
**Cons**:
- Larger app size than native (40-60MB)
- Some desktop features need plugins
**Verdict**: ✅ **BEST CHOICE** for this project

---

## Conclusion

Migrasi dari Laravel web app ke Flutter desktop dengan offline mode adalah **feasible** dan **recommended**.

**Key Success Factors**:
1. ✅ Core business logic dapat diimplementasikan di Dart
2. ✅ Offline OCR tersedia (Tesseract)
3. ✅ SQLite cukup powerful untuk local database
4. ✅ Flutter desktop sudah mature untuk production
5. ✅ Bisa dimulai offline-only, add cloud sync later

**Recommended Approach**:
- Start dengan **offline-only MVP** (Phase 1-4)
- Polish & release v1.0 (Phase 5)
- Add cloud sync sebagai v2.0 feature (Phase 6)

**Timeline**: 5-7 bulan untuk production-ready offline app

**Next Action**: Confirm requirements dan start Phase 1! 🚀
