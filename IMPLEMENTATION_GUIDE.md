# MatchFinance Flutter - Detailed Implementation Guide

**Complete step-by-step implementation guide dengan full code examples**

---

## 📋 Table of Contents

1. [Database Layer - Complete Implementation](#1-database-layer---complete-implementation)
2. [Authentication System](#2-authentication-system)
3. [Master Data Management](#3-master-data-management)
4. [PDF Upload & OCR Processing](#4-pdf-upload--ocr-processing)
5. [Transaction Matching Algorithm](#5-transaction-matching-algorithm)
6. [Account Mapping](#6-account-mapping)
7. [Dashboard & Charts](#7-dashboard--charts)
8. [Reports & Export](#8-reports--export)
9. [Backup & Restore](#9-backup--restore)
10. [Performance Optimization](#10-performance-optimization)

---

## 1. Database Layer - Complete Implementation

### 1.1 Complete Table Definitions

#### Companies Table

```dart
// lib/data/database/tables/companies.dart
import 'package:drift/drift.dart';

@DataClassName('Company')
class Companies extends Table {
  IntColumn get id => integer().autoIncrement()();
  TextColumn get uuid => text().unique()();
  TextColumn get name => text().withLength(min: 1, max: 255)();
  TextColumn get slug => text().unique().nullable()();
  TextColumn get logoPath => text().nullable()();
  TextColumn get settings => text().nullable()(); // JSON
  TextColumn get status => text().withDefault(const Constant('active'))();
  IntColumn get createdAt => integer()();
  IntColumn get updatedAt => integer()();
  IntColumn get deletedAt => integer().nullable()();
}
```

#### Users Table

```dart
// lib/data/database/tables/users.dart
import 'package:drift/drift.dart';

@DataClassName('User')
class Users extends Table {
  IntColumn get id => integer().autoIncrement()();
  TextColumn get uuid => text().unique()();
  IntColumn get companyId => integer().references(Companies, #id)();
  TextColumn get name => text().withLength(min: 1, max: 255)();
  TextColumn get email => text()();
  TextColumn get passwordHash => text()();
  TextColumn get role => text()(); // owner, admin, manager, staff
  BoolColumn get isActive => boolean().withDefault(const Constant(true))();
  TextColumn get avatarPath => text().nullable()();
  TextColumn get preferences => text().nullable()(); // JSON
  IntColumn get lastLoginAt => integer().nullable()();
  IntColumn get createdAt => integer()();
  IntColumn get updatedAt => integer()();
  IntColumn get deletedAt => integer().nullable()();

  @override
  List<Set<Column>> get uniqueKeys => [
    {companyId, email},
  ];
}
```

#### Bank Statements Table

```dart
// lib/data/database/tables/bank_statements.dart
import 'package:drift/drift.dart';

@DataClassName('BankStatement')
class BankStatements extends Table {
  IntColumn get id => integer().autoIncrement()();
  TextColumn get uuid => text().unique()();
  IntColumn get companyId => integer().references(Companies, #id)();
  IntColumn get bankId => integer().references(Banks, #id)();
  IntColumn get userId => integer().references(Users, #id)();

  // File info
  TextColumn get filePath => text()();
  TextColumn get fileHash => text().unique()();
  TextColumn get originalFilename => text()();
  IntColumn get fileSize => integer()();

  // OCR status
  TextColumn get ocrStatus => text().withDefault(const Constant('pending'))();
  TextColumn get ocrResponse => text().nullable()(); // JSON
  TextColumn get ocrError => text().nullable()();
  IntColumn get ocrStartedAt => integer().nullable()();
  IntColumn get ocrCompletedAt => integer().nullable()();

  // Statement info
  TextColumn get bankName => text().nullable()();
  TextColumn get accountNumber => text().nullable()();
  TextColumn get accountHolderName => text().nullable()();
  IntColumn get periodFrom => integer().nullable()();
  IntColumn get periodTo => integer().nullable()();
  RealColumn get openingBalance => real().nullable()();
  RealColumn get closingBalance => real().nullable()();

  // Statistics
  IntColumn get totalTransactions => integer().withDefault(const Constant(0))();
  IntColumn get matchedTransactions => integer().withDefault(const Constant(0))();
  IntColumn get unmatchedTransactions => integer().withDefault(const Constant(0))();
  IntColumn get verifiedTransactions => integer().withDefault(const Constant(0))();

  // Matching status
  TextColumn get matchingStatus => text().withDefault(const Constant('pending'))();
  IntColumn get matchingStartedAt => integer().nullable()();
  IntColumn get matchingCompletedAt => integer().nullable()();

  TextColumn get status => text().withDefault(const Constant('active'))();
  IntColumn get uploadedAt => integer()();
  IntColumn get createdAt => integer()();
  IntColumn get updatedAt => integer()();
  IntColumn get deletedAt => integer().nullable()();

  @override
  Set<Column> get primaryKey => {id};

  @override
  List<String> get customConstraints => [
    'CREATE INDEX idx_statements_company ON bank_statements(company_id, deleted_at)',
    'CREATE INDEX idx_statements_status ON bank_statements(company_id, ocr_status, matching_status)',
    'CREATE INDEX idx_statements_period ON bank_statements(company_id, period_from, period_to)',
  ];
}
```

#### Statement Transactions Table

```dart
// lib/data/database/tables/statement_transactions.dart
import 'package:drift/drift.dart';

@DataClassName('StatementTransaction')
class StatementTransactions extends Table {
  IntColumn get id => integer().autoIncrement()();
  TextColumn get uuid => text().unique()();
  IntColumn get companyId => integer().references(Companies, #id)();
  IntColumn get bankStatementId => integer().references(BankStatements, #id, onDelete: KeyAction.cascade)();

  // Transaction data
  IntColumn get transactionDate => integer()();
  TextColumn get description => text()();
  TextColumn get normalizedDescription => text().nullable()();
  TextColumn get referenceNo => text().nullable()();
  RealColumn get debitAmount => real().withDefault(const Constant(0))();
  RealColumn get creditAmount => real().withDefault(const Constant(0))();
  RealColumn get balance => real().nullable()();
  TextColumn get transactionType => text().nullable()(); // debit, credit
  RealColumn get amount => real().nullable()();

  // Categorization
  IntColumn get typeId => integer().nullable().references(Types, #id)();
  IntColumn get categoryId => integer().nullable().references(Categories, #id)();
  IntColumn get subCategoryId => integer().nullable().references(SubCategories, #id)();
  IntColumn get matchedKeywordId => integer().nullable().references(Keywords, #id)();
  IntColumn get confidenceScore => integer().withDefault(const Constant(0))();
  BoolColumn get isManualCategory => boolean().withDefault(const Constant(false))();
  TextColumn get matchingReason => text().nullable()();
  TextColumn get matchMethod => text().nullable()();
  TextColumn get alternativeCategories => text().nullable()(); // JSON

  // Account mapping
  IntColumn get accountId => integer().nullable().references(Accounts, #id)();
  IntColumn get matchedAccountKeywordId => integer().nullable().references(AccountKeywords, #id)();
  IntColumn get accountConfidenceScore => integer().withDefault(const Constant(0))();
  BoolColumn get isManualAccount => boolean().withDefault(const Constant(false))();

  // Verification
  BoolColumn get isVerified => boolean().withDefault(const Constant(false))();
  IntColumn get verifiedBy => integer().nullable().references(Users, #id)();
  IntColumn get verifiedAt => integer().nullable()();
  TextColumn get feedbackStatus => text().nullable()(); // pending, correct, incorrect, partial
  TextColumn get feedbackNotes => text().nullable()();

  IntColumn get createdAt => integer()();
  IntColumn get updatedAt => integer()();
  IntColumn get deletedAt => integer().nullable()();

  @override
  List<String> get customConstraints => [
    'CREATE INDEX idx_transactions_statement ON statement_transactions(bank_statement_id, deleted_at)',
    'CREATE INDEX idx_transactions_date ON statement_transactions(company_id, transaction_date)',
    'CREATE INDEX idx_transactions_category ON statement_transactions(company_id, sub_category_id)',
    'CREATE INDEX idx_transactions_verified ON statement_transactions(company_id, is_verified)',
  ];
}
```

#### Keywords Table

```dart
// lib/data/database/tables/keywords.dart
import 'package:drift/drift.dart';

@DataClassName('Keyword')
class Keywords extends Table {
  IntColumn get id => integer().autoIncrement()();
  TextColumn get uuid => text().unique()();
  IntColumn get companyId => integer().references(Companies, #id)();
  IntColumn get subCategoryId => integer().references(SubCategories, #id)();

  // Matching rules
  TextColumn get keyword => text()();
  BoolColumn get isRegex => boolean().withDefault(const Constant(false))();
  BoolColumn get caseSensitive => boolean().withDefault(const Constant(false))();
  TextColumn get matchType => text().withDefault(const Constant('contains'))(); // exact, contains, starts_with, ends_with, regex
  RealColumn get minAmount => real().nullable()();
  RealColumn get maxAmount => real().nullable()();

  IntColumn get priority => integer().withDefault(const Constant(5))(); // 1-10
  BoolColumn get isActive => boolean().withDefault(const Constant(true))();

  // Learning & statistics
  IntColumn get matchCount => integer().withDefault(const Constant(0))();
  IntColumn get lastMatchedAt => integer().nullable()();
  IntColumn get effectivenessScore => integer().withDefault(const Constant(0))(); // 0-100
  IntColumn get falsePositiveCount => integer().withDefault(const Constant(0))();
  IntColumn get truePositiveCount => integer().withDefault(const Constant(0))();

  IntColumn get createdAt => integer()();
  IntColumn get updatedAt => integer()();
  IntColumn get deletedAt => integer().nullable()();

  @override
  List<String> get customConstraints => [
    'CREATE INDEX idx_keywords_active ON keywords(company_id, is_active, priority)',
    'CREATE INDEX idx_keywords_effectiveness ON keywords(effectiveness_score)',
  ];
}
```

*See complete table definitions for remaining tables in the appendix*

### 1.2 Database Repository Pattern

```dart
// lib/data/repositories/company_repository_impl.dart
import 'package:drift/drift.dart';
import '../../domain/repositories/company_repository.dart';
import '../../domain/entities/company_entity.dart';
import '../database/app_database.dart';

class CompanyRepositoryImpl implements CompanyRepository {
  final AppDatabase _db;

  CompanyRepositoryImpl(this._db);

  @override
  Future<List<CompanyEntity>> getAllCompanies() async {
    final companies = await _db.select(_db.companies).get();
    return companies.map((c) => _mapToEntity(c)).toList();
  }

  @override
  Future<CompanyEntity?> getCompanyById(int id) async {
    final company = await (_db.select(_db.companies)
          ..where((tbl) => tbl.id.equals(id)))
        .getSingleOrNull();

    return company != null ? _mapToEntity(company) : null;
  }

  @override
  Future<int> createCompany(CompanyEntity entity) async {
    return await _db.into(_db.companies).insert(
      CompaniesCompanion.insert(
        uuid: entity.uuid,
        name: entity.name,
        slug: Value(entity.slug),
        logoPath: Value(entity.logoPath),
        settings: Value(entity.settingsJson),
        status: entity.status,
        createdAt: DateTime.now().millisecondsSinceEpoch,
        updatedAt: DateTime.now().millisecondsSinceEpoch,
      ),
    );
  }

  @override
  Future<bool> updateCompany(int id, CompanyEntity entity) async {
    return await _db.update(_db.companies).replace(
      Company(
        id: id,
        uuid: entity.uuid,
        name: entity.name,
        slug: entity.slug,
        logoPath: entity.logoPath,
        settings: entity.settingsJson,
        status: entity.status,
        createdAt: entity.createdAt.millisecondsSinceEpoch,
        updatedAt: DateTime.now().millisecondsSinceEpoch,
        deletedAt: null,
      ),
    );
  }

  @override
  Future<bool> deleteCompany(int id) async {
    // Soft delete
    return await (_db.update(_db.companies)
          ..where((tbl) => tbl.id.equals(id)))
        .write(
      CompaniesCompanion(
        deletedAt: Value(DateTime.now().millisecondsSinceEpoch),
      ),
    ) >
        0;
  }

  @override
  Stream<List<CompanyEntity>> watchAllCompanies() {
    return _db
        .select(_db.companies)
        .watch()
        .map((rows) => rows.map((r) => _mapToEntity(r)).toList());
  }

  CompanyEntity _mapToEntity(Company company) {
    return CompanyEntity(
      id: company.id,
      uuid: company.uuid,
      name: company.name,
      slug: company.slug,
      logoPath: company.logoPath,
      settingsJson: company.settings,
      status: company.status,
      createdAt: DateTime.fromMillisecondsSinceEpoch(company.createdAt),
      updatedAt: DateTime.fromMillisecondsSinceEpoch(company.updatedAt),
    );
  }
}
```

---

## 2. Authentication System

### 2.1 Password Hashing Service

```dart
// lib/services/auth/password_service.dart
import 'package:bcrypt/bcrypt.dart';

class PasswordService {
  /// Hash a password using bcrypt
  static String hashPassword(String password) {
    return BCrypt.hashpw(password, BCrypt.gensalt());
  }

  /// Verify password against hash
  static bool verifyPassword(String password, String hash) {
    return BCrypt.checkpw(password, hash);
  }

  /// Validate password strength
  static bool isPasswordStrong(String password) {
    // At least 8 characters
    if (password.length < 8) return false;

    // Contains uppercase
    if (!password.contains(RegExp(r'[A-Z]'))) return false;

    // Contains lowercase
    if (!password.contains(RegExp(r'[a-z]'))) return false;

    // Contains number
    if (!password.contains(RegExp(r'[0-9]'))) return false;

    return true;
  }

  /// Generate validation error message
  static String? validatePassword(String password) {
    if (password.isEmpty) return 'Password cannot be empty';
    if (password.length < 8) return 'Password must be at least 8 characters';
    if (!password.contains(RegExp(r'[A-Z]')))
      return 'Password must contain uppercase letter';
    if (!password.contains(RegExp(r'[a-z]')))
      return 'Password must contain lowercase letter';
    if (!password.contains(RegExp(r'[0-9]')))
      return 'Password must contain number';
    return null;
  }
}
```

### 2.2 Authentication State Management

```dart
// lib/presentation/providers/auth_provider.dart
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../domain/entities/user_entity.dart';
import '../../domain/usecases/auth/login_usecase.dart';
import '../../domain/usecases/auth/logout_usecase.dart';

// Auth state
class AuthState {
  final UserEntity? user;
  final bool isAuthenticated;
  final bool isLoading;
  final String? error;

  AuthState({
    this.user,
    this.isAuthenticated = false,
    this.isLoading = false,
    this.error,
  });

  AuthState copyWith({
    UserEntity? user,
    bool? isAuthenticated,
    bool? isLoading,
    String? error,
  }) {
    return AuthState(
      user: user ?? this.user,
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

// Auth notifier
class AuthNotifier extends StateNotifier<AuthState> {
  final LoginUseCase _loginUseCase;
  final LogoutUseCase _logoutUseCase;
  final SharedPreferences _prefs;

  AuthNotifier(
    this._loginUseCase,
    this._logoutUseCase,
    this._prefs,
  ) : super(AuthState()) {
    _checkAuthStatus();
  }

  Future<void> _checkAuthStatus() async {
    final userId = _prefs.getInt('user_id');
    if (userId != null) {
      // Auto-login if session exists
      // Load user data from database
      final result = await _loginUseCase.getUserById(userId);
      result.fold(
        (failure) => state = AuthState(),
        (user) => state = AuthState(
          user: user,
          isAuthenticated: true,
        ),
      );
    }
  }

  Future<void> login(String email, String password, int companyId) async {
    state = state.copyWith(isLoading: true, error: null);

    final result = await _loginUseCase(
      email: email,
      password: password,
      companyId: companyId,
    );

    result.fold(
      (failure) {
        state = state.copyWith(
          isLoading: false,
          error: failure.message,
        );
      },
      (user) async {
        // Save session
        await _prefs.setInt('user_id', user.id);
        await _prefs.setInt('company_id', user.companyId);

        // Update last login
        await _updateLastLogin(user.id);

        state = AuthState(
          user: user,
          isAuthenticated: true,
        );
      },
    );
  }

  Future<void> _updateLastLogin(int userId) async {
    // Update user's last login timestamp
    // Implementation in repository
  }

  Future<void> logout() async {
    await _prefs.remove('user_id');
    await _prefs.remove('company_id');

    await _logoutUseCase();

    state = AuthState();
  }

  Future<void> changePassword(String oldPassword, String newPassword) async {
    // Implement password change logic
  }
}

// Provider
final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(
    ref.watch(loginUseCaseProvider),
    ref.watch(logoutUseCaseProvider),
    ref.watch(sharedPreferencesProvider),
  );
});
```

### 2.3 Login Screen

```dart
// lib/presentation/screens/auth/login_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gap/gap.dart';
import '../../providers/auth_provider.dart';
import '../../providers/company_provider.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({Key? key}) : super(key: key);

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  int? _selectedCompanyId;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedCompanyId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a company')),
      );
      return;
    }

    await ref.read(authProvider.notifier).login(
          _emailController.text,
          _passwordController.text,
          _selectedCompanyId!,
        );

    final authState = ref.read(authProvider);
    if (authState.isAuthenticated) {
      // Navigate to dashboard
      if (mounted) {
        Navigator.of(context).pushReplacementNamed('/dashboard');
      }
    } else if (authState.error != null) {
      // Show error
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(authState.error!)),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final companiesAsync = ref.watch(companiesProvider);

    return Scaffold(
      body: Center(
        child: Container(
          constraints: const BoxConstraints(maxWidth: 400),
          padding: const EdgeInsets.all(32),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Logo
                Image.asset(
                  'assets/images/logo.png',
                  height: 80,
                ),
                const Gap(32),

                // Title
                Text(
                  'MatchFinance',
                  style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                  textAlign: TextAlign.center,
                ),
                Text(
                  'Bank Statement Processing',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Colors.grey,
                      ),
                  textAlign: TextAlign.center,
                ),
                const Gap(48),

                // Company selection
                companiesAsync.when(
                  data: (companies) {
                    return DropdownButtonFormField<int>(
                      decoration: const InputDecoration(
                        labelText: 'Company',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.business),
                      ),
                      value: _selectedCompanyId,
                      items: companies.map((company) {
                        return DropdownMenuItem(
                          value: company.id,
                          child: Text(company.name),
                        );
                      }).toList(),
                      onChanged: (value) {
                        setState(() {
                          _selectedCompanyId = value;
                        });
                      },
                      validator: (value) {
                        if (value == null) return 'Please select company';
                        return null;
                      },
                    );
                  },
                  loading: () => const LinearProgressIndicator(),
                  error: (err, stack) => Text('Error: $err'),
                ),
                const Gap(16),

                // Email field
                TextFormField(
                  controller: _emailController,
                  decoration: const InputDecoration(
                    labelText: 'Email',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.email),
                  ),
                  keyboardType: TextInputType.emailAddress,
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter email';
                    }
                    if (!value.contains('@')) {
                      return 'Please enter valid email';
                    }
                    return null;
                  },
                ),
                const Gap(16),

                // Password field
                TextFormField(
                  controller: _passwordController,
                  decoration: const InputDecoration(
                    labelText: 'Password',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.lock),
                  ),
                  obscureText: true,
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter password';
                    }
                    return null;
                  },
                ),
                const Gap(24),

                // Login button
                ElevatedButton(
                  onPressed: authState.isLoading ? null : _handleLogin,
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.all(16),
                  ),
                  child: authState.isLoading
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Login'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
```

### 2.4 Login UseCase

```dart
// lib/domain/usecases/auth/login_usecase.dart
import 'package:dartz/dartz.dart';
import '../../entities/user_entity.dart';
import '../../repositories/auth_repository.dart';
import '../../../core/errors/failures.dart';
import '../../../services/auth/password_service.dart';

class LoginUseCase {
  final AuthRepository repository;

  LoginUseCase(this.repository);

  Future<Either<Failure, UserEntity>> call({
    required String email,
    required String password,
    required int companyId,
  }) async {
    try {
      // Get user by email and company
      final user = await repository.getUserByEmail(email, companyId);

      if (user == null) {
        return Left(AuthFailure('Invalid email or password'));
      }

      // Check if user is active
      if (!user.isActive) {
        return Left(AuthFailure('Account is inactive'));
      }

      // Verify password
      final isValid = PasswordService.verifyPassword(password, user.passwordHash);

      if (!isValid) {
        return Left(AuthFailure('Invalid email or password'));
      }

      return Right(user);
    } catch (e) {
      return Left(AuthFailure('Login failed: ${e.toString()}'));
    }
  }

  Future<Either<Failure, UserEntity>> getUserById(int userId) async {
    try {
      final user = await repository.getUserById(userId);
      if (user == null) {
        return Left(AuthFailure('User not found'));
      }
      return Right(user);
    } catch (e) {
      return Left(AuthFailure('Failed to get user: ${e.toString()}'));
    }
  }
}
```

---

## 3. Master Data Management

### 3.1 Keywords Management with CRUD

```dart
// lib/presentation/screens/master_data/keywords_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:data_table_2/data_table_2.dart';
import '../../providers/keyword_provider.dart';
import '../../widgets/master_data/keyword_form_dialog.dart';

class KeywordsScreen extends ConsumerStatefulWidget {
  const KeywordsScreen({Key? key}) : super(key: key);

  @override
  ConsumerState<KeywordsScreen> createState() => _KeywordsScreenState();
}

class _KeywordsScreenState extends ConsumerState<KeywordsScreen> {
  String _searchQuery = '';
  int? _filterSubCategoryId;

  @override
  Widget build(BuildContext context) {
    final keywordsAsync = ref.watch(keywordsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Keywords Management'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _showKeywordDialog(context),
          ),
        ],
      ),
      body: Column(
        children: [
          // Search and filter bar
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    decoration: const InputDecoration(
                      hintText: 'Search keywords...',
                      prefixIcon: Icon(Icons.search),
                      border: OutlineInputBorder(),
                    ),
                    onChanged: (value) {
                      setState(() {
                        _searchQuery = value;
                      });
                    },
                  ),
                ),
                const SizedBox(width: 16),
                // Sub-category filter dropdown
                // ... implementation
              ],
            ),
          ),

          // Data table
          Expanded(
            child: keywordsAsync.when(
              data: (keywords) {
                // Apply filters
                var filtered = keywords.where((k) {
                  if (_searchQuery.isNotEmpty &&
                      !k.keyword.toLowerCase().contains(_searchQuery.toLowerCase())) {
                    return false;
                  }
                  if (_filterSubCategoryId != null &&
                      k.subCategoryId != _filterSubCategoryId) {
                    return false;
                  }
                  return true;
                }).toList();

                if (filtered.isEmpty) {
                  return const Center(child: Text('No keywords found'));
                }

                return DataTable2(
                  columnSpacing: 12,
                  horizontalMargin: 12,
                  minWidth: 900,
                  columns: const [
                    DataColumn2(label: Text('Keyword'), size: ColumnSize.L),
                    DataColumn2(label: Text('Sub-Category'), size: ColumnSize.M),
                    DataColumn2(label: Text('Match Type'), size: ColumnSize.S),
                    DataColumn2(label: Text('Priority'), size: ColumnSize.S),
                    DataColumn2(label: Text('Matches'), size: ColumnSize.S),
                    DataColumn2(label: Text('Effectiveness'), size: ColumnSize.S),
                    DataColumn2(label: Text('Active'), size: ColumnSize.S),
                    DataColumn2(label: Text('Actions'), size: ColumnSize.M),
                  ],
                  rows: filtered.map((keyword) {
                    return DataRow2(
                      cells: [
                        DataCell(
                          Row(
                            children: [
                              if (keyword.isRegex)
                                const Icon(Icons.code, size: 16, color: Colors.orange),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  keyword.keyword,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ),
                        DataCell(Text(keyword.subCategoryName ?? '')),
                        DataCell(Text(keyword.matchType.toUpperCase())),
                        DataCell(
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: _getPriorityColor(keyword.priority),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              keyword.priority.toString(),
                              style: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ),
                        DataCell(Text(keyword.matchCount.toString())),
                        DataCell(
                          LinearProgressIndicator(
                            value: keyword.effectivenessScore / 100,
                            backgroundColor: Colors.grey[300],
                          ),
                        ),
                        DataCell(
                          Switch(
                            value: keyword.isActive,
                            onChanged: (value) {
                              ref
                                  .read(keywordProvider.notifier)
                                  .toggleActive(keyword.id);
                            },
                          ),
                        ),
                        DataCell(
                          Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              IconButton(
                                icon: const Icon(Icons.edit, size: 20),
                                onPressed: () => _showKeywordDialog(
                                  context,
                                  keyword: keyword,
                                ),
                              ),
                              IconButton(
                                icon: const Icon(Icons.delete, size: 20),
                                onPressed: () => _deleteKeyword(keyword.id),
                              ),
                            ],
                          ),
                        ),
                      ],
                    );
                  }).toList(),
                );
              },
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (err, stack) => Center(child: Text('Error: $err')),
            ),
          ),
        ],
      ),
    );
  }

  Color _getPriorityColor(int priority) {
    if (priority >= 8) return Colors.red;
    if (priority >= 6) return Colors.orange;
    if (priority >= 4) return Colors.blue;
    return Colors.grey;
  }

  void _showKeywordDialog(BuildContext context, {Keyword? keyword}) {
    showDialog(
      context: context,
      builder: (context) => KeywordFormDialog(keyword: keyword),
    );
  }

  Future<void> _deleteKeyword(int id) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete Keyword'),
        content: const Text('Are you sure you want to delete this keyword?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      await ref.read(keywordProvider.notifier).deleteKeyword(id);
    }
  }
}
```

### 3.2 Keyword Form Dialog

```dart
// lib/presentation/widgets/master_data/keyword_form_dialog.dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:gap/gap.dart';
import '../../../domain/entities/keyword_entity.dart';
import '../../providers/keyword_provider.dart';
import '../../providers/sub_category_provider.dart';

class KeywordFormDialog extends ConsumerStatefulWidget {
  final KeywordEntity? keyword;

  const KeywordFormDialog({Key? key, this.keyword}) : super(key: key);

  @override
  ConsumerState<KeywordFormDialog> createState() => _KeywordFormDialogState();
}

class _KeywordFormDialogState extends ConsumerState<KeywordFormDialog> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _keywordController;
  late int _priority;
  late String _matchType;
  late bool _isRegex;
  late bool _caseSensitive;
  late bool _isActive;
  int? _subCategoryId;

  @override
  void initState() {
    super.initState();
    _keywordController = TextEditingController(text: widget.keyword?.keyword ?? '');
    _priority = widget.keyword?.priority ?? 5;
    _matchType = widget.keyword?.matchType ?? 'contains';
    _isRegex = widget.keyword?.isRegex ?? false;
    _caseSensitive = widget.keyword?.caseSensitive ?? false;
    _isActive = widget.keyword?.isActive ?? true;
    _subCategoryId = widget.keyword?.subCategoryId;
  }

  @override
  void dispose() {
    _keywordController.dispose();
    super.dispose();
  }

  Future<void> _handleSubmit() async {
    if (!_formKey.currentState!.validate()) return;

    final keyword = KeywordEntity(
      id: widget.keyword?.id ?? 0,
      uuid: widget.keyword?.uuid ?? '',
      companyId: ref.read(selectedCompanyIdProvider)!,
      subCategoryId: _subCategoryId!,
      keyword: _keywordController.text,
      isRegex: _isRegex,
      caseSensitive: _caseSensitive,
      matchType: _matchType,
      priority: _priority,
      isActive: _isActive,
      matchCount: widget.keyword?.matchCount ?? 0,
      effectivenessScore: widget.keyword?.effectivenessScore ?? 0,
      createdAt: widget.keyword?.createdAt ?? DateTime.now(),
      updatedAt: DateTime.now(),
    );

    if (widget.keyword == null) {
      await ref.read(keywordProvider.notifier).createKeyword(keyword);
    } else {
      await ref.read(keywordProvider.notifier).updateKeyword(keyword);
    }

    if (mounted) {
      Navigator.pop(context);
    }
  }

  @override
  Widget build(BuildContext context) {
    final subCategoriesAsync = ref.watch(subCategoriesProvider);

    return Dialog(
      child: Container(
        width: 600,
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: ListView(
            shrinkWrap: true,
            children: [
              Text(
                widget.keyword == null ? 'Add Keyword' : 'Edit Keyword',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const Gap(24),

              // Keyword text field
              TextFormField(
                controller: _keywordController,
                decoration: const InputDecoration(
                  labelText: 'Keyword',
                  hintText: 'Enter keyword or regex pattern',
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter keyword';
                  }
                  return null;
                },
              ),
              const Gap(16),

              // Sub-category dropdown
              subCategoriesAsync.when(
                data: (subCategories) {
                  return DropdownButtonFormField<int>(
                    decoration: const InputDecoration(
                      labelText: 'Sub-Category',
                      border: OutlineInputBorder(),
                    ),
                    value: _subCategoryId,
                    items: subCategories.map((sc) {
                      return DropdownMenuItem(
                        value: sc.id,
                        child: Text(sc.name),
                      );
                    }).toList(),
                    onChanged: (value) {
                      setState(() {
                        _subCategoryId = value;
                      });
                    },
                    validator: (value) {
                      if (value == null) return 'Please select sub-category';
                      return null;
                    },
                  );
                },
                loading: () => const LinearProgressIndicator(),
                error: (err, stack) => Text('Error: $err'),
              ),
              const Gap(16),

              // Match type dropdown
              DropdownButtonFormField<String>(
                decoration: const InputDecoration(
                  labelText: 'Match Type',
                  border: OutlineInputBorder(),
                ),
                value: _matchType,
                items: const [
                  DropdownMenuItem(value: 'exact', child: Text('Exact Match')),
                  DropdownMenuItem(value: 'contains', child: Text('Contains')),
                  DropdownMenuItem(value: 'starts_with', child: Text('Starts With')),
                  DropdownMenuItem(value: 'ends_with', child: Text('Ends With')),
                  DropdownMenuItem(value: 'regex', child: Text('Regex')),
                ],
                onChanged: (value) {
                  setState(() {
                    _matchType = value!;
                    if (_matchType == 'regex') {
                      _isRegex = true;
                    }
                  });
                },
              ),
              const Gap(16),

              // Priority slider
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Priority: $_priority',
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                  Slider(
                    value: _priority.toDouble(),
                    min: 1,
                    max: 10,
                    divisions: 9,
                    label: _priority.toString(),
                    onChanged: (value) {
                      setState(() {
                        _priority = value.toInt();
                      });
                    },
                  ),
                ],
              ),
              const Gap(16),

              // Switches
              SwitchListTile(
                title: const Text('Regex Pattern'),
                subtitle: const Text('Use regular expression matching'),
                value: _isRegex,
                onChanged: (value) {
                  setState(() {
                    _isRegex = value;
                  });
                },
              ),
              SwitchListTile(
                title: const Text('Case Sensitive'),
                subtitle: const Text('Match with exact case'),
                value: _caseSensitive,
                onChanged: (value) {
                  setState(() {
                    _caseSensitive = value;
                  });
                },
              ),
              SwitchListTile(
                title: const Text('Active'),
                subtitle: const Text('Enable this keyword for matching'),
                value: _isActive,
                onChanged: (value) {
                  setState(() {
                    _isActive = value;
                  });
                },
              ),
              const Gap(24),

              // Action buttons
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Cancel'),
                  ),
                  const Gap(8),
                  ElevatedButton(
                    onPressed: _handleSubmit,
                    child: Text(widget.keyword == null ? 'Add' : 'Update'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

---

## 4. PDF Upload & OCR Processing

### 4.1 OCR Service with Tesseract

```dart
// lib/services/ocr/tesseract_ocr_service.dart
import 'dart:io';
import 'dart:isolate';
import 'package:path_provider/path_provider.dart';
import 'package:syncfusion_flutter_pdf/pdf.dart';
import 'package:image/image.dart' as img;
import 'package:path/path.dart' as path;

// For Windows: tesseract_ocr_windows package
// For macOS/Linux: FFI bindings or process call

class TesseractOcrService {
  /// Convert PDF to images
  Future<List<File>> pdfToImages(File pdfFile) async {
    final document = PdfDocument(inputBytes: await pdfFile.readAsBytes());
    final tempDir = await getTemporaryDirectory();
    final imageFiles = <File>[];

    for (int i = 0; i < document.pages.count; i++) {
      final page = document.pages[i];
      final image = await page.toImage();
      final bytes = await image.toByteData(format: ImageByteFormat.png);

      final imageFile = File(
        path.join(tempDir.path, 'page_${i + 1}.png'),
      );
      await imageFile.writeAsBytes(
        bytes!.buffer.asUint8List(),
      );

      imageFiles.add(imageFile);
    }

    document.dispose();
    return imageFiles;
  }

  /// Preprocess image for better OCR
  Future<File> preprocessImage(File imageFile) async {
    final bytes = await imageFile.readAsBytes();
    final image = img.decodeImage(bytes);

    if (image == null) throw Exception('Failed to decode image');

    // Convert to grayscale
    var processed = img.grayscale(image);

    // Increase contrast
    processed = img.contrast(processed, contrast: 150);

    // Normalize
    processed = img.normalize(processed, min: 0, max: 255);

    // Optional: Denoise
    // processed = img.gaussianBlur(processed, radius: 1);

    // Save processed image
    final processedFile = File('${imageFile.path}_processed.png');
    await processedFile.writeAsBytes(img.encodePng(processed));

    return processedFile;
  }

  /// Run OCR on image
  Future<String> extractText(File imageFile) async {
    // Preprocess image
    final processed = await preprocessImage(imageFile);

    // Platform-specific OCR implementation
    if (Platform.isWindows) {
      return await _extractTextWindows(processed);
    } else if (Platform.isMacOS || Platform.isLinux) {
      return await _extractTextUnix(processed);
    } else {
      throw UnsupportedError('Unsupported platform');
    }
  }

  Future<String> _extractTextWindows(File imageFile) async {
    // Using tesseract_ocr_windows package
    // NOTE: Package needs to be added to pubspec.yaml

    // Example implementation (pseudo-code):
    // final tesseract = Tesseract();
    // await tesseract.init('eng'); // English language
    // final text = await tesseract.readText(imageFile.path);
    // tesseract.dispose();
    // return text;

    // For now, using Process to call tesseract.exe
    final result = await Process.run(
      'tesseract',
      [imageFile.path, 'stdout', '-l', 'eng'],
    );

    if (result.exitCode != 0) {
      throw Exception('OCR failed: ${result.stderr}');
    }

    return result.stdout as String;
  }

  Future<String> _extractTextUnix(File imageFile) async {
    // Call tesseract via process
    final result = await Process.run(
      'tesseract',
      [imageFile.path, 'stdout', '-l', 'eng'],
    );

    if (result.exitCode != 0) {
      throw Exception('OCR failed: ${result.stderr}');
    }

    return result.stdout as String;
  }

  /// Process entire PDF in isolate
  static Future<OcrResult> processPdfInBackground(File pdfFile) async {
    return await Isolate.run(() async {
      final service = TesseractOcrService();

      // Convert PDF to images
      final imageFiles = await service.pdfToImages(pdfFile);

      // Extract text from each page
      final pages = <String>[];
      for (final imageFile in imageFiles) {
        final text = await service.extractText(imageFile);
        pages.add(text);

        // Clean up temp file
        await imageFile.delete();
      }

      return OcrResult(
        pageCount: pages.length,
        pages: pages,
        fullText: pages.join('\n'),
      );
    });
  }
}

class OcrResult {
  final int pageCount;
  final List<String> pages;
  final String fullText;

  OcrResult({
    required this.pageCount,
    required this.pages,
    required this.fullText,
  });
}
```

### 4.2 Alternative: Google ML Kit OCR

```dart
// lib/services/ocr/mlkit_ocr_service.dart
import 'dart:io';
import 'package:google_mlkit_text_recognition/google_mlkit_text_recognition.dart';
import 'package:syncfusion_flutter_pdf/pdf.dart';

class MLKitOcrService {
  final textRecognizer = TextRecognizer(script: TextRecognitionScript.latin);

  Future<OcrResult> processPdf(File pdfFile) async {
    // Convert PDF to images
    final imageFiles = await _pdfToImages(pdfFile);

    // Process each image
    final pages = <String>[];
    for (final imageFile in imageFiles) {
      final inputImage = InputImage.fromFile(imageFile);
      final recognizedText = await textRecognizer.processImage(inputImage);

      pages.add(recognizedText.text);

      // Clean up
      await imageFile.delete();
    }

    return OcrResult(
      pageCount: pages.length,
      pages: pages,
      fullText: pages.join('\n'),
    );
  }

  Future<List<File>> _pdfToImages(File pdfFile) async {
    // Same as Tesseract implementation
    // ...
  }

  void dispose() {
    textRecognizer.close();
  }
}
```

### 4.3 Bank Parser Factory

```dart
// lib/services/parsers/bank_parser_factory.dart
import 'base_bank_parser.dart';
import 'bca_parser.dart';
import 'mandiri_parser.dart';
import 'bni_parser.dart';
import 'bri_parser.dart';
import 'btn_parser.dart';
import 'cimb_parser.dart';

class BankParserFactory {
  static BaseBankParser getParser(String bankCode) {
    switch (bankCode.toUpperCase()) {
      case 'BCA':
        return BCAParser();
      case 'MANDIRI':
        return MandiriParser();
      case 'BNI':
        return BNIParser();
      case 'BRI':
        return BRIParser();
      case 'BTN':
        return BTNParser();
      case 'CIMB':
        return CIMBParser();
      default:
        throw UnsupportedError('Unsupported bank: $bankCode');
    }
  }

  static String detectBank(String text) {
    text = text.toLowerCase();

    if (text.contains('bca') || text.contains('bank central asia')) {
      return 'BCA';
    }
    if (text.contains('mandiri') || text.contains('bank mandiri')) {
      return 'MANDIRI';
    }
    if (text.contains('bni') || text.contains('bank negara indonesia')) {
      return 'BNI';
    }
    if (text.contains('bri') || text.contains('bank rakyat indonesia')) {
      return 'BRI';
    }
    if (text.contains('btn') || text.contains('bank tabungan negara')) {
      return 'BTN';
    }
    if (text.contains('cimb') || text.contains('cimb niaga')) {
      return 'CIMB';
    }

    throw Exception('Unable to detect bank from PDF content');
  }
}
```

### 4.4 Base Bank Parser

```dart
// lib/services/parsers/base_bank_parser.dart
abstract class BaseBankParser {
  /// Parse OCR text and extract transactions
  ParseResult parse(String ocrText);

  /// Extract account number
  String? extractAccountNumber(String text);

  /// Extract account holder name
  String? extractAccountHolderName(String text);

  /// Extract statement period
  ({DateTime? from, DateTime? to}) extractPeriod(String text);

  /// Extract opening balance
  double? extractOpeningBalance(String text);

  /// Extract closing balance
  double? extractClosingBalance(String text);

  /// Extract transaction rows
  List<TransactionRow> extractTransactions(String text);

  /// Parse date string to DateTime
  DateTime? parseDate(String dateStr);

  /// Parse amount string to double
  double? parseAmount(String amountStr) {
    // Remove non-numeric characters except dot and comma
    final cleaned = amountStr.replaceAll(RegExp(r'[^\d,.-]'), '');

    // Handle Indonesian format (1.000.000,00) → 1000000.00
    final normalized = cleaned.replaceAll('.', '').replaceAll(',', '.');

    return double.tryParse(normalized);
  }
}

class ParseResult {
  final String? accountNumber;
  final String? accountHolderName;
  final DateTime? periodFrom;
  final DateTime? periodTo;
  final double? openingBalance;
  final double? closingBalance;
  final List<TransactionRow> transactions;

  ParseResult({
    this.accountNumber,
    this.accountHolderName,
    this.periodFrom,
    this.periodTo,
    this.openingBalance,
    this.closingBalance,
    required this.transactions,
  });
}

class TransactionRow {
  final DateTime date;
  final String description;
  final String? referenceNo;
  final double? debitAmount;
  final double? creditAmount;
  final double? balance;
  final String? branchCode;

  TransactionRow({
    required this.date,
    required this.description,
    this.referenceNo,
    this.debitAmount,
    this.creditAmount,
    this.balance,
    this.branchCode,
  });

  String get transactionType {
    if (debitAmount != null && debitAmount! > 0) return 'debit';
    if (creditAmount != null && creditAmount! > 0) return 'credit';
    return 'unknown';
  }

  double get amount {
    return (debitAmount ?? 0) + (creditAmount ?? 0);
  }
}
```

### 4.5 BCA Parser Example

```dart
// lib/services/parsers/bca_parser.dart
import 'base_bank_parser.dart';
import 'package:intl/intl.dart';

class BCAParser extends BaseBankParser {
  @override
  ParseResult parse(String ocrText) {
    return ParseResult(
      accountNumber: extractAccountNumber(ocrText),
      accountHolderName: extractAccountHolderName(ocrText),
      periodFrom: extractPeriod(ocrText).$1,
      periodTo: extractPeriod(ocrText).$2,
      openingBalance: extractOpeningBalance(ocrText),
      closingBalance: extractClosingBalance(ocrText),
      transactions: extractTransactions(ocrText),
    );
  }

  @override
  String? extractAccountNumber(String text) {
    // BCA account format: 10 digits
    final regex = RegExp(r'(?:No\.|Nomor)\s*(?:Rekening|Account)\s*:?\s*(\d{10})');
    final match = regex.firstMatch(text);
    return match?.group(1);
  }

  @override
  String? extractAccountHolderName(String text) {
    // Look for "Nama:" or "Name:" pattern
    final regex = RegExp(r'(?:Nama|Name)\s*:?\s*([A-Z\s]+)');
    final match = regex.firstMatch(text);
    return match?.group(1)?.trim();
  }

  @override
  ({DateTime? $1, DateTime? $2}) extractPeriod(String text) {
    // BCA format: "Periode: 01 Jan 2024 - 31 Jan 2024"
    final regex = RegExp(
      r'Periode[:\s]+(\d{2}\s+[A-Za-z]+\s+\d{4})\s*-\s*(\d{2}\s+[A-Za-z]+\s+\d{4})',
    );
    final match = regex.firstMatch(text);

    if (match == null) return (null, null);

    return (
      parseDate(match.group(1)!),
      parseDate(match.group(2)!),
    );
  }

  @override
  double? extractOpeningBalance(String text) {
    final regex = RegExp(r'Saldo\s+Awal\s*:?\s*([\d,\.]+)');
    final match = regex.firstMatch(text);
    if (match == null) return null;
    return parseAmount(match.group(1)!);
  }

  @override
  double? extractClosingBalance(String text) {
    final regex = RegExp(r'Saldo\s+Akhir\s*:?\s*([\d,\.]+)');
    final match = regex.firstMatch(text);
    if (match == null) return null;
    return parseAmount(match.group(1)!);
  }

  @override
  List<TransactionRow> extractTransactions(String text) {
    final transactions = <TransactionRow>[];

    // BCA transaction line pattern:
    // 05/01 TRANSFER KE 1234567890 100.000,00 CR 1.000.000,00
    // Format: DATE DESCRIPTION AMOUNT TYPE BALANCE

    final lines = text.split('\n');
    final transactionRegex = RegExp(
      r'(\d{2}/\d{2})\s+(.+?)\s+([\d,\.]+)\s+(DB|CR)\s+([\d,\.]+)',
    );

    for (final line in lines) {
      final match = transactionRegex.firstMatch(line);
      if (match == null) continue;

      final dateStr = match.group(1)!;
      final description = match.group(2)!.trim();
      final amountStr = match.group(3)!;
      final type = match.group(4)!;
      final balanceStr = match.group(5)!;

      final date = parseDate(dateStr);
      if (date == null) continue;

      final amount = parseAmount(amountStr);
      final balance = parseAmount(balanceStr);

      transactions.add(
        TransactionRow(
          date: date,
          description: description,
          debitAmount: type == 'DB' ? amount : null,
          creditAmount: type == 'CR' ? amount : null,
          balance: balance,
        ),
      );
    }

    return transactions;
  }

  @override
  DateTime? parseDate(String dateStr) {
    // BCA formats:
    // 1. "05/01" (day/month, year from period)
    // 2. "05 Jan 2024" (full date)

    try {
      if (dateStr.contains('/')) {
        // Format: 05/01
        final parts = dateStr.split('/');
        final day = int.parse(parts[0]);
        final month = int.parse(parts[1]);
        // Assume current year or year from context
        return DateTime(DateTime.now().year, month, day);
      } else {
        // Format: 05 Jan 2024
        final formats = [
          DateFormat('dd MMM yyyy'),
          DateFormat('dd MMMM yyyy'),
        ];

        for (final format in formats) {
          try {
            return format.parse(dateStr);
          } catch (e) {
            continue;
          }
        }
      }
    } catch (e) {
      return null;
    }

    return null;
  }
}
```

*Implementasikan parser serupa untuk bank lainnya (Mandiri, BNI, BRI, BTN, CIMB) mengikuti format spesifik masing-masing bank.*

---

## 5. Transaction Matching Algorithm

### 5.1 Complete Matching Service

```dart
// lib/services/matching/transaction_matching_service.dart
import 'dart:math';
import 'package:string_similarity/string_similarity.dart';
import '../../data/database/app_database.dart';

class TransactionMatchingService {
  final AppDatabase _db;

  TransactionMatchingService(this._db);

  /// Match a single transaction against all keywords
  Future<MatchResult> matchTransaction(
    String description,
    List<Keyword> keywords,
  ) async {
    // Normalize description
    final normalized = normalizeText(description);

    // Sort keywords by priority (highest first)
    final sortedKeywords = List<Keyword>.from(keywords)
      ..sort((a, b) => b.priority.compareTo(a.priority));

    MatchResult? bestMatch;
    final alternatives = <AlternativeMatch>[];

    for (final keyword in sortedKeywords) {
      final result = await _checkKeywordMatch(normalized, keyword);

      if (result != null) {
        if (bestMatch == null || result.confidence > bestMatch.confidence) {
          if (bestMatch != null) {
            alternatives.add(AlternativeMatch(
              keyword: bestMatch.keyword,
              confidence: bestMatch.confidence,
            ));
          }
          bestMatch = result;
        } else {
          alternatives.add(AlternativeMatch(
            keyword: result.keyword,
            confidence: result.confidence,
          ));
        }
      }
    }

    // Sort alternatives by confidence
    alternatives.sort((a, b) => b.confidence.compareTo(a.confidence));

    // Take top 5 alternatives
    final topAlternatives = alternatives.take(5).toList();

    if (bestMatch != null) {
      return bestMatch.copyWith(alternatives: topAlternatives);
    }

    return MatchResult(
      isMatched: false,
      confidence: 0,
      alternatives: topAlternatives,
    );
  }

  /// Check if a keyword matches the description
  Future<MatchResult?> _checkKeywordMatch(
    String normalizedDescription,
    Keyword keyword,
  ) async {
    final keywordText = keyword.caseSensitive
        ? keyword.keyword
        : keyword.keyword.toLowerCase();

    final searchText = keyword.caseSensitive
        ? normalizedDescription
        : normalizedDescription.toLowerCase();

    // Apply match type
    bool isMatch = false;
    int baseConfidence = 0;

    switch (keyword.matchType) {
      case 'exact':
        isMatch = searchText == keywordText;
        baseConfidence = 100;
        break;

      case 'contains':
        isMatch = searchText.contains(keywordText);
        if (isMatch) {
          // Calculate confidence based on match position and length ratio
          final matchRatio = keywordText.length / searchText.length;
          baseConfidence = (70 + (matchRatio * 25)).toInt().clamp(70, 95);
        }
        break;

      case 'starts_with':
        isMatch = searchText.startsWith(keywordText);
        baseConfidence = 90;
        break;

      case 'ends_with':
        isMatch = searchText.endsWith(keywordText);
        baseConfidence = 90;
        break;

      case 'regex':
        if (keyword.isRegex) {
          try {
            final regex = RegExp(
              keywordText,
              caseSensitive: keyword.caseSensitive,
            );
            isMatch = regex.hasMatch(searchText);
            baseConfidence = 85; // Regex matches get slightly lower confidence
          } catch (e) {
            // Invalid regex
            return null;
          }
        }
        break;
    }

    // If no match, try fuzzy matching
    if (!isMatch && keyword.matchType != 'regex') {
      final similarity = _calculateSimilarity(searchText, keywordText);
      if (similarity >= 0.80) {
        // 80% similarity threshold
        isMatch = true;
        baseConfidence = (similarity * 85).toInt(); // Max 85 for fuzzy
      }
    }

    if (!isMatch) return null;

    // Calculate final confidence score
    final confidence = _calculateConfidenceScore(
      baseConfidence: baseConfidence,
      priority: keyword.priority,
      matchType: keyword.matchType,
    );

    return MatchResult(
      keyword: keyword,
      isMatched: true,
      confidence: confidence,
      matchMethod: keyword.matchType,
      matchReason: 'Matched with keyword: ${keyword.keyword}',
    );
  }

  /// Calculate final confidence score
  int _calculateConfidenceScore({
    required int baseConfidence,
    required int priority,
    required String matchType,
  }) {
    // Base score from match type
    var score = baseConfidence;

    // Priority bonus (max +15)
    final priorityBonus = ((priority - 5) * 3).clamp(0, 15);
    score += priorityBonus;

    // Regex penalty
    if (matchType == 'regex') {
      score -= 5;
    }

    // Clamp to 0-100
    return score.clamp(0, 100);
  }

  /// Calculate Levenshtein similarity
  double _calculateSimilarity(String str1, String str2) {
    return StringSimilarity.compareTwoStrings(str1, str2);
  }

  /// Alternative: Manual Levenshtein implementation
  int calculateLevenshteinDistance(String s1, String s2) {
    if (s1.isEmpty) return s2.length;
    if (s2.isEmpty) return s1.length;

    final matrix = List.generate(
      s1.length + 1,
      (i) => List.filled(s2.length + 1, 0),
    );

    for (int i = 0; i <= s1.length; i++) {
      matrix[i][0] = i;
    }

    for (int j = 0; j <= s2.length; j++) {
      matrix[0][j] = j;
    }

    for (int i = 1; i <= s1.length; i++) {
      for (int j = 1; j <= s2.length; j++) {
        final cost = s1[i - 1] == s2[j - 1] ? 0 : 1;

        matrix[i][j] = min(
          min(
            matrix[i - 1][j] + 1, // deletion
            matrix[i][j - 1] + 1, // insertion
          ),
          matrix[i - 1][j - 1] + cost, // substitution
        );
      }
    }

    return matrix[s1.length][s2.length];
  }

  /// Normalize text for matching
  String normalizeText(String text) {
    // Remove extra whitespace
    text = text.trim().replaceAll(RegExp(r'\s+'), ' ');

    // Remove special characters (keep alphanumeric and space)
    text = text.replaceAll(RegExp(r'[^\w\s]'), '');

    return text;
  }

  /// Match all transactions in a bank statement
  Future<void> matchAllTransactions(int statementId) async {
    // Get all unmatched transactions
    final transactions = await (_db.select(_db.statementTransactions)
          ..where((tbl) =>
              tbl.bankStatementId.equals(statementId) &
              tbl.subCategoryId.isNull()))
        .get();

    // Load all active keywords
    final companyId = await _getCompanyIdForStatement(statementId);
    final keywords = await (_db.select(_db.keywords)
          ..where((tbl) =>
              tbl.companyId.equals(companyId) & tbl.isActive.equals(true)))
        .get();

    // Match each transaction
    for (final transaction in transactions) {
      await _matchAndUpdateTransaction(transaction, keywords);
    }

    // Update statement statistics
    await _updateStatementStatistics(statementId);
  }

  Future<void> _matchAndUpdateTransaction(
    StatementTransaction transaction,
    List<Keyword> keywords,
  ) async {
    final result = await matchTransaction(transaction.description, keywords);

    if (result.isMatched && result.keyword != null) {
      // Update transaction with match result
      await (_db.update(_db.statementTransactions)
            ..where((tbl) => tbl.id.equals(transaction.id)))
          .write(
        StatementTransactionsCompanion(
          subCategoryId: Value(result.keyword!.subCategoryId),
          categoryId: Value(await _getCategoryId(result.keyword!.subCategoryId)),
          typeId: Value(await _getTypeId(result.keyword!.subCategoryId)),
          matchedKeywordId: Value(result.keyword!.id),
          confidenceScore: Value(result.confidence),
          matchMethod: Value(result.matchMethod),
          matchingReason: Value(result.matchReason),
          alternativeCategories: Value(_encodeAlternatives(result.alternatives)),
        ),
      );

      // Update keyword statistics
      await _updateKeywordStats(result.keyword!.id);

      // Save matching log
      await _saveMatchingLog(transaction.id, result);
    }
  }

  Future<int> _getCompanyIdForStatement(int statementId) async {
    final statement = await (_db.select(_db.bankStatements)
          ..where((tbl) => tbl.id.equals(statementId)))
        .getSingle();
    return statement.companyId;
  }

  Future<int?> _getCategoryId(int subCategoryId) async {
    final subCategory = await (_db.select(_db.subCategories)
          ..where((tbl) => tbl.id.equals(subCategoryId)))
        .getSingle();
    return subCategory.categoryId;
  }

  Future<int?> _getTypeId(int subCategoryId) async {
    final categoryId = await _getCategoryId(subCategoryId);
    if (categoryId == null) return null;

    final category = await (_db.select(_db.categories)
          ..where((tbl) => tbl.id.equals(categoryId)))
        .getSingle();
    return category.typeId;
  }

  Future<void> _updateKeywordStats(int keywordId) async {
    await _db.customUpdate(
      'UPDATE keywords SET match_count = match_count + 1, '
      'last_matched_at = ? WHERE id = ?',
      updates: {_db.keywords},
      updateKind: UpdateKind.update,
    )
        .bind([DateTime.now().millisecondsSinceEpoch, keywordId])
        .execute();
  }

  Future<void> _saveMatchingLog(int transactionId, MatchResult result) async {
    // Save to matching_logs table
    await _db.into(_db.matchingLogs).insert(
          MatchingLogsCompanion.insert(
            uuid: _generateUuid(),
            companyId: 0, // Get from transaction
            statementTransactionId: transactionId,
            keywordId: Value(result.keyword?.id),
            subCategoryId: Value(result.keyword?.subCategoryId),
            confidenceScore: Value(result.confidence),
            isMatched: result.isMatched,
            matchReason: Value(result.matchReason),
            matchMethod: Value(result.matchMethod),
            matchDetails: Value(_encodeMatchDetails(result)),
            createdAt: DateTime.now().millisecondsSinceEpoch,
          ),
        );
  }

  Future<void> _updateStatementStatistics(int statementId) async {
    // Calculate statistics
    final stats = await _db.customSelect(
      '''
      SELECT
        COUNT(*) as total,
        SUM(CASE WHEN sub_category_id IS NOT NULL THEN 1 ELSE 0 END) as matched,
        SUM(CASE WHEN sub_category_id IS NULL THEN 1 ELSE 0 END) as unmatched,
        SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified
      FROM statement_transactions
      WHERE bank_statement_id = ?
      ''',
      variables: [Variable.withInt(statementId)],
    ).getSingle();

    // Update statement
    await (_db.update(_db.bankStatements)
          ..where((tbl) => tbl.id.equals(statementId)))
        .write(
      BankStatementsCompanion(
        totalTransactions: Value(stats.read<int>('total')),
        matchedTransactions: Value(stats.read<int>('matched')),
        unmatchedTransactions: Value(stats.read<int>('unmatched')),
        verifiedTransactions: Value(stats.read<int>('verified')),
        matchingStatus: const Value('completed'),
        matchingCompletedAt: Value(DateTime.now().millisecondsSinceEpoch),
      ),
    );
  }

  String _encodeAlternatives(List<AlternativeMatch> alternatives) {
    // Encode to JSON
    return jsonEncode(alternatives.map((a) => {
          'keyword_id': a.keyword.id,
          'confidence': a.confidence,
        }).toList());
  }

  String _encodeMatchDetails(MatchResult result) {
    return jsonEncode({
      'confidence': result.confidence,
      'method': result.matchMethod,
      'reason': result.matchReason,
    });
  }

  String _generateUuid() {
    return Uuid().v4();
  }
}

// Result classes
class MatchResult {
  final Keyword? keyword;
  final bool isMatched;
  final int confidence;
  final String? matchMethod;
  final String? matchReason;
  final List<AlternativeMatch> alternatives;

  MatchResult({
    this.keyword,
    required this.isMatched,
    required this.confidence,
    this.matchMethod,
    this.matchReason,
    this.alternatives = const [],
  });

  MatchResult copyWith({
    Keyword? keyword,
    bool? isMatched,
    int? confidence,
    String? matchMethod,
    String? matchReason,
    List<AlternativeMatch>? alternatives,
  }) {
    return MatchResult(
      keyword: keyword ?? this.keyword,
      isMatched: isMatched ?? this.isMatched,
      confidence: confidence ?? this.confidence,
      matchMethod: matchMethod ?? this.matchMethod,
      matchReason: matchReason ?? this.matchReason,
      alternatives: alternatives ?? this.alternatives,
    );
  }
}

class AlternativeMatch {
  final Keyword keyword;
  final int confidence;

  AlternativeMatch({
    required this.keyword,
    required this.confidence,
  });
}
```

---

## 6. Account Mapping

*(Sama seperti transaction matching, tapi untuk GL accounts)*

```dart
// lib/services/matching/account_matching_service.dart
import '../../data/database/app_database.dart';
import 'transaction_matching_service.dart'; // Reuse matching logic

class AccountMatchingService {
  final AppDatabase _db;
  final TransactionMatchingService _matchingService;

  AccountMatchingService(this._db)
      : _matchingService = TransactionMatchingService(_db);

  /// Match transaction to GL account
  Future<AccountMatchResult?> matchToAccount(
    StatementTransaction transaction,
    List<AccountKeyword> accountKeywords,
  ) async {
    final description = transaction.description;

    // Reuse matching logic from TransactionMatchingService
    // Convert AccountKeywords to Keyword-like objects and match

    // Implementation similar to transaction matching...

    // Return best account match
    return null; // Implement
  }

  /// Match all transactions to accounts
  Future<void> matchAllToAccounts(int statementId) async {
    // Get categorized transactions
    final transactions = await (_db.select(_db.statementTransactions)
          ..where((tbl) =>
              tbl.bankStatementId.equals(statementId) &
              tbl.subCategoryId.isNotNull()))
        .get();

    // Load active account keywords
    final companyId = await _getCompanyIdForStatement(statementId);
    final accountKeywords = await (_db.select(_db.accountKeywords)
          ..where((tbl) =>
              tbl.companyId.equals(companyId) & tbl.isActive.equals(true)))
        .get();

    // Match each transaction
    for (final transaction in transactions) {
      final result = await matchToAccount(transaction, accountKeywords);

      if (result != null) {
        // Update transaction with account
        await (_db.update(_db.statementTransactions)
              ..where((tbl) => tbl.id.equals(transaction.id)))
            .write(
          StatementTransactionsCompanion(
            accountId: Value(result.accountId),
            matchedAccountKeywordId: Value(result.keywordId),
            accountConfidenceScore: Value(result.confidence),
          ),
        );

        // Save account matching log
        await _saveAccountMatchingLog(transaction.id, result);
      }
    }
  }

  Future<int> _getCompanyIdForStatement(int statementId) async {
    // Implementation
    return 0;
  }

  Future<void> _saveAccountMatchingLog(
    int transactionId,
    AccountMatchResult result,
  ) async {
    // Save to account_matching_logs table
  }
}

class AccountMatchResult {
  final int accountId;
  final int? keywordId;
  final int confidence;

  AccountMatchResult({
    required this.accountId,
    this.keywordId,
    required this.confidence,
  });
}
```

---

## 7. Dashboard & Charts

### 7.1 Dashboard Screen with Charts

```dart
// lib/presentation/screens/dashboard/dashboard_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:gap/gap.dart';
import '../../providers/dashboard_provider.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboardData = ref.watch(dashboardDataProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => ref.refresh(dashboardDataProvider),
          ),
        ],
      ),
      body: dashboardData.when(
        data: (data) => _buildDashboard(context, data),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, stack) => Center(child: Text('Error: $err')),
      ),
    );
  }

  Widget _buildDashboard(BuildContext context, DashboardData data) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Statistics cards
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  title: 'Total Transactions',
                  value: data.totalTransactions.toString(),
                  icon: Icons.receipt,
                  color: Colors.blue,
                ),
              ),
              const Gap(16),
              Expanded(
                child: _StatCard(
                  title: 'Matched',
                  value: data.matchedTransactions.toString(),
                  subtitle: '${data.matchPercentage.toStringAsFixed(1)}%',
                  icon: Icons.check_circle,
                  color: Colors.green,
                ),
              ),
              const Gap(16),
              Expanded(
                child: _StatCard(
                  title: 'Unmatched',
                  value: data.unmatchedTransactions.toString(),
                  icon: Icons.warning,
                  color: Colors.orange,
                ),
              ),
              const Gap(16),
              Expanded(
                child: _StatCard(
                  title: 'Verified',
                  value: data.verifiedTransactions.toString(),
                  icon: Icons.verified,
                  color: Colors.purple,
                ),
              ),
            ],
          ),
          const Gap(32),

          // Charts row
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Pie chart - Category breakdown
              Expanded(
                flex: 1,
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Category Breakdown',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const Gap(24),
                        SizedBox(
                          height: 300,
                          child: PieChart(
                            PieChartData(
                              sections: data.categoryBreakdown.map((cat) {
                                return PieChartSectionData(
                                  value: cat.count.toDouble(),
                                  title: '${cat.percentage.toStringAsFixed(0)}%',
                                  color: cat.color,
                                  radius: 100,
                                  titleStyle: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                  ),
                                );
                              }).toList(),
                              sectionsSpace: 2,
                              centerSpaceRadius: 40,
                            ),
                          ),
                        ),
                        const Gap(16),
                        // Legend
                        ...data.categoryBreakdown.map((cat) {
                          return Padding(
                            padding: const EdgeInsets.symmetric(vertical: 4),
                            child: Row(
                              children: [
                                Container(
                                  width: 16,
                                  height: 16,
                                  color: cat.color,
                                ),
                                const Gap(8),
                                Text(cat.name),
                                const Spacer(),
                                Text('${cat.count}'),
                              ],
                            ),
                          );
                        }),
                      ],
                    ),
                  ),
                ),
              ),
              const Gap(16),

              // Line chart - Transaction timeline
              Expanded(
                flex: 2,
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Transaction Timeline (Last 30 Days)',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const Gap(24),
                        SizedBox(
                          height: 300,
                          child: LineChart(
                            LineChartData(
                              gridData: FlGridData(show: true),
                              titlesData: FlTitlesData(
                                leftTitles: AxisTitles(
                                  sideTitles: SideTitles(showTitles: true),
                                ),
                                bottomTitles: AxisTitles(
                                  sideTitles: SideTitles(
                                    showTitles: true,
                                    getTitlesWidget: (value, meta) {
                                      // Format dates
                                      return Text(value.toInt().toString());
                                    },
                                  ),
                                ),
                                rightTitles: AxisTitles(
                                  sideTitles: SideTitles(showTitles: false),
                                ),
                                topTitles: AxisTitles(
                                  sideTitles: SideTitles(showTitles: false),
                                ),
                              ),
                              borderData: FlBorderData(show: true),
                              lineBarsData: [
                                LineChartBarData(
                                  spots: data.timeline.map((t) {
                                    return FlSpot(
                                      t.day.toDouble(),
                                      t.count.toDouble(),
                                    );
                                  }).toList(),
                                  isCurved: true,
                                  color: Colors.blue,
                                  barWidth: 3,
                                  dotData: FlDotData(show: true),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
          const Gap(32),

          // Recent activity table
          Card(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Recent Bank Statements',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const Gap(16),
                  // DataTable implementation
                  // ...
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String title;
  final String value;
  final String? subtitle;
  final IconData icon;
  final Color color;

  const _StatCard({
    required this.title,
    required this.value,
    this.subtitle,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(icon, color: color, size: 32),
                ),
                const Spacer(),
              ],
            ),
            const Gap(16),
            Text(
              title,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: Colors.grey,
                  ),
            ),
            const Gap(8),
            Text(
              value,
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            if (subtitle != null) ...[
              const Gap(4),
              Text(
                subtitle!,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Colors.grey,
                    ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
```

---

**Document ini sangat panjang. Saya akan continue dengan:**
- Reports & Export (Excel, PDF)
- Backup & Restore
- Performance Optimization
- Testing Examples

Apakah Anda ingin saya lanjutkan dengan membuat file terpisah untuk sections yang tersisa, atau Anda ingin fokus ke topik specific terlebih dahulu?

