<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankStatement;
use App\Models\Company;
use App\Models\DocumentCollection;
use App\Models\DocumentItem;
use App\Jobs\ProcessBankStatementOCR;
use App\Jobs\ProcessAccountMatching;
use App\Jobs\ProcessTransactionMatching;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BankStatementController extends Controller
{
   /**
     * Display listing of bank statements with filters
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // SUPER ADMIN: Can see all statements from all companies
        if ($user->isSuperAdmin()) {
            $query = BankStatement::with(['bank:id,name,code,logo', 'user:id,name', 'company:id,name'])
                ->latest('uploaded_at');

            // Filter by company (super admin only)
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
        } else {
            // COMPANY SCOPED QUERY (regular users)
            $query = BankStatement::where('company_id', $user->company_id)
                ->with(['bank:id,name,code,logo', 'user:id,name'])
                ->latest('uploaded_at');
        }

        // Filter by bank
        if ($request->filled('bank_id')) {
            $query->where('bank_id', $request->bank_id);
        }

        // Filter by OCR status
        if ($request->filled('ocr_status')) {
            $query->where('ocr_status', $request->ocr_status);
        }

        // Filter by reconciliation status
        if ($request->filled('is_reconciled')) {
            $query->where('is_reconciled', $request->boolean('is_reconciled'));
        }

        // Filter by uploaded user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range (period)
        if ($request->filled('date_from')) {
            $query->where('period_from', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('period_to', '<=', $request->date_to);
        }

        // Search by filename, account number, or holder name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('account_holder_name', 'like', "%{$search}%");
            });
        }

        // Get paginated results
        $statements = $query->paginate(20)->withQueryString();

        // Get filter options
        $banks = Bank::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'logo']);
        
        // Companies (Super Admin only)
        $companies = $user->isSuperAdmin() 
            ? Company::where('status', '!=', 'cancelled')
                ->orderBy('name')
                ->get(['id', 'name'])
            : null;

        // Users for filter (within company scope)
        $users = $user->isSuperAdmin() && $request->filled('company_id')
            ? \App\Models\User::where('company_id', $request->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
            : ($user->isAdmin() 
                ? \App\Models\User::where('company_id', $user->company_id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : null);

        // OCR Status options
        $ocrStatuses = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed'
        ];

        // ✅ FIX: Statistics - Clone query untuk setiap count
        $baseQuery = clone $query->getQuery(); // Clone base query
        
        // Build base query untuk stats (tanpa pagination)
        $statsQuery = BankStatement::query();
        
        // Apply same filters untuk stats
        if ($user->isSuperAdmin()) {
            // Super admin filters
            if ($request->filled('company_id')) {
                $statsQuery->where('company_id', $request->company_id);
            }
        } else {
            // Company scoped
            $statsQuery->where('company_id', $user->company_id);
        }

        // Apply all other filters
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

        // Calculate stats with same filters
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('ocr_status', 'pending')->count(),
            'processing' => (clone $statsQuery)->where('ocr_status', 'processing')->count(),
            'completed' => (clone $statsQuery)->where('ocr_status', 'completed')->count(),
            'failed' => (clone $statsQuery)->where('ocr_status', 'failed')->count(),
        ];

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
     * Show company selection form (Super Admin Only)
     */
    public function selectCompany()
    {
        $user = auth()->user();
        
        // Only super admin can access
        abort_unless($user->isSuperAdmin(), 403);
        
        // Get all companies (banks are now global, so no need to check bank count per company)
        $companies = Company::where('status', 'active')
            ->orderBy('name')
            ->get();
        
        return view('bank-statements.select-company', compact('companies'));
    }

    /**
     * Show the form for creating a new bank statement
     * - Super Admin: Must have company context (via company_id parameter)
     * - User Biasa: Auto-scoped to their company
     */
    public function create(Request $request)
    {
        $user = auth()->user();

        // SUPER ADMIN: Must have company context
        if ($user->isSuperAdmin()) {
            // Check if company_id provided in query string
            $companyId = $request->input('company_id');
            
            if (!$companyId) {
                // Redirect to company selection page
                return redirect()->route('bank-statements.select-company')
                    ->with('info', 'Please select a company first.');
            }
            
            // Verify company exists
            $company = Company::findOrFail($companyId);
            
            // Get global banks (shared across all companies)
            $banks = Bank::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'logo']);
            
            // Check if any banks exist in system
            if ($banks->isEmpty()) {
                return redirect()->route('bank-statements.select-company')
                    ->with('error', 'No active banks available in the system. Please add banks first.');
            }
            
            return view('bank-statements.create', compact('banks', 'company'));
        }
        
        // REGULAR USER: Company scoped (auto by trait)
        // Verify user has company
        if (!$user->company_id) {
            return redirect()->route('dashboard')
                ->with('error', 'You are not assigned to any company. Please contact administrator.');
        }
        
        // Get global banks (shared across all companies)
        $banks = Bank::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'logo']);
        
        // If no banks available in system
        if ($banks->isEmpty()) {
            return redirect()->route('bank-statements.index')
                ->with('error', 'No banks available in the system. Please contact administrator.');
        }
        
        return view('bank-statements.create', compact('banks'));
    }

    /**
     * Store a newly created bank statement
     * - Super Admin: Use company_id from request
     * - User Biasa: Auto use their company_id
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Validation
        $rules = [
            'bank_id' => 'required|exists:banks,id', // Bank is global, no company check
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|mimes:pdf|max:10240', // 10MB max
        ];
        
        // Super admin must provide company_id
        if ($user->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }
        
        $validated = $request->validate($rules);

        // Determine company_id
        $companyId = $user->isSuperAdmin() 
            ? $validated['company_id'] 
            : $user->company_id;
        
        // Verify user has company
        if (!$companyId) {
            return back()
                ->with('error', 'No company context found.')
                ->withInput();
        }

        // Get bank (global - no company check needed)
        $bank = Bank::findOrFail($request->bank_id);
        
        $uploadedCount = 0;
        $replacedCount = 0;
        $failedFiles = [];

        DB::beginTransaction();

        try {
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                
                try {
                    // Calculate file hash for duplicate detection
                    $fileHash = hash_file('sha256', $file->getRealPath());

                    // Check for existing file (COMPANY SCOPED - including soft deleted)
                    $existingStatement = BankStatement::withTrashed()
                        ->where('company_id', $companyId)
                        ->where('file_hash', $fileHash)
                        ->where('bank_id', $bank->id)
                        ->first();

                    // Generate unique filename
                    $filename = $this->generateUniqueFilename($originalName);

                    // Store file - use private disk for security
                    $path = $file->storeAs(
                        "companies/{$companyId}/bank-statements/{$bank->slug}/" . date('Y/m'),
                        $filename,
                        'local'
                    );

                    // Verify file was stored
                    if (!Storage::disk('local')->exists($path)) {
                        throw new \Exception("Failed to store file: {$originalName}");
                    }

                    if ($existingStatement) {
                        // REPLACE: Update existing record and delete old file
                        $this->replaceExistingStatement($existingStatement, $file, $path, $fileHash, $originalName, $filename, $bank, $companyId);
                        $replacedCount++;

                        Log::info('Bank statement REPLACED', [
                            'id' => $existingStatement->id,
                            'company_id' => $companyId,
                            'user_id' => $user->id,
                            'filename' => $originalName,
                            'bank' => $bank->name,
                        ]);
                    } else {
                        // NEW: Create new record
                        $bankStatement = $this->createNewStatement($bank, $file, $path, $fileHash, $originalName, $companyId);
                        $uploadedCount++;

                        Log::info('Bank statement UPLOADED', [
                            'id' => $bankStatement->id,
                            'company_id' => $companyId,
                            'user_id' => $user->id,
                            'filename' => $originalName,
                            'bank' => $bank->name,
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to upload individual file', [
                        'company_id' => $companyId,
                        'filename' => $originalName,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $failedFiles[] = $originalName;
                }
            }

            DB::commit();

            // Build success message
            $message = $this->buildUploadMessage($uploadedCount, $replacedCount, count($failedFiles));

            return redirect()->route('bank-statements.index')
                ->with('success', $message)
                ->with('uploaded_count', $uploadedCount)
                ->with('replaced_count', $replacedCount)
                ->with('failed_files', $failedFiles)
                ->with('queued_count', $uploadedCount + $replacedCount);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bank statement upload failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
            
            return back()
                ->with('error', 'Upload failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified bank statement with complete filters
     * Supports: Super Admin & Regular Users with advanced filtering
     */
    public function show(BankStatement $bankStatement)
    {
        $user = auth()->user();
        
        // ===================================
        // ACCESS CONTROL
        // ===================================
        if ($user->isSuperAdmin()) {
            // Super Admin: Full Access to all companies
            $bankStatement->load([
                'bank:id,name,code,logo',
                'user:id,name,email',
                'reconciledBy:id,name',
                'company:id,name'
            ]);
        } else {
            // Regular User: Company-scoped only
            abort_unless($bankStatement->company_id === $user->company_id, 403, 
                'You do not have permission to view this bank statement.');
            
            $bankStatement->load([
                'bank:id,name,code,logo',
                'user:id,name,email',
                'reconciledBy:id,name'
            ]);
        }

        // ===================================
        // BUILD QUERY WITH RELATIONSHIPS
        // ===================================
        $query = $bankStatement->transactions()
            ->with([
                'type:id,name',
                'category:id,name,color',
                'subCategory:id,name',
                'account:id,name,code,account_type',
                'verifiedBy:id,name'
            ]);

        // ===================================
        // APPLY STATUS FILTERS
        // ===================================
        $filter = request('filter');
        
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

        // ===================================
        // APPLY TYPE FILTER (Credit/Debit)
        // ===================================
        $type = request('type');
        if ($type === 'credit') {
            $query->where('transaction_type', 'credit');
        } elseif ($type === 'debit') {
            $query->where('transaction_type', 'debit');
        }

        // ===================================
        // APPLY AMOUNT RANGE FILTER
        // ===================================
        $amountRange = request('amount_range');
        switch ($amountRange) {
            case 'large':
                $query->where('amount', '>', 1000000); // > 1 Million
                break;
            case 'medium':
                $query->whereBetween('amount', [100000, 1000000]); // 100K - 1M
                break;
            case 'small':
                $query->where('amount', '<', 100000); // < 100K
                break;
        }

        // ===================================
        // APPLY SPECIAL FILTERS
        // ===================================
        $special = request('special');
        switch ($special) {
            case 'round':
                // Round numbers: divisible by 100K or 1M
                $query->where(function($q) {
                    $q->whereRaw('amount % 1000000 = 0')
                    ->orWhereRaw('amount % 100000 = 0');
                });
                break;
            case 'manual':
                // Manual categorization or account assignment
                $query->where(function($q) {
                    $q->where('is_manual_category', true)
                    ->orWhere('is_manual_account', true);
                });
                break;
        }

        // ===================================
        // APPLY SORTING
        // ===================================
        $sort = request('sort', 'date-desc'); // Default: newest first
        
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

        // ===================================
        // PAGINATE RESULTS
        // ===================================
        $transactions = $query->paginate(20);

        // ===================================
        // CALCULATE STATISTICS
        // ===================================
        $statistics = [
            'total' => $bankStatement->total_transactions,
            'categorized' => $bankStatement->transactions()->whereNotNull('sub_category_id')->count(),
            'uncategorized' => $bankStatement->transactions()->whereNull('sub_category_id')->count(),
            'with_account' => $bankStatement->transactions()->whereNotNull('account_id')->count(),
            'without_account' => $bankStatement->transactions()->whereNull('account_id')->count(),
            'verified' => $bankStatement->verified_transactions,
            'unverified' => $bankStatement->total_transactions - $bankStatement->verified_transactions,
            'high_confidence' => $bankStatement->transactions()->where('confidence_score', '>=', 80)->count(),
            'low_confidence' => $bankStatement->transactions()->where('confidence_score', '>', 0)->where('confidence_score', '<', 50)->count(),
        ];

        return view('bank-statements.show', compact('bankStatement', 'statistics', 'transactions'));
    }

    /**
     * Show the form for editing the specified bank statement (COMPANY SCOPED or SUPER ADMIN)
     */
    public function edit(BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        // Get global banks (no company restriction)
        $banks = Bank::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        
        return view('bank-statements.edit', compact('bankStatement', 'banks'));
    }

    /**
     * Update the specified bank statement (COMPANY SCOPED or SUPER ADMIN)
     */
    public function update(Request $request, BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id', // Global bank, no company check
            'notes' => 'nullable|string|max:1000',
            'account_holder_name' => 'nullable|string|max:255',
        ]);

        // Update bank statement
        $bankStatement->update($validated);

        return redirect()->route('bank-statements.show', $bankStatement)
            ->with('success', 'Bank statement updated successfully.');
    }

    /**
     * Remove the specified bank statement (COMPANY SCOPED or SUPER ADMIN)
     */
    public function destroy(BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        try {
            // Soft delete akan otomatis cascade ke transactions (karena SoftDeletes)
            $bankStatement->delete();

            Log::info('Bank statement deleted', [
                'id' => $bankStatement->id,
                'company_id' => $bankStatement->company_id,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('bank-statements.index')
                ->with('success', 'Bank statement deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete bank statement', [
                'id' => $bankStatement->id,
                'company_id' => $bankStatement->company_id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to delete bank statement.');
        }
    }

    /**
     * Permanently delete bank statement and its file (COMPANY SCOPED or SUPER ADMIN)
     */
    public function forceDelete($id)
    {
        $user = auth()->user();

        try {
            // Find statement (with trashed)
            if ($user->isSuperAdmin()) {
                $bankStatement = BankStatement::withTrashed()->findOrFail($id);
            } else {
                // COMPANY SCOPED
                $bankStatement = BankStatement::withTrashed()
                    ->where('company_id', $user->company_id)
                    ->findOrFail($id);
            }

            // Delete file from storage
            if ($bankStatement->file_path && Storage::disk('local')->exists($bankStatement->file_path)) {
                Storage::disk('local')->delete($bankStatement->file_path);
            }

            // Force delete (permanent)
            $bankStatement->forceDelete();

            Log::info('Bank statement permanently deleted', [
                'id' => $bankStatement->id,
                'company_id' => $bankStatement->company_id,
                'user_id' => $user->id,
            ]);

            return redirect()->route('bank-statements.index')
                ->with('success', 'Bank statement permanently deleted.');

        } catch (\Exception $e) {
            Log::error('Failed to force delete bank statement', [
                'id' => $id,
                'company_id' => $user->company_id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to permanently delete bank statement.');
        }
    }

    /**
     * Restore soft deleted bank statement (COMPANY SCOPED or SUPER ADMIN)
     */
    public function restore($id)
    {
        $user = auth()->user();

        try {
            // Find statement (with trashed)
            if ($user->isSuperAdmin()) {
                $bankStatement = BankStatement::withTrashed()->findOrFail($id);
            } else {
                // COMPANY SCOPED
                $bankStatement = BankStatement::withTrashed()
                    ->where('company_id', $user->company_id)
                    ->findOrFail($id);
            }

            $bankStatement->restore();

            Log::info('Bank statement restored', [
                'id' => $bankStatement->id,
                'company_id' => $bankStatement->company_id,
                'user_id' => $user->id,
            ]);

            return redirect()->route('bank-statements.show', $bankStatement)
                ->with('success', 'Bank statement restored successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to restore bank statement', [
                'id' => $id,
                'company_id' => $user->company_id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to restore bank statement.');
        }
    }

    /**
     * Download bank statement PDF (COMPANY SCOPED or SUPER ADMIN)
     */
    public function download(BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        if (!Storage::disk('local')->exists($bankStatement->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->download(
            $bankStatement->file_path,
            $bankStatement->original_filename
        );
    }

    /**
     * Reprocess OCR for a statement (COMPANY SCOPED or SUPER ADMIN)
     */
    public function reprocess(BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        // Reset OCR status
        $bankStatement->update([
            'ocr_status' => 'pending',
            'ocr_error' => null,
            'ocr_response' => null,
            'ocr_started_at' => null,
            'ocr_completed_at' => null,
        ]);

        // Soft delete existing transactions
        $bankStatement->transactions()->delete();

        // Reset statistics
        $bankStatement->update([
            'total_transactions' => 0,
            'processed_transactions' => 0,
            'matched_transactions' => 0,
            'unmatched_transactions' => 0,
            'verified_transactions' => 0,
        ]);

        // Dispatch OCR job again
        ProcessBankStatementOCR::dispatch($bankStatement, $bankStatement->bank->slug)
            ->onQueue('ocr-processing');

        Log::info('OCR reprocessing queued', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'OCR processing has been queued again.');
    }

    /**
     * Match transactions for a statement (COMPANY SCOPED or SUPER ADMIN)
     */
    public function matchTransactions(BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        ProcessTransactionMatching::dispatch($bankStatement)
            ->onQueue('matching');

        return back()->with('success', 'Transaction matching has been queued.');
    }

    /**
     * Rematch all transactions (COMPANY SCOPED or SUPER ADMIN)
     */
    public function rematchAll(BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        // Reset category matching data
        $bankStatement->transactions()->update([
            'matched_keyword_id' => null,
            'confidence_score' => 0,
            'type_id' => null,
            'category_id' => null,
            'sub_category_id' => null,
            'is_manual_category' => false,
            'matching_reason' => null,
        ]);

        // Delete existing transaction_categories
        foreach ($bankStatement->transactions as $transaction) {
            $transaction->transactionCategories()->delete();
        }

        // Delete matching logs
        foreach ($bankStatement->transactions as $transaction) {
            $transaction->matchingLogs()->delete();
        }

        // Dispatch matching job
        ProcessTransactionMatching::dispatch($bankStatement)
            ->onQueue('matching');

        Log::info('All transactions rematching queued', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'All transactions will be re-matched.');
    }

    /**
     * Match accounts for all transactions (COMPANY SCOPED or SUPER ADMIN)
     */
    public function matchAccounts(BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        ProcessAccountMatching::dispatch($bankStatement)
            ->onQueue('matching');

        return back()->with('success', 'Account matching has been queued.');
    }

    /**
     * Rematch all accounts (COMPANY SCOPED or SUPER ADMIN)
     */
    public function rematchAccounts(BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        // Reset account matching data
        $bankStatement->transactions()->update([
            'account_id' => null,
            'matched_account_keyword_id' => null,
            'account_confidence_score' => null,
            'is_manual_account' => false,
        ]);

        // Delete existing account matching logs
        foreach ($bankStatement->transactions as $transaction) {
            $transaction->accountMatchingLogs()->delete();
        }

        // Dispatch account matching job with force rematch
        ProcessAccountMatching::dispatch($bankStatement, $forceRematch = true)
            ->onQueue('matching');

        Log::info('All accounts rematching queued', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'All accounts will be re-matched.');
    }

    /**
     * Verify all matched transactions (COMPANY SCOPED or SUPER ADMIN)
     */
    public function verifyAllMatched(BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        $updated = $bankStatement->transactions()
            ->categorized()
            ->unverified()
            ->update([
                'is_verified' => true,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

        // Update statistics
        $bankStatement->updateStatistics();

        Log::info('Bulk verification performed', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'count' => $updated,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', "{$updated} transaction(s) verified.");
    }

    /**
     * Verify high confidence transactions only (COMPANY SCOPED or SUPER ADMIN)
     */
    public function verifyHighConfidence(BankStatement $bankStatement, Request $request)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
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

        // Update statistics
        $bankStatement->updateStatistics();

        return back()->with('success', "{$updated} high confidence transaction(s) verified.");
    }

    /**
     * Reconcile bank statement (COMPANY SCOPED or SUPER ADMIN)
     */
    public function reconcile(BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        if (!$bankStatement->is_fully_verified) {
            return back()->with('error', 'All transactions must be verified before reconciliation.');
        }

        $bankStatement->markAsReconciled();

        Log::info('Bank statement reconciled', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Bank statement reconciled successfully.');
    }

    /**
     * Unreconcile bank statement (COMPANY SCOPED or SUPER ADMIN)
     */
    public function unreconcile(BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        $bankStatement->unmarkReconciliation();

        Log::info('Bank statement unreconciled', [
            'statement_id' => $bankStatement->id,
            'company_id' => $bankStatement->company_id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Bank statement unreconciled.');
    }

    /**
     * Get statistics (COMPANY SCOPED or SUPER ADMIN)
     */
    public function statistics(Request $request)
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            // Super admin: all companies or filtered
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
            // Regular users: company scoped
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
    // API Methods for Multi-Bank Upload (COMPANY SCOPED or SUPER ADMIN)
    // ========================================

    /**
     * Upload Bank Statement - Mandiri (API - COMPANY SCOPED or SUPER ADMIN)
     */
    public function uploadMandiri(Request $request)
    {
        return $this->uploadBankStatement($request, 'mandiri');
    }

    /**
     * Upload Bank Statement - BCA (API - COMPANY SCOPED or SUPER ADMIN)
     */
    public function uploadBCA(Request $request)
    {
        return $this->uploadBankStatement($request, 'bca');
    }

    /**
     * Upload Bank Statement - BNI (API - COMPANY SCOPED or SUPER ADMIN)
     */
    public function uploadBNI(Request $request)
    {
        return $this->uploadBankStatement($request, 'bni');
    }

    /**
     * Upload Bank Statement - BRI (API - COMPANY SCOPED or SUPER ADMIN)
     */
    public function uploadBRI(Request $request)
    {
        return $this->uploadBankStatement($request, 'bri');
    }

    /**
     * Upload Bank Statement - BTN (API - COMPANY SCOPED or SUPER ADMIN)
     */
    public function uploadBTN(Request $request)
    {
        return $this->uploadBankStatement($request, 'btn');
    }

    /**
     * Upload Bank Statement - CIMB (API - COMPANY SCOPED or SUPER ADMIN)
     */
    public function uploadCIMB(Request $request)
    {
        return $this->uploadBankStatement($request, 'cimb-niaga');
    }

    /**
     * Core upload method for all banks with multi-file support (API - COMPANY SCOPED or SUPER ADMIN)
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

            // Determine company_id
            $companyId = $user->isSuperAdmin() && isset($validated['company_id'])
                ? $validated['company_id'] 
                : $user->company_id;

            // Get bank by slug (global bank - no company check)
            $bank = Bank::where('slug', $bankSlug)
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

                    // COMPANY SCOPED DUPLICATE CHECK
                    $existingStatement = BankStatement::withTrashed()
                        ->where('company_id', $companyId)
                        ->where('file_hash', $fileHash)
                        ->where('bank_id', $bank->id)
                        ->first();

                    $filename = $this->generateUniqueFilename($originalName);

                    // COMPANY-SPECIFIC STORAGE PATH
                    $path = $file->storeAs(
                        "companies/{$companyId}/bank-statements/{$bankSlug}/" . date('Y/m'),
                        $filename,
                        'local'
                    );

                    if (!Storage::disk('local')->exists($path)) {
                        throw new \Exception("Failed to store file: {$originalName}");
                    }

                    if ($existingStatement) {
                        $this->replaceExistingStatement($existingStatement, $file, $path, $fileHash, $originalName, $filename, $bank, $companyId);
                        
                        $replacedStatements[] = [
                            'id' => $existingStatement->id,
                            'filename' => $originalName,
                            'size' => $this->formatBytes($file->getSize()),
                            'status' => 'replaced',
                        ];
                    } else {
                        $bankStatement = $this->createNewStatement($bank, $file, $path, $fileHash, $originalName, $companyId);
                        
                        $uploadedStatements[] = [
                            'id' => $bankStatement->id,
                            'filename' => $originalName,
                            'size' => $this->formatBytes($file->getSize()),
                            'status' => 'queued',
                        ];
                    }

                } catch (\Exception $e) {
                    Log::error("Failed to upload file via API", [
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

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Bank '{$bankSlug}' not found or not active.",
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Bank statement upload failed via API", [
                'company_id' => $user->company_id,
                'bank' => $bankSlug,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upload status for a bank statement (API - COMPANY SCOPED or SUPER ADMIN)
     */
    public function getStatus(BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
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
     * Retry failed OCR processing (API - COMPANY SCOPED or SUPER ADMIN)
     */
    public function retryOCR(BankStatement $bankStatement)
    {
        // AUTHORIZATION: Super admin or company ownership
        abort_unless(
            auth()->user()->isSuperAdmin() || $bankStatement->company_id === auth()->user()->company_id,
            403
        );

        if ($bankStatement->ocr_status !== 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Only failed statements can be retried.',
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
            'message' => 'OCR processing has been queued again.',
        ]);
    }

    // ========================================
    // Private Helper Methods
    // ========================================

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseFilename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        
        $timestamp = now()->format('Ymd_His');
        $microseconds = now()->format('u');
        $randomString = Str::random(8);
        
        return sprintf(
            '%s_%s_%s_%s.%s',
            $baseFilename,
            $timestamp,
            $microseconds,
            $randomString,
            $extension
        );
    }

    /**
     * Replace existing bank statement
     * + Handle existing document collection
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
        // Delete old file from storage
        if ($existingStatement->file_path && Storage::disk('local')->exists($existingStatement->file_path)) {
            Storage::disk('local')->delete($existingStatement->file_path);
        }

        // Delete existing transactions
        $existingStatement->transactions()->delete();

        // Restore if soft deleted
        if ($existingStatement->trashed()) {
            $existingStatement->restore();
        }

        // Update existing record - reset all data
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
            // Reset financial metadata
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
            // Reset statistics
            'total_transactions' => 0,
            'processed_transactions' => 0,
            'matched_transactions' => 0,
            'unmatched_transactions' => 0,
            'verified_transactions' => 0,
            // Reset reconciliation
            'is_reconciled' => false,
            'reconciled_at' => null,
            'reconciled_by' => null,
            'notes' => null,
        ]);

        // ✅ HANDLE DOCUMENT COLLECTION
        if ($existingStatement->documentItem) {
            // Keep existing collection, reset item status
            $existingStatement->documentItem->update([
                'knowledge_status' => 'pending',
                'knowledge_error' => null,
                'processed_at' => null,
            ]);

            Log::info('✅ Document collection kept for replaced statement', [
                'statement_id' => $existingStatement->id,
                'collection_id' => $existingStatement->documentItem->document_collection_id,
            ]);
        } else {
            // Create new collection if doesn't exist
            $this->createDocumentCollection($existingStatement);
        }

        // Dispatch new OCR job
        ProcessBankStatementOCR::dispatch($existingStatement, $bank->slug)
            ->onQueue('ocr-processing');
    }

    /**
     * Create new bank statement
     * + Auto-create document collection for AI chat
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

        // ✅ AUTO-CREATE DOCUMENT COLLECTION
        $this->createDocumentCollection($bankStatement);

        // Dispatch OCR job
        ProcessBankStatementOCR::dispatch($bankStatement, $bank->slug)
            ->onQueue('ocr-processing');

        return $bankStatement;
    }

    /**
     * ✅ NEW METHOD: Create document collection & item for bank statement
     */
    private function createDocumentCollection(BankStatement $bankStatement): void
    {
        try {
            // Generate collection name from filename & date
            $baseName = pathinfo($bankStatement->original_filename, PATHINFO_FILENAME);
            $bankName = $bankStatement->bank->name ?? 'Bank';
            $collectionName = "{$bankName} - " . Str::limit($baseName, 80) . ' (' . now()->format('d M Y') . ')';
            
            // Create document collection
            $collection = DocumentCollection::create([
                'uuid' => Str::uuid(),
                'company_id' => $bankStatement->company_id,
                'user_id' => $bankStatement->user_id,
                'name' => $collectionName,
                'description' => "Auto-created from: {$bankStatement->original_filename}",
                'color' => $this->getRandomColor(),
                'icon' => 'document-text',
                'document_count' => 1,
                'total_transactions' => 0,
                'total_debit' => 0,
                'total_credit' => 0,
                'is_active' => true,
            ]);

            // Create document item (link statement to collection)
            DocumentItem::create([
                'uuid' => Str::uuid(),
                'company_id' => $bankStatement->company_id,
                'document_collection_id' => $collection->id,
                'bank_statement_id' => $bankStatement->id,
                'document_type' => 'bank_statement',
                'sort_order' => 0,
                'knowledge_status' => 'pending', // Will be updated to 'ready' after OCR
            ]);

            Log::info('✅ Document collection auto-created', [
                'collection_id' => $collection->id,
                'collection_name' => $collection->name,
                'statement_id' => $bankStatement->id,
                'company_id' => $bankStatement->company_id,
            ]);

        } catch (\Exception $e) {
            // Don't fail the upload if collection creation fails
            Log::error('❌ Failed to create document collection', [
                'statement_id' => $bankStatement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * ✅ NEW METHOD: Get random color for collection UI
     */
    private function getRandomColor(): string
    {
        $colors = [
            '#3B82F6', // Blue
            '#10B981', // Green
            '#F59E0B', // Amber
            '#EF4444', // Red
            '#8B5CF6', // Purple
            '#EC4899', // Pink
            '#06B6D4', // Cyan
            '#F97316', // Orange
            '#14B8A6', // Teal
            '#84CC16', // Lime
        ];

        return $colors[array_rand($colors)];
    }

    /**
     * Build upload message
     */
    private function buildUploadMessage(int $uploaded, int $replaced, int $errors): string
    {
        $messages = [];

        if ($uploaded > 0) {
            $messages[] = "{$uploaded} new file(s) uploaded";
        }

        if ($replaced > 0) {
            $messages[] = "{$replaced} file(s) replaced";
        }

        if ($errors > 0) {
            $messages[] = "{$errors} file(s) failed";
        }

        $message = implode(', ', $messages);
        
        if ($uploaded > 0 || $replaced > 0) {
            $message .= ' and queued for OCR processing';
        }

        return $message . '.';
    }

    /**
     * Format bytes to human readable format
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