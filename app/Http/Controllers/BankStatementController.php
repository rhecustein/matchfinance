<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankStatement;
use App\Models\Company;
use App\Models\DocumentCollection;
use App\Models\DocumentItem;
use App\Models\StatementTransaction;
use App\Models\Keyword;
use App\Models\User;
use App\Jobs\ProcessBankStatementOCR;
use App\Jobs\ProcessAccountMatching;
use App\Jobs\ProcessTransactionMatching;
use App\Services\TransactionMatchingService;
use App\Services\KeywordLearningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * BankStatementController
 * 
 * Controller untuk mengelola Bank Statement dan transaksi
 * 
 * ==========================================
 * AKSES KONTROL
 * ==========================================
 * 
 * SEMUA COMPANY MEMBER (owner, admin, manager, staff, user):
 * ✅ Upload bank statement (create, store)
 * ✅ View bank statements (index, show, download)
 * ✅ Edit/Update bank statement (edit, update)
 * ✅ Delete bank statement (destroy)
 * ✅ Reprocess OCR (reprocess, retryOCR)
 * ✅ Transaction matching (matchTransactions, rematchAll)
 * ✅ Account matching (matchAccounts, rematchAccounts)
 * ✅ Validation (validateView, approveTransaction, rejectTransaction)
 * ✅ Reconciliation (reconcile, unreconcile)
 * ✅ Bulk operations (verifyAllMatched, verifyHighConfidence, bulkApprove)
 * ✅ Statistics (statistics)
 * ✅ SEMUA operasi terkait bank statement
 * 
 * SUPER ADMIN:
 * ✅ Akses ke semua company
 * ✅ Dapat memilih company context
 * 
 * CATATAN PENTING:
 * - User Management (create, edit, delete user) = HANYA Owner/Admin
 * - Master Data (banks, types, categories, keywords) = HANYA Owner/Admin
 * - Subscription Management = HANYA Owner/Admin
 * - Bank Statement & Transaksi = SEMUA user company
 */
class BankStatementController extends Controller
{
    protected TransactionMatchingService $matchingService;
    protected KeywordLearningService $learningService;
    
    public function __construct(
        TransactionMatchingService $matchingService,
        KeywordLearningService $learningService
    ) {
        $this->matchingService = $matchingService;
        $this->learningService = $learningService;
    }

    // ========================================
    // METODE CRUD UTAMA
    // ========================================

    /**
     * Tampilkan daftar bank statements dengan filter
     * 
     * AKSES: Semua company member
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // SUPER ADMIN: Bisa lihat semua statement dari semua company
        if ($user->isSuperAdmin()) {
            $query = BankStatement::with([
                'bank' => function($q) {
                    $q->withoutGlobalScopes()->select('id', 'name', 'code', 'logo');
                },
                'user:id,name',
                'company:id,name'
            ])->latest('uploaded_at');

            // Filter berdasarkan company (khusus super admin)
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
        } else {
            // COMPANY SCOPED QUERY (user biasa)
            $query = BankStatement::where('company_id', $user->company_id)
                ->with([
                    'bank' => function($q) {
                        $q->withoutGlobalScopes()->select('id', 'name', 'code', 'logo');
                    },
                    'user:id,name'
                ])
                ->latest('uploaded_at');
        }

        // Filter berdasarkan bank
        if ($request->filled('bank_id')) {
            $query->where('bank_id', $request->bank_id);
        }

        // Filter berdasarkan status OCR
        if ($request->filled('ocr_status')) {
            $query->where('ocr_status', $request->ocr_status);
        }

        // Filter berdasarkan status rekonsiliasi
        if ($request->filled('is_reconciled')) {
            $query->where('is_reconciled', $request->boolean('is_reconciled'));
        }

        // Filter berdasarkan user yang upload
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter berdasarkan rentang tanggal (periode)
        if ($request->filled('date_from')) {
            $query->where('period_from', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('period_to', '<=', $request->date_to);
        }

        // Pencarian berdasarkan filename, nomor rekening, atau nama pemegang
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('account_holder_name', 'like', "%{$search}%");
            });
        }

        // Dapatkan hasil dengan pagination
        $statements = $query->paginate(20)->withQueryString();

        // Dapatkan daftar bank global (tanpa pembatasan company)
        $banks = Bank::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'logo']);
        
        // Companies (Khusus Super Admin)
        $companies = $user->isSuperAdmin() 
            ? Company::where('status', '!=', 'cancelled')
                ->orderBy('name')
                ->get(['id', 'name'])
            : null;

        // Users untuk filter (dalam scope company)
        $users = $this->getUsersForFilter($user, $request);

        // Opsi status OCR
        $ocrStatuses = [
            'pending' => 'Menunggu',
            'processing' => 'Diproses',
            'completed' => 'Selesai',
            'failed' => 'Gagal'
        ];

        // Hitung statistik
        $stats = $this->calculateStatistics($user, $request);

        return view('bank-statements.index', compact(
            'statements', 
            'banks', 
            'companies', 
            'users', 
            'ocrStatuses',
            'stats'
        ));
    }

    /**
     * Tampilkan form pemilihan company (Khusus Super Admin)
     * 
     * AKSES: Super Admin only
     */
    public function selectCompany()
    {
        $user = auth()->user();
        
        // Hanya super admin yang bisa akses
        abort_unless($user->isSuperAdmin(), 403, 'Akses ditolak. Hanya Super Admin yang dapat memilih company.');
        
        $companies = Company::where('status', 'active')
            ->orderBy('name')
            ->get();
        
        return view('bank-statements.select-company', compact('companies'));
    }

    /**
     * Tampilkan form untuk upload bank statement baru
     * 
     * AKSES: Semua company member
     */
    public function create(Request $request)
    {
        $user = auth()->user();

        // SUPER ADMIN: Harus punya company context
        if ($user->isSuperAdmin()) {
            $companyId = $request->input('company_id');
            
            if (!$companyId) {
                return redirect()->route('bank-statements.select-company')
                    ->with('info', 'Silakan pilih company terlebih dahulu.');
            }
            
            $company = Company::findOrFail($companyId);
            
            $banks = Bank::withoutGlobalScopes()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'logo']);
            
            if ($banks->isEmpty()) {
                return redirect()->route('bank-statements.select-company')
                    ->with('error', 'Tidak ada bank aktif di sistem. Silakan tambahkan bank terlebih dahulu.');
            }
            
            return view('bank-statements.create', compact('banks', 'company'));
        }
        
        // REGULAR USER: Company scoped
        if (!$user->company_id) {
            return redirect()->route('dashboard')
                ->with('error', 'Anda belum terdaftar di company manapun. Silakan hubungi administrator.');
        }
        
        $banks = Bank::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'logo']);
        
        if ($banks->isEmpty()) {
            return redirect()->route('bank-statements.index')
                ->with('error', 'Tidak ada bank tersedia di sistem. Silakan hubungi administrator.');
        }
        
        return view('bank-statements.create', compact('banks'));
    }

    /**
     * Simpan bank statement baru
     * 
     * AKSES: Semua company member
     * PERUBAHAN: Update pemanggilan generateUniqueFilename dengan parameter Bank
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Validasi
        $rules = [
            'bank_id' => 'required|exists:banks,id',
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|mimes:pdf|max:10240',
        ];
        
        if ($user->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }
        
        $validated = $request->validate($rules);

        // Tentukan company_id
        $companyId = $user->isSuperAdmin() 
            ? $validated['company_id'] 
            : $user->company_id;
        
        if (!$companyId) {
            return back()
                ->with('error', 'Company context tidak ditemukan.')
                ->withInput();
        }

        $bank = Bank::withoutGlobalScopes()->findOrFail($request->bank_id);
        
        $uploadedCount = 0;
        $replacedCount = 0;
        $failedFiles = [];

        DB::beginTransaction();

        try {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                
                try {
                    $fileHash = hash_file('sha256', $file->getRealPath());

                    // Cek file yang sudah ada (COMPANY SCOPED)
                    $existingStatement = BankStatement::withTrashed()
                        ->where('company_id', $companyId)
                        ->where('file_hash', $fileHash)
                        ->where('bank_id', $bank->id)
                        ->first();

                    // ✅ PERUBAHAN: Gunakan Bank object sebagai parameter
                    $filename = $this->generateUniqueFilename($bank);

                    $path = $file->storeAs(
                        "companies/{$companyId}/bank-statements/{$bank->slug}/" . date('Y/m'),
                        $filename,
                        'local'
                    );

                    if (!Storage::disk('local')->exists($path)) {
                        throw new \Exception("Gagal menyimpan file: {$originalName}");
                    }

                    if ($existingStatement) {
                        $this->replaceExistingStatement($existingStatement, $file, $path, $fileHash, $originalName, $filename, $bank, $companyId);
                        $replacedCount++;

                        Log::info('Bank statement DIGANTI', [
                            'id' => $existingStatement->id,
                            'company_id' => $companyId,
                            'user_id' => $user->id,
                            'filename' => $originalName,
                            'new_filename' => $filename, // ✅ Log filename baru
                            'bank' => $bank->name,
                        ]);
                    } else {
                        $bankStatement = $this->createNewStatement($bank, $file, $path, $fileHash, $originalName, $companyId);
                        $uploadedCount++;

                        Log::info('Bank statement DIUPLOAD', [
                            'id' => $bankStatement->id,
                            'company_id' => $companyId,
                            'user_id' => $user->id,
                            'filename' => $originalName,
                            'stored_filename' => $filename, // ✅ Log filename baru
                            'bank' => $bank->name,
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error('Gagal upload file individual', [
                        'company_id' => $companyId,
                        'filename' => $originalName,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $failedFiles[] = $originalName;
                }
            }

            DB::commit();

            $message = $this->buildUploadMessage($uploadedCount, $replacedCount, count($failedFiles));

            return redirect()->route('bank-statements.index')
                ->with('success', $message)
                ->with('uploaded_count', $uploadedCount)
                ->with('replaced_count', $replacedCount)
                ->with('failed_files', $failedFiles)
                ->with('queued_count', $uploadedCount + $replacedCount);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Upload bank statement gagal', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
            
            return back()
                ->with('error', 'Upload gagal: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Tampilkan detail bank statement
     * 
     * AKSES: Semua company member
     */
    public function show(BankStatement $bankStatement)
    {
        $user = auth()->user();
        
        // KONTROL AKSES
        if ($user->isSuperAdmin()) {
            $bankStatement->load([
                'bank' => function($q) {
                    $q->withoutGlobalScopes()->select('id', 'name', 'code', 'logo');
                },
                'user:id,name,email',
                'reconciledBy:id,name',
                'company:id,name'
            ]);
        } else {
            // Pastikan user hanya bisa akses bank statement company-nya sendiri
            abort_unless($bankStatement->company_id === $user->company_id, 403, 
                'Anda tidak memiliki izin untuk melihat bank statement ini.');
            
            $bankStatement->load([
                'bank' => function($q) {
                    $q->withoutGlobalScopes()->select('id', 'name', 'code', 'logo');
                },
                'user:id,name,email',
                'reconciledBy:id,name'
            ]);
        }

        // BUILD QUERY DENGAN RELATIONSHIPS
        $query = $bankStatement->transactions()
            ->with([
                'type:id,name',
                'category:id,name,color',
                'subCategory:id,name',
                'account:id,name,code,account_type',
                'verifiedBy:id,name'
            ]);

        // TERAPKAN FILTER
        $this->applyTransactionFilters($query, request());

        // SORTING
        $this->applyTransactionSorting($query, request('sort', 'date-desc'));

        $transactions = $query->paginate(20);

        // STATISTIK
        $statistics = $this->calculateTransactionStatistics($bankStatement);

        return view('bank-statements.show', compact('bankStatement', 'statistics', 'transactions'));
    }

    
    // ========================================
    // 🎯 METODE VALIDATION SYSTEM
    // ========================================

    /**
     * Tampilkan halaman validasi dengan keyword suggestions
     * 
     * AKSES: Semua company member
     */
    public function validateView(BankStatement $bankStatement)
    {
        $user = auth()->user();
        
        // KONTROL AKSES - Semua company member bisa validasi
        abort_unless(
            $user->isSuperAdmin() || $bankStatement->company_id === $user->company_id, 
            403,
            'Anda tidak memiliki izin untuk memvalidasi bank statement ini.'
        );
        
        // Load bank statement relationships
        $bankStatement->load([
            'bank' => function($q) {
                $q->withoutGlobalScopes()->select('id', 'name', 'code', 'logo');
            },
            'user:id,name'
        ]);
        
        // Eager loading sesuai model relationships
        $query = $bankStatement->transactions()
            ->with([
                // Category matching relationships (denormalized)
                'matchedKeyword',
                'matchedKeyword.subCategory',
                'matchedKeyword.subCategory.category',
                'matchedKeyword.subCategory.category.type',
                
                // Direct category relationships
                'type:id,name',
                'category:id,name,color',
                'subCategory:id,name,category_id',
                'subCategory.category:id,name,type_id',
                'subCategory.category.type:id,name',
                
                // Verification
                'verifiedBy:id,name',
                'approvedBy:id,name',
                'rejectedBy:id,name',
                
                // Matching logs dengan full hierarchy
                'matchingLogs' => function($q) {
                    $q->orderByDesc('confidence_score')->limit(5);
                },
                'matchingLogs.keyword',
                'matchingLogs.keyword.subCategory',
                'matchingLogs.keyword.subCategory.category',
                'matchingLogs.keyword.subCategory.category.type',
            ]);
        
        // Terapkan filter
        $filter = request('filter', 'all');
        
        switch ($filter) {
            case 'pending':
                $query->where('is_verified', false);
                break;
            case 'approved':
                $query->where('is_verified', true)
                      ->where('is_approved', true);
                break;
            case 'rejected':
                $query->where('is_rejected', true);
                break;
            case 'high-confidence':
                $query->where('is_verified', false)
                      ->where('confidence_score', '>=', 80);
                break;
            case 'low-confidence':
                $query->where('is_verified', false)
                      ->where(function($q) {
                          $q->where('confidence_score', '<', 50)
                            ->orWhereNull('matched_keyword_id');
                      });
                break;
            case 'no-match':
                $query->where('is_verified', false)
                      ->whereNull('matched_keyword_id');
                break;
        }
        
        // Dapatkan transaksi
        $transactions = $query->orderBy('transaction_date')
                              ->orderBy('transaction_time')
                              ->get();
        
        // Hitung statistik
        $stats = $this->calculateValidationStatistics($bankStatement, $transactions);
        
        return view('bank-statements.validate', compact('bankStatement', 'transactions', 'stats'));
    }

    /**
     * Approve auto suggestion (AJAX)
     * 
     * AKSES: Semua company member
     */
    public function approveTransaction(StatementTransaction $transaction)
    {
        $user = auth()->user();
        
        // KONTROL AKSES - Semua company member bisa approve
        abort_unless(
            $user->isSuperAdmin() || $transaction->company_id === $user->company_id,
            403,
            'Anda tidak memiliki izin untuk meng-approve transaksi ini.'
        );
        
        // Validasi ada suggestion
        if (!$transaction->matched_keyword_id) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada keyword suggestion untuk transaksi ini'
            ], 400);
        }
        
        // Cek jika sudah verified
        if ($transaction->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi sudah diverifikasi sebelumnya'
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // Simpan nilai lama untuk logging
            $oldKeywordId = $transaction->matched_keyword_id;
            $oldConfidence = $transaction->confidence_score;
            
            // Update verification
            $transaction->update([
                'is_verified' => true,
                'is_approved' => true,
                'verified_by' => $user->id,
                'approved_by' => $user->id,
                'verified_at' => now(),
                'is_manual_category' => false,
                'feedback_status' => 'correct',
                'feedback_notes' => 'User meng-approve primary suggestion',
                'feedback_by' => $user->id,
                'feedback_at' => now(),
            ]);
            
            // Update statistik bank statement
            $transaction->bankStatement->increment('verified_transactions');
            
            // LEARNING: Update performa keyword
            $this->learningService->updateKeywordStats($oldKeywordId, 'approved', [
                'transaction_id' => $transaction->id,
                'confidence_score' => $oldConfidence,
                'user_id' => $user->id,
            ]);
            
            DB::commit();
            
            // Load relationships untuk response
            $transaction->load([
                'matchedKeyword',
                'type:id,name',
                'category:id,name,color',
                'subCategory:id,name',
                'verifiedBy:id,name'
            ]);
            
            Log::info('Transaksi di-approve', [
                'transaction_id' => $transaction->id,
                'statement_id' => $transaction->bank_statement_id,
                'user_id' => $user->id,
                'keyword_id' => $oldKeywordId,
                'keyword' => $transaction->matchedKeyword->keyword ?? null,
                'confidence_score' => $oldConfidence,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil di-approve',
                'data' => [
                    'id' => $transaction->id,
                    'is_verified' => true,
                    'is_approved' => true,
                    'verified_at' => $transaction->verified_at->format('d M Y H:i'),
                    'verified_by' => $transaction->verifiedBy->name ?? null,
                    'is_manual' => false,
                    'keyword_id' => $transaction->matched_keyword_id,
                    'keyword' => $transaction->matchedKeyword->keyword ?? null,
                    'type_id' => $transaction->type_id,
                    'type' => $transaction->type->name ?? null,
                    'category_id' => $transaction->category_id,
                    'category' => $transaction->category->name ?? null,
                    'category_color' => $transaction->category->color ?? null,
                    'sub_category_id' => $transaction->sub_category_id,
                    'sub_category' => $transaction->subCategory->name ?? null,
                    'confidence_score' => $transaction->confidence_score,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Gagal approve transaksi', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal approve transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject transaksi dan tampilkan suggestions (AJAX)
     * 
     * AKSES: Semua company member
     */
    public function rejectTransaction(StatementTransaction $transaction)
    {
        $user = auth()->user();
        
        // KONTROL AKSES - Semua company member bisa reject
        abort_unless(
            $user->isSuperAdmin() || $transaction->company_id === $user->company_id,
            403,
            'Anda tidak memiliki izin untuk me-reject transaksi ini.'
        );
        
        // Validasi ada primary match
        if (!$transaction->matched_keyword_id) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada primary match untuk di-reject'
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // Simpan nilai lama untuk learning
            $rejectedKeywordId = $transaction->matched_keyword_id;
            $rejectedConfidence = $transaction->confidence_score;
            
            // Update status rejection
            $transaction->update([
                'is_rejected' => true,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'feedback_status' => 'incorrect',
                'feedback_notes' => 'User me-reject primary suggestion',
                'feedback_by' => $user->id,
                'feedback_at' => now(),
                
                // Simpan data match untuk referensi, tapi tandai sebagai rejected
                'is_verified' => false,
                'is_approved' => false,
            ]);
            
            // LEARNING: Penalize rejected keyword
            $this->learningService->updateKeywordStats($rejectedKeywordId, 'rejected', [
                'transaction_id' => $transaction->id,
                'confidence_score' => $rejectedConfidence,
                'user_id' => $user->id,
            ]);
            
            // Dapatkan alternative suggestions
            $alternatives = $transaction->alternative_categories['suggestions'] ?? [];
            
            // Filter out rejected primary suggestion (rank 1)
            $availableSuggestions = collect($alternatives)
                ->filter(fn($suggestion) => $suggestion['rank'] > 1)
                ->values()
                ->toArray();
            
            DB::commit();
            
            Log::info('Transaksi di-reject', [
                'transaction_id' => $transaction->id,
                'statement_id' => $transaction->bank_statement_id,
                'user_id' => $user->id,
                'rejected_keyword_id' => $rejectedKeywordId,
                'alternatives_count' => count($availableSuggestions),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Transaksi di-reject. Silakan pilih kategori yang benar dari suggestions.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'rejected_keyword_id' => $rejectedKeywordId,
                    'alternatives' => $availableSuggestions,
                    'total_alternatives' => count($availableSuggestions),
                    'show_suggestions' => true,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Gagal reject transaksi', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal reject transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terapkan selected suggestion (AJAX)
     * 
     * AKSES: Semua company member
     */
    public function applySuggestion(StatementTransaction $transaction, Request $request)
    {
        $validated = $request->validate([
            'suggestion_rank' => 'required|integer|min:1|max:5',
            'keyword_id' => 'required|exists:keywords,id'
        ]);
        
        $user = auth()->user();
        
        // KONTROL AKSES - Semua company member bisa terapkan suggestion
        abort_unless(
            $user->isSuperAdmin() || $transaction->company_id === $user->company_id,
            403,
            'Anda tidak memiliki izin untuk menerapkan suggestion ini.'
        );
        
        DB::beginTransaction();
        
        try {
            // Dapatkan alternative suggestions
            $alternatives = $transaction->alternative_categories['suggestions'] ?? [];
            
            if (empty($alternatives)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada suggestions tersedia untuk transaksi ini'
                ], 404);
            }
            
            // Cari selected suggestion
            $selectedSuggestion = collect($alternatives)
                ->firstWhere('rank', $validated['suggestion_rank']);
            
            if (!$selectedSuggestion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suggestion tidak ditemukan'
                ], 404);
            }
            
            // Verifikasi keyword_id cocok
            if ($selectedSuggestion['keyword_id'] != $validated['keyword_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Keyword ID tidak cocok'
                ], 400);
            }
            
            // Verifikasi keyword milik company
            $keyword = Keyword::where('id', $validated['keyword_id'])
                ->where('company_id', $transaction->company_id)
                ->where('is_active', true)
                ->firstOrFail();
            
            // Simpan nilai original untuk learning
            $originalKeywordId = $transaction->matched_keyword_id;
            $wasRejected = $transaction->is_rejected;
            $wasVerified = $transaction->is_verified;
            
            // Terapkan suggestion
            $transaction->update([
                'matched_keyword_id' => $selectedSuggestion['keyword_id'],
                'sub_category_id' => $selectedSuggestion['sub_category_id'],
                'category_id' => $selectedSuggestion['category_id'],
                'type_id' => $selectedSuggestion['type_id'],
                'confidence_score' => $selectedSuggestion['confidence_score'],
                
                // Verification
                'is_verified' => true,
                'is_approved' => true,
                'is_rejected' => false,
                'verified_by' => $user->id,
                'approved_by' => $user->id,
                'verified_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                
                // Feedback
                'feedback_status' => 'correct',
                'feedback_notes' => "User memilih suggestion rank #{$validated['suggestion_rank']} (sebelumnya " . 
                                   ($wasRejected ? "di-reject" : "bukan primary") . ")",
                'feedback_by' => $user->id,
                'feedback_at' => now(),
                
                // Matching metadata
                'is_manual_category' => false,
                'match_method' => 'user_selected_suggestion',
                'matching_reason' => "User memilih alternative suggestion (rank #{$validated['suggestion_rank']})",
            ]);
            
            // Update statistik bank statement (jika belum verified sebelumnya)
            if (!$wasVerified) {
                $transaction->bankStatement->increment('verified_transactions');
            }
            
            // LEARNING: Boost selected keyword
            $this->learningService->updateKeywordStats(
                $selectedSuggestion['keyword_id'], 
                'selected_from_suggestion',
                [
                    'transaction_id' => $transaction->id,
                    'suggestion_rank' => $validated['suggestion_rank'],
                    'confidence_score' => $selectedSuggestion['confidence_score'],
                    'user_id' => $user->id,
                ]
            );
            
            // LEARNING: Penalize original keyword (jika berbeda)
            if ($originalKeywordId && $originalKeywordId != $selectedSuggestion['keyword_id']) {
                $this->learningService->updateKeywordStats(
                    $originalKeywordId,
                    'replaced_by_suggestion',
                    [
                        'transaction_id' => $transaction->id,
                        'replaced_by_keyword_id' => $selectedSuggestion['keyword_id'],
                        'user_id' => $user->id,
                    ]
                );
            }
            
            DB::commit();
            
            // Load fresh relationships
            $transaction = $transaction->fresh()->load([
                'type:id,name',
                'category:id,name,color',
                'subCategory:id,name',
                'matchedKeyword:id,keyword',
                'verifiedBy:id,name'
            ]);
            
            Log::info('Suggestion diterapkan', [
                'transaction_id' => $transaction->id,
                'statement_id' => $transaction->bank_statement_id,
                'user_id' => $user->id,
                'suggestion_rank' => $validated['suggestion_rank'],
                'selected_keyword_id' => $selectedSuggestion['keyword_id'],
                'original_keyword_id' => $originalKeywordId,
                'was_rejected' => $wasRejected,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Suggestion berhasil diterapkan',
                'data' => [
                    'transaction' => [
                        'id' => $transaction->id,
                        'is_verified' => true,
                        'is_approved' => true,
                        'is_rejected' => false,
                        'verified_at' => $transaction->verified_at->format('d M Y H:i'),
                        'verified_by' => $transaction->verifiedBy->name ?? null,
                        'keyword_id' => $transaction->matched_keyword_id,
                        'keyword' => $transaction->matchedKeyword->keyword ?? null,
                        'type_id' => $transaction->type_id,
                        'type' => $transaction->type->name ?? null,
                        'category_id' => $transaction->category_id,
                        'category' => $transaction->category->name ?? null,
                        'category_color' => $transaction->category->color ?? null,
                        'sub_category_id' => $transaction->sub_category_id,
                        'sub_category' => $transaction->subCategory->name ?? null,
                        'confidence_score' => $transaction->confidence_score,
                        'match_method' => $transaction->match_method,
                        'is_manual' => false,
                    ],
                    'selected_suggestion' => [
                        'rank' => $validated['suggestion_rank'],
                        'keyword_id' => $selectedSuggestion['keyword_id'],
                        'sub_category' => $selectedSuggestion['sub_category_name'],
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Gagal apply suggestion', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'suggestion_rank' => $validated['suggestion_rank'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal apply suggestion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dapatkan alternative suggestions untuk transaksi (AJAX)
     * 
     * AKSES: Semua company member
     */
    public function getSuggestions(StatementTransaction $transaction)
    {
        $user = auth()->user();
        
        // KONTROL AKSES
        abort_unless(
            $user->isSuperAdmin() || $transaction->company_id === $user->company_id,
            403
        );
        
        try {
            $alternatives = $transaction->alternative_categories['suggestions'] ?? [];
            
            // Filter out primary (rank 1) jika transaksi di-reject
            if ($transaction->is_rejected && !empty($alternatives)) {
                $alternatives = collect($alternatives)
                    ->filter(fn($s) => $s['rank'] > 1)
                    ->values()
                    ->toArray();
            }
            
            Log::info('Suggestions diambil', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'suggestions_count' => count($alternatives),
                'is_rejected' => $transaction->is_rejected,
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'transaction_id' => $transaction->id,
                    'description' => $transaction->description,
                    'current_match' => [
                        'keyword_id' => $transaction->matched_keyword_id,
                        'sub_category_id' => $transaction->sub_category_id,
                        'confidence_score' => $transaction->confidence_score,
                        'is_verified' => $transaction->is_verified,
                        'is_rejected' => $transaction->is_rejected,
                    ],
                    'suggestions' => $alternatives,
                    'total_suggestions' => count($alternatives),
                    'generation_timestamp' => $transaction->alternative_categories['generation_timestamp'] ?? null,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Gagal get suggestions', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil suggestions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set keyword secara manual via Select2 (AJAX)
     * 
     * AKSES: Semua company member
     */
    public function setKeywordManually(Request $request, StatementTransaction $transaction)
    {
        $user = auth()->user();
        
        // KONTROL AKSES
        abort_unless(
            $user->isSuperAdmin() || $transaction->company_id === $user->company_id,
            403
        );
        
        $validated = $request->validate([
            'keyword_id' => 'required|exists:keywords,id'
        ]);
        
        DB::beginTransaction();
        
        try {
            // Load keyword dengan relationships
            $keyword = Keyword::with('subCategory.category.type')
                ->where('company_id', $transaction->company_id)
                ->where('is_active', true)
                ->findOrFail($validated['keyword_id']);
            
            // Cek jika sudah verified
            $wasVerified = $transaction->is_verified;
            
            // Update transaksi dengan keyword baru
            $transaction->update([
                'matched_keyword_id' => $keyword->id,
                'sub_category_id' => $keyword->sub_category_id,
                'category_id' => $keyword->subCategory->category_id,
                'type_id' => $keyword->subCategory->category->type_id,
                'confidence_score' => 100,
                'is_verified' => true,
                'verified_by' => $user->id,
                'verified_at' => now(),
                'is_manual_category' => true,
                'matching_reason' => 'Ditetapkan manual oleh user: ' . $user->name,
            ]);
            
            // Update statistik bank statement (hanya jika belum verified sebelumnya)
            if (!$wasVerified) {
                $transaction->bankStatement->increment('verified_transactions');
            }
            
            // Increment keyword usage count
            $keyword->increment('match_count');
            $keyword->update(['last_matched_at' => now()]);
            
            DB::commit();
            
            Log::info('Transaksi ditetapkan secara manual', [
                'transaction_id' => $transaction->id,
                'statement_id' => $transaction->bank_statement_id,
                'user_id' => $user->id,
                'keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Keyword berhasil ditetapkan',
                'data' => [
                    'id' => $transaction->id,
                    'keyword_id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'type_id' => $keyword->subCategory->category->type_id,
                    'type' => $keyword->subCategory->category->type->name,
                    'category_id' => $keyword->subCategory->category_id,
                    'category' => $keyword->subCategory->category->name,
                    'sub_category_id' => $keyword->sub_category_id,
                    'sub_category' => $keyword->subCategory->name,
                    'confidence_score' => 100,
                    'is_verified' => true,
                    'is_manual' => true,
                    'verified_at' => $transaction->verified_at->format('d M Y H:i'),
                    'verified_by' => $user->name,
                    'matching_reason' => $transaction->matching_reason,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Gagal set keyword manual', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'keyword_id' => $validated['keyword_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menetapkan keyword: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cari keywords untuk Select2 (AJAX)
     * 
     * AKSES: Semua company member
     */
    public function searchKeywords(Request $request)
    {
        $user = auth()->user();
        $search = $request->get('q', '');
        $companyId = $user->isSuperAdmin() && $request->filled('company_id')
            ? $request->company_id
            : $user->company_id;
        
        try {
            $keywords = Keyword::with([
                    'subCategory:id,name,category_id',
                    'subCategory.category:id,name,type_id',
                    'subCategory.category.type:id,name'
                ])
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->when($search, function($query) use ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('keyword', 'like', "%{$search}%")
                          ->orWhereHas('subCategory', function($sq) use ($search) {
                              $sq->where('name', 'like', "%{$search}%")
                                 ->orWhereHas('category', function($cq) use ($search) {
                                     $cq->where('name', 'like', "%{$search}%");
                                 });
                          });
                    });
                })
                ->orderByDesc('priority')
                ->orderByDesc('match_count')
                ->limit(50)
                ->get()
                ->map(function($keyword) {
                    return [
                        'id' => $keyword->id,
                        'text' => $keyword->keyword . ' (' . $keyword->subCategory->name . ')',
                        'keyword' => $keyword->keyword,
                        'sub_category_id' => $keyword->sub_category_id,
                        'sub_category' => $keyword->subCategory->name,
                        'category_id' => $keyword->subCategory->category_id,
                        'category' => $keyword->subCategory->category->name,
                        'type_id' => $keyword->subCategory->category->type_id,
                        'type' => $keyword->subCategory->category->type->name,
                        'priority' => $keyword->priority,
                        'match_count' => $keyword->match_count,
                        'category_path' => $keyword->subCategory->category->type->name . ' → ' . 
                                           $keyword->subCategory->category->name . ' → ' . 
                                           $keyword->subCategory->name,
                    ];
                });
            
            Log::info('Keywords search', [
                'company_id' => $companyId,
                'search' => $search,
                'count' => $keywords->count()
            ]);
            
            return response()->json([
                'results' => $keywords,
                'pagination' => [
                    'more' => false
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Gagal search keywords', [
                'user_id' => $user->id,
                'search' => $search,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'results' => [],
                'error' => $e->getMessage(),
                'pagination' => [
                    'more' => false
                ]
            ], 500);
        }
    }

    /**
     * Bulk approve transaksi (AJAX)
     * 
     * AKSES: Semua company member
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'transaction_ids' => 'required|array|min:1|max:100',
            'transaction_ids.*' => 'required|integer|exists:statement_transactions,id'
        ]);
        
        $user = auth()->user();
        
        DB::beginTransaction();
        
        try {
            $transactions = StatementTransaction::whereIn('id', $validated['transaction_ids'])
                ->where('company_id', $user->company_id)
                ->whereNotNull('matched_keyword_id')
                ->where('is_verified', false)
                ->get();
            
            if ($transactions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada transaksi yang memenuhi syarat untuk di-approve'
                ], 400);
            }
            
            $approvedCount = 0;
            $skippedCount = 0;
            
            foreach ($transactions as $transaction) {
                try {
                    $transaction->update([
                        'is_verified' => true,
                        'is_approved' => true,
                        'verified_by' => $user->id,
                        'approved_by' => $user->id,
                        'verified_at' => now(),
                        'feedback_status' => 'correct',
                        'feedback_notes' => 'Bulk approved oleh user',
                        'feedback_by' => $user->id,
                        'feedback_at' => now(),
                    ]);
                    
                    // Learning
                    $this->learningService->updateKeywordStats(
                        $transaction->matched_keyword_id,
                        'approved',
                        ['transaction_id' => $transaction->id, 'bulk' => true]
                    );
                    
                    $approvedCount++;
                    
                } catch (\Exception $e) {
                    Log::warning('Gagal approve transaksi dalam bulk', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                    $skippedCount++;
                }
            }
            
            // Update statistik bank statement
            if ($approvedCount > 0) {
                $statementIds = $transactions->pluck('bank_statement_id')->unique();
                BankStatement::whereIn('id', $statementIds)
                    ->each(function($statement) {
                        $statement->updateStatistics();
                    });
            }
            
            DB::commit();
            
            Log::info('Bulk approval selesai', [
                'user_id' => $user->id,
                'approved' => $approvedCount,
                'skipped' => $skippedCount,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "{$approvedCount} transaksi berhasil di-approve" . 
                            ($skippedCount > 0 ? ", {$skippedCount} dilewati" : ""),
                'data' => [
                    'approved' => $approvedCount,
                    'skipped' => $skippedCount,
                    'total' => count($validated['transaction_ids']),
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bulk approval gagal', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Bulk approval gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // METODE LAINNYA
    // ========================================

    /**
     * Tampilkan form edit
     * 
     * AKSES: Semua company member
     */
    public function edit(BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403,
            'Anda tidak memiliki izin untuk mengedit bank statement ini.'
        );

        $banks = Bank::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        
        return view('bank-statements.edit', compact('bankStatement', 'banks'));
    }

    /**
     * Update bank statement
     * 
     * AKSES: Semua company member
     */
    public function update(Request $request, BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403,
            'Anda tidak memiliki izin untuk mengupdate bank statement ini.'
        );

        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'notes' => 'nullable|string|max:1000',
            'account_holder_name' => 'nullable|string|max:255',
        ]);

        $bankStatement->update($validated);

        return redirect()->route('bank-statements.show', $bankStatement)
            ->with('success', 'Bank statement berhasil diupdate.');
    }

    /**
     * Hapus bank statement
     * 
     * AKSES: Semua company member
     */
    public function destroy(BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403,
            'Anda tidak memiliki izin untuk menghapus bank statement ini.'
        );

        try {
            $bankStatement->delete();

            Log::info('Bank statement dihapus', [
                'id' => $bankStatement->id,
                'company_id' => $bankStatement->company_id,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('bank-statements.index')
                ->with('success', 'Bank statement berhasil dihapus.');

        } catch (\Exception $e) {
            Log::error('Gagal hapus bank statement', [
                'id' => $bankStatement->id,
                'company_id' => $bankStatement->company_id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Gagal menghapus bank statement.');
        }
    }

    /**
     * Download bank statement PDF
     * 
     * AKSES: Semua company member
     */
    public function download(BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403,
            'Anda tidak memiliki izin untuk mendownload bank statement ini.'
        );

        if (!Storage::disk('local')->exists($bankStatement->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::disk('local')->download(
            $bankStatement->file_path,
            $bankStatement->original_filename
        );
    }

    /**
     * Reprocess OCR
     * 
     * AKSES: Semua company member
     */
    public function reprocess(BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403,
            'Anda tidak memiliki izin untuk reprocess OCR bank statement ini.'
        );

        $bankStatement->update([
            'ocr_status' => 'pending',
            'ocr_error' => null,
            'ocr_response' => null,
            'ocr_started_at' => null,
            'ocr_completed_at' => null,
        ]);

        $bankStatement->transactions()->delete();

        $bankStatement->update([
            'total_transactions' => 0,
            'processed_transactions' => 0,
            'matched_transactions' => 0,
            'unmatched_transactions' => 0,
            'verified_transactions' => 0,
        ]);

        ProcessBankStatementOCR::dispatch($bankStatement, $bankStatement->bank->slug)
            ->onQueue('ocr-processing');

        Log::info('OCR reprocessing di-queue', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Proses OCR telah di-queue kembali.');
    }

    /**
     * Match transaksi
     * 
     * AKSES: Semua company member
     */
    public function matchTransactions(BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403,
            'Anda tidak memiliki izin untuk melakukan matching transaksi.'
        );

        ProcessTransactionMatching::dispatch($bankStatement)
            ->onQueue('matching');

        return back()->with('success', 'Transaction matching telah di-queue.');
    }

    /**
     * Rematch semua transaksi
     * 
     * AKSES: Semua company member
     */
    public function rematchAll(BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403,
            'Anda tidak memiliki izin untuk rematch semua transaksi.'
        );

        $bankStatement->transactions()->update([
            'matched_keyword_id' => null,
            'confidence_score' => 0,
            'type_id' => null,
            'category_id' => null,
            'sub_category_id' => null,
            'is_manual_category' => false,
            'matching_reason' => null,
        ]);

        foreach ($bankStatement->transactions as $transaction) {
            $transaction->transactionCategories()->delete();
            $transaction->matchingLogs()->delete();
        }

        ProcessTransactionMatching::dispatch($bankStatement)
            ->onQueue('matching');

        Log::info('Semua transaksi rematch di-queue', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Semua transaksi akan di-match ulang.');
    }

    /**
     * Match accounts
     * 
     * AKSES: Semua company member
     */
    public function matchAccounts(BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403,
            'Anda tidak memiliki izin untuk matching accounts.'
        );

        ProcessAccountMatching::dispatch($bankStatement)
            ->onQueue('matching');

        return back()->with('success', 'Account matching telah di-queue.');
    }

    /**
     * Rematch semua accounts
     * 
     * AKSES: Semua company member
     */
    public function rematchAccounts(BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403,
            'Anda tidak memiliki izin untuk rematch semua accounts.'
        );

        $bankStatement->transactions()->update([
            'account_id' => null,
            'matched_account_keyword_id' => null,
            'account_confidence_score' => null,
            'is_manual_account' => false,
        ]);

        foreach ($bankStatement->transactions as $transaction) {
            $transaction->accountMatchingLogs()->delete();
        }

        ProcessAccountMatching::dispatch($bankStatement, $forceRematch = true)
            ->onQueue('matching');

        Log::info('Semua accounts rematch di-queue', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Semua accounts akan di-match ulang.');
    }

    /**
     * Verify semua transaksi yang sudah matched
     * 
     * AKSES: Semua company member
     */
    public function verifyAllMatched(BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403,
            'Anda tidak memiliki izin untuk verify transaksi.'
        );

        $updated = $bankStatement->transactions()
            ->categorized()
            ->unverified()
            ->update([
                'is_verified' => true,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

        $bankStatement->updateStatistics();

        Log::info('Bulk verification dilakukan', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'count' => $updated,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', "{$updated} transaksi berhasil diverifikasi.");
    }

    /**
     * Verify transaksi dengan confidence tinggi
     * 
     * AKSES: Semua company member
     */
    public function verifyHighConfidence(BankStatement $bankStatement, Request $request)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403,
            'Anda tidak memiliki izin untuk verify transaksi.'
        );

        $threshold = $request->input('threshold', 80);

        $updated = $bankStatement->transactions()
            ->highConfidence($threshold)
            ->unverified()
            ->update([
                'is_verified' => true,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

        $bankStatement->updateStatistics();

        return back()->with('success', "{$updated} transaksi high confidence berhasil diverifikasi.");
    }

    /**
     * Reconcile bank statement
     * 
     * AKSES: Semua company member
     */
    public function reconcile(BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403,
            'Anda tidak memiliki izin untuk melakukan rekonsiliasi.'
        );

        if (!$bankStatement->is_fully_verified) {
            return back()->with('error', 'Semua transaksi harus diverifikasi sebelum rekonsiliasi.');
        }

        $bankStatement->markAsReconciled();

        Log::info('Bank statement direkonsiliasi', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Bank statement berhasil direkonsiliasi.');
    }

    /**
     * Unreconcile bank statement
     * 
     * AKSES: Semua company member
     */
    public function unreconcile(BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403,
            'Anda tidak memiliki izin untuk membatalkan rekonsiliasi.'
        );

        $bankStatement->unmarkReconciliation();

        Log::info('Rekonsiliasi bank statement dibatalkan', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Rekonsiliasi bank statement berhasil dibatalkan.');
    }

    /**
     * Dapatkan statistik
     * 
     * AKSES: Semua company member
     */
    public function statistics(Request $request)
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $query = BankStatement::query();
            
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
            
            $stats = [
                'total' => (clone $query)->count(),
                'pending' => (clone $query)->pending()->count(),
                'processing' => (clone $query)->processing()->count(),
                'completed' => (clone $query)->completed()->count(),
                'failed' => (clone $query)->failed()->count(),
                'reconciled' => (clone $query)->reconciled()->count(),
                'unreconciled' => (clone $query)->unreconciled()->count(),
            ];
        } else {
            $stats = [
                'total' => BankStatement::where('company_id', $user->company_id)->count(),
                'pending' => BankStatement::where('company_id', $user->company_id)->pending()->count(),
                'processing' => BankStatement::where('company_id', $user->company_id)->processing()->count(),
                'completed' => BankStatement::where('company_id', $user->company_id)->completed()->count(),
                'failed' => BankStatement::where('company_id', $user->company_id)->failed()->count(),
                'reconciled' => BankStatement::where('company_id', $user->company_id)->reconciled()->count(),
                'unreconciled' => BankStatement::where('company_id', $user->company_id)->unreconciled()->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    // ========================================
    // API METHODS - MULTI-BANK UPLOAD
    // ========================================

    public function uploadMandiri(Request $request)
    {
        return $this->uploadBankStatement($request, 'mandiri');
    }

    public function uploadBCA(Request $request)
    {
        return $this->uploadBankStatement($request, 'bca');
    }

    public function uploadBNI(Request $request)
    {
        return $this->uploadBankStatement($request, 'bni');
    }

    public function uploadBRI(Request $request)
    {
        return $this->uploadBankStatement($request, 'bri');
    }

    public function uploadBTN(Request $request)
    {
        return $this->uploadBankStatement($request, 'btn');
    }

    public function uploadCIMB(Request $request)
    {
        return $this->uploadBankStatement($request, 'cimb');
    }

    /**
     * Core upload method untuk API
     * PERUBAHAN: Update pemanggilan generateUniqueFilename dengan parameter Bank
     */
    private function uploadBankStatement(Request $request, string $bankSlug)
    {
        $user = auth()->user();

        try {
            $validated = $request->validate([
                'company_id' => $user->isSuperAdmin() ? 'nullable|exists:companies,id' : 'nullable',
                'files' => 'required|array|min:1|max:10',
                'files.*' => 'required|file|mimes:pdf|max:10240',
            ]);

            $companyId = $user->isSuperAdmin() && isset($validated['company_id'])
                ? $validated['company_id'] 
                : $user->company_id;

            $bank = Bank::withoutGlobalScopes()
                ->where('slug', $bankSlug)
                ->where('is_active', true)
                ->firstOrFail();

            $uploadedStatements = [];
            $replacedStatements = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();

                try {
                    $fileHash = hash_file('sha256', $file->getRealPath());

                    $existingStatement = BankStatement::withTrashed()
                        ->where('company_id', $companyId)
                        ->where('file_hash', $fileHash)
                        ->where('bank_id', $bank->id)
                        ->first();

                    // ✅ PERUBAHAN: Gunakan Bank object sebagai parameter
                    $filename = $this->generateUniqueFilename($bank);

                    $path = $file->storeAs(
                        "companies/{$companyId}/bank-statements/{$bankSlug}/" . date('Y/m'),
                        $filename,
                        'local'
                    );

                    if (!Storage::disk('local')->exists($path)) {
                        throw new \Exception("Gagal menyimpan file: {$originalName}");
                    }

                    if ($existingStatement) {
                        $this->replaceExistingStatement($existingStatement, $file, $path, $fileHash, $originalName, $filename, $bank, $companyId);
                        
                        $replacedStatements[] = [
                            'id' => $existingStatement->id,
                            'original_filename' => $originalName,
                            'stored_filename' => $filename, // ✅ Tambahkan info filename
                            'size' => $this->formatBytes($file->getSize()),
                            'status' => 'replaced',
                        ];
                    } else {
                        $bankStatement = $this->createNewStatement($bank, $file, $path, $fileHash, $originalName, $companyId);
                        
                        $uploadedStatements[] = [
                            'id' => $bankStatement->id,
                            'original_filename' => $originalName,
                            'stored_filename' => $filename, // ✅ Tambahkan info filename
                            'size' => $this->formatBytes($file->getSize()),
                            'status' => 'queued',
                        ];
                    }

                } catch (\Exception $e) {
                    Log::error("Gagal upload file via API", [
                        'company_id' => $companyId,
                        'filename' => $originalName,
                        'error' => $e->getMessage(),
                    ]);

                    $errors[] = [
                        'filename' => $originalName,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            $response = [
                'success' => true,
                'message' => $this->buildUploadMessage(
                    count($uploadedStatements),
                    count($replacedStatements),
                    count($errors)
                ),
                'data' => [
                    'bank' => [
                        'id' => $bank->id,
                        'name' => $bank->name,
                        'slug' => $bank->slug,
                    ],
                    'uploaded' => $uploadedStatements,
                    'total_uploaded' => count($uploadedStatements),
                ],
            ];

            if (!empty($replacedStatements)) {
                $response['data']['replaced'] = $replacedStatements;
                $response['data']['total_replaced'] = count($replacedStatements);
            }

            if (!empty($errors)) {
                $response['data']['errors'] = $errors;
                $response['data']['total_errors'] = count($errors);
            }

            return response()->json($response, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Upload bank statement via API gagal", [
                'company_id' => $user->company_id,
                'bank' => $bankSlug,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dapatkan status upload
     */
    public function getStatus(BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        return response()->json([
            'success' => true,
            'data' => $bankStatement->getSummary(),
        ]);
    }

    /**
     * Retry OCR yang gagal
     */
    public function retryOCR(BankStatement $bankStatement)
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        if ($bankStatement->ocr_status !== 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya statement yang gagal yang bisa di-retry.',
            ], 400);
        }

        $bankStatement->update([
            'ocr_status' => 'pending',
            'ocr_error' => null,
        ]);

        ProcessBankStatementOCR::dispatch($bankStatement, $bankStatement->bank->slug)
            ->onQueue('ocr-processing');

        return response()->json([
            'success' => true,
            'message' => 'Proses OCR telah di-queue kembali.',
        ]);
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    /**
     * Dapatkan users untuk dropdown filter
     */
    private function getUsersForFilter(User $user, Request $request): ?object
    {
        if ($user->isSuperAdmin() && $request->filled('company_id')) {
            return User::where('company_id', $request->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }
        
        if ($user->hasAdminAccess()) {
            return User::where('company_id', $user->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }
        
        return null;
    }

    /**
     * Hitung statistik untuk halaman index
     */
    private function calculateStatistics(User $user, Request $request): array
    {
        $statsQuery = BankStatement::query();
        
        // Terapkan scope company
        if ($user->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $statsQuery->where('company_id', $request->company_id);
            }
        } else {
            $statsQuery->where('company_id', $user->company_id);
        }

        // Terapkan semua filter
        if ($request->filled('bank_id')) {
            $statsQuery->where('bank_id', $request->bank_id);
        }

        if ($request->filled('is_reconciled')) {
            $statsQuery->where('is_reconciled', $request->boolean('is_reconciled'));
        }

        if ($request->filled('user_id')) {
            $statsQuery->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $statsQuery->where('period_from', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $statsQuery->where('period_to', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $statsQuery->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('account_holder_name', 'like', "%{$search}%");
            });
        }

        // Hitung stats
        return [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('ocr_status', 'pending')->count(),
            'processing' => (clone $statsQuery)->where('ocr_status', 'processing')->count(),
            'completed' => (clone $statsQuery)->where('ocr_status', 'completed')->count(),
            'failed' => (clone $statsQuery)->where('ocr_status', 'failed')->count(),
        ];
    }

    /**
     * Terapkan filter transaksi ke query
     */
    private function applyTransactionFilters($query, Request $request): void
    {
        $filter = $request->get('filter');
        
        switch ($filter) {
            case 'categorized':
                $query->whereNotNull('sub_category_id');
                break;
            case 'uncategorized':
                $query->whereNull('sub_category_id');
                break;
            case 'verified':
                $query->where('is_verified', true);
                break;
            case 'approved':
                $query->where('is_approved', true);
                break;
            case 'rejected':
                $query->where('is_rejected', true);
                break;
            case 'with-account':
                $query->whereNotNull('account_id');
                break;
            case 'high-confidence':
                $query->where('confidence_score', '>=', 80);
                break;
            case 'low-confidence':
                $query->where('confidence_score', '>', 0)
                    ->where('confidence_score', '<', 50);
                break;
        }

        // FILTER TIPE
        $type = $request->get('type');
        if ($type === 'credit') {
            $query->where('transaction_type', 'credit');
        } elseif ($type === 'debit') {
            $query->where('transaction_type', 'debit');
        }

        // FILTER RENTANG AMOUNT
        $amountRange = $request->get('amount_range');
        switch ($amountRange) {
            case 'large':
                $query->where('amount', '>', 1000000);
                break;
            case 'medium':
                $query->whereBetween('amount', [100000, 1000000]);
                break;
            case 'small':
                $query->where('amount', '<', 100000);
                break;
        }

        // FILTER KHUSUS
        $special = $request->get('special');
        switch ($special) {
            case 'round':
                $query->where(function($q) {
                    $q->whereRaw('amount % 1000000 = 0')
                    ->orWhereRaw('amount % 100000 = 0');
                });
                break;
            case 'manual':
                $query->where(function($q) {
                    $q->where('is_manual_category', true)
                    ->orWhere('is_manual_account', true);
                });
                break;
        }
    }

    /**
     * Terapkan sorting transaksi ke query
     */
    private function applyTransactionSorting($query, string $sort): void
    {
        switch ($sort) {
            case 'amount-desc':
                $query->orderBy('amount', 'desc');
                break;
            case 'amount-asc':
                $query->orderBy('amount', 'asc');
                break;
            case 'date-asc':
                $query->orderBy('transaction_date', 'asc')
                    ->orderBy('transaction_time', 'asc');
                break;
            case 'date-desc':
            default:
                $query->orderBy('transaction_date', 'desc')
                    ->orderBy('transaction_time', 'desc');
                break;
        }
    }

    /**
     * Hitung statistik transaksi untuk halaman show
     */
    private function calculateTransactionStatistics(BankStatement $bankStatement): array
    {
        return [
            'total' => $bankStatement->total_transactions,
            'categorized' => $bankStatement->transactions()->whereNotNull('sub_category_id')->count(),
            'uncategorized' => $bankStatement->transactions()->whereNull('sub_category_id')->count(),
            'with_account' => $bankStatement->transactions()->whereNotNull('account_id')->count(),
            'without_account' => $bankStatement->transactions()->whereNull('account_id')->count(),
            'verified' => $bankStatement->verified_transactions,
            'unverified' => $bankStatement->total_transactions - $bankStatement->verified_transactions,
            'approved' => $bankStatement->transactions()->where('is_approved', true)->count(),
            'rejected' => $bankStatement->transactions()->where('is_rejected', true)->count(),
            'high_confidence' => $bankStatement->transactions()->where('confidence_score', '>=', 80)->count(),
            'low_confidence' => $bankStatement->transactions()->where('confidence_score', '>', 0)->where('confidence_score', '<', 50)->count(),
        ];
    }

    /**
     * Hitung statistik validasi
     */
    private function calculateValidationStatistics(BankStatement $bankStatement, $transactions): array
    {
        $stats = [
            'total' => $bankStatement->total_transactions,
            'verified' => $bankStatement->verified_transactions,
            'pending' => $bankStatement->total_transactions - $bankStatement->verified_transactions,
            'high_confidence' => $transactions->where('is_verified', false)
                                             ->where('confidence_score', '>=', 80)
                                             ->count(),
            'medium_confidence' => $transactions->where('is_verified', false)
                                               ->where('confidence_score', '>=', 50)
                                               ->where('confidence_score', '<', 80)
                                               ->count(),
            'low_confidence' => $transactions->where('is_verified', false)
                                            ->where('confidence_score', '>', 0)
                                            ->where('confidence_score', '<', 50)
                                            ->count(),
            'no_match' => $transactions->where('is_verified', false)
                                      ->whereNull('matched_keyword_id')
                                      ->count(),
            'auto_approved' => $transactions->where('is_verified', true)
                                           ->where('is_manual_category', false)
                                           ->count(),
            'manual_assigned' => $transactions->where('is_verified', true)
                                             ->where('is_manual_category', true)
                                             ->count(),
            'rejected' => $transactions->where('is_rejected', true)->count(),
        ];
        
        // Hitung persentase progress
        $stats['progress'] = $bankStatement->total_transactions > 0 
            ? round(($stats['verified'] / $bankStatement->total_transactions) * 100, 1)
            : 0;
        
        return $stats;
    }

    private function generateUniqueFilename(Bank $bank): string
    {
        $bankSlug = $bank->slug; // e.g., 'mandiri', 'bca', 'bni'
        $date = now()->format('Ymd'); // e.g., '20251015'
        $time = now()->format('His'); // e.g., '143025'
        $microseconds = now()->format('u'); // e.g., '456789'
        
        // Format: mandiri_20251015_143025_456789.pdf
        return sprintf(
            '%s_%s_%s_%s.pdf',
            $bankSlug,
            $date,
            $time,
            $microseconds
        );
    }

    /**
     * Replace bank statement yang sudah ada
     * Method ini tidak berubah, hanya menerima parameter $filename dari method store/upload
     */
    private function replaceExistingStatement(
        BankStatement $existingStatement,
        $file,
        string $path,
        string $fileHash,
        string $originalName,
        string $filename,
        Bank $bank,
        int $companyId
    ): void {
        // Hapus file lama jika ada
        if ($existingStatement->file_path && Storage::disk('local')->exists($existingStatement->file_path)) {
            Storage::disk('local')->delete($existingStatement->file_path);
        }

        // Hapus transaksi yang ada
        $existingStatement->transactions()->delete();

        // Restore jika soft deleted
        if ($existingStatement->trashed()) {
            $existingStatement->restore();
        }

        // Update statement yang ada
        $existingStatement->update([
            'user_id' => auth()->id(),
            'file_path' => $path,
            'file_hash' => $fileHash,
            'original_filename' => $originalName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'ocr_status' => 'pending',
            'ocr_job_id' => Str::uuid()->toString(),
            'ocr_error' => null,
            'ocr_response' => null,
            'ocr_started_at' => null,
            'ocr_completed_at' => null,
            'uploaded_at' => now(),
            'bank_name' => null,
            'period_from' => null,
            'period_to' => null,
            'account_number' => null,
            'account_holder_name' => null,
            'opening_balance' => null,
            'closing_balance' => null,
            'total_credit_count' => 0,
            'total_debit_count' => 0,
            'total_credit_amount' => 0,
            'total_debit_amount' => 0,
            'total_transactions' => 0,
            'processed_transactions' => 0,
            'matched_transactions' => 0,
            'unmatched_transactions' => 0,
            'verified_transactions' => 0,
            'is_reconciled' => false,
            'reconciled_at' => null,
            'reconciled_by' => null,
            'notes' => null,
        ]);

        // Pertahankan atau buat document collection
        if ($existingStatement->documentItem) {
            $existingStatement->documentItem->update([
                'knowledge_status' => 'pending',
                'knowledge_error' => null,
                'processed_at' => null,
            ]);

            Log::info('Document collection dipertahankan untuk statement yang diganti', [
                'statement_id' => $existingStatement->id,
                'collection_id' => $existingStatement->documentItem->document_collection_id,
            ]);
        } else {
            $this->createDocumentCollection($existingStatement);
        }

        // Queue proses OCR
        ProcessBankStatementOCR::dispatch($existingStatement, $bank->slug)
            ->onQueue('ocr-processing');
    }

    /**
     * Buat bank statement baru
     * Method ini tidak berubah, hanya menerima parameter $filename dari method store/upload
     */
    private function createNewStatement(
        Bank $bank,
        $file,
        string $path,
        string $fileHash,
        string $originalName,
        int $companyId
    ): BankStatement {
        $bankStatement = BankStatement::create([
            'company_id' => $companyId,
            'bank_id' => $bank->id,
            'user_id' => auth()->id(),
            'file_path' => $path,
            'file_hash' => $fileHash,
            'original_filename' => $originalName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'ocr_status' => 'pending',
            'ocr_job_id' => Str::uuid()->toString(),
            'uploaded_at' => now(),
        ]);

        $this->createDocumentCollection($bankStatement);

        ProcessBankStatementOCR::dispatch($bankStatement, $bank->slug)
            ->onQueue('ocr-processing');

        return $bankStatement;
    }

    /**
     * Buat document collection untuk bank statement
     */
    private function createDocumentCollection(BankStatement $bankStatement): void
    {
        try {
            $baseName = pathinfo($bankStatement->original_filename, PATHINFO_FILENAME);
            $bankName = $bankStatement->bank->name ?? 'Bank';
            $collectionName = "{$bankName} - " . Str::limit($baseName, 80) . ' (' . now()->format('d M Y') . ')';
            
            $collection = DocumentCollection::create([
                'uuid' => Str::uuid(),
                'company_id' => $bankStatement->company_id,
                'user_id' => $bankStatement->user_id,
                'name' => $collectionName,
                'description' => "Dibuat otomatis dari: {$bankStatement->original_filename}",
                'color' => $this->getRandomColor(),
                'icon' => 'document-text',
                'document_count' => 1,
                'total_transactions' => 0,
                'total_debit' => 0,
                'total_credit' => 0,
                'is_active' => true,
            ]);

            DocumentItem::create([
                'uuid' => Str::uuid(),
                'company_id' => $bankStatement->company_id,
                'document_collection_id' => $collection->id,
                'bank_statement_id' => $bankStatement->id,
                'document_type' => 'bank_statement',
                'sort_order' => 0,
                'knowledge_status' => 'pending',
            ]);

            Log::info('Document collection dibuat otomatis', [
                'collection_id' => $collection->id,
                'collection_name' => $collection->name,
                'statement_id' => $bankStatement->id,
                'company_id' => $bankStatement->company_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal membuat document collection', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Dapatkan warna random untuk collection
     */
    private function getRandomColor(): string
    {
        $colors = [
            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
            '#EC4899', '#06B6D4', '#F97316', '#14B8A6', '#84CC16',
        ];

        return $colors[array_rand($colors)];
    }

    /**
     * Bangun pesan upload
     */
    private function buildUploadMessage(int $uploaded, int $replaced, int $errors): string
    {
        $messages = [];

        if ($uploaded > 0) {
            $messages[] = "{$uploaded} file baru berhasil diupload";
        }

        if ($replaced > 0) {
            $messages[] = "{$replaced} file berhasil diganti";
        }

        if ($errors > 0) {
            $messages[] = "{$errors} file gagal";
        }

        $message = implode(', ', $messages);
        
        if ($uploaded > 0 || $replaced > 0) {
            $message .= ' dan telah di-queue untuk proses OCR';
        }

        return $message . '.';
    }

    /**
     * Format bytes ke format yang mudah dibaca
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}   