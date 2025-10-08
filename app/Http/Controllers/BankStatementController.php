<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankStatement;
use App\Models\StatementTransaction;
use App\Models\Payment;
use App\Models\Keyword;
use App\Models\SubCategory;
use App\Models\AuditLog;
use App\Services\OcrService;
use App\Services\TransactionMatchingService;
use App\Services\MachineLearningService;
use App\Services\BlockchainVerificationService;
use App\Services\RealTimeAnalyticsService;
use App\Services\FraudDetectionService;
use App\Services\NotificationService;
use App\Jobs\ProcessBankStatementOcr;
use App\Jobs\MatchTransactionsJob;
use App\Jobs\GenerateReportsJob;
use App\Events\BankStatementProcessed;
use App\Events\SuspiciousActivityDetected;
use App\Repositories\BankStatementRepository;
use App\Repositories\TransactionRepository;
use App\Http\Requests\BankStatementUploadRequest;
use App\Http\Resources\BankStatementResource;
use App\Http\Resources\TransactionResource;
use App\Exports\AdvancedBankStatementExport;
use App\Imports\BankStatementImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Intervention\Image\Facades\Image;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BankStatementController extends Controller
{
    // Advanced configuration constants
    private const CACHE_TTL = 300;
    private const REDIS_TTL = 3600;
    private const OCR_MAX_RETRIES = 5;
    private const OCR_TIMEOUT = 300;
    private const CHUNK_SIZE = 500;
    private const BATCH_SIZE = 100;
    private const MAX_FILE_SIZE = 52428800; // 50MB
    private const ALLOWED_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'application/vnd.ms-excel'];
    private const CONFIDENCE_THRESHOLD = 0.75;
    private const FRAUD_THRESHOLD = 0.85;
    private const RATE_LIMIT_PER_MINUTE = 10;
    
    // Dependency injection untuk services
    public function __construct(
        private OcrService $ocrService,
        private TransactionMatchingService $matchingService,
        private NotificationService $notificationService,
        private BankStatementRepository $statementRepo,
    ) {
        // Middleware setup
        $this->middleware('auth');
        $this->middleware('verified');
        $this->middleware('throttle:60,1')->only(['uploadAndProcess']);
        $this->middleware('permission:manage-statements')->only(['destroy', 'massDelete']);
        $this->middleware('log.activity');
    }

    /**
     * Advanced index dengan real-time filtering, sorting, dan analytics
     */
    public function index(Request $request)
    {
        try {
            // Rate limiting check
            if (!$this->checkRateLimit('index', 30)) {
                return response()->json(['error' => 'Rate limit exceeded'], 429);
            }

            // Advanced query builder dengan multiple filters
            $query = $this->buildComplexQuery($request);
            
            // Real-time analytics integration
            $analytics = $this->analyticsService->getRealtimeStats([
                'user_id' => auth()->id(),
                'filters' => $request->all(),
                'time_range' => $request->get('time_range', '30d')
            ]);
            
            // Pagination dengan cursor-based untuk performance
            if ($request->get('use_cursor', false)) {
                $statements = $query->cursorPaginate(
                    $request->get('per_page', 25)
                )->withQueryString();
            } else {
                $statements = $query->paginate(
                    $request->get('per_page', 25)
                )->withQueryString();
            }
            
            // Transform dengan resource collection
            $statementsResource = BankStatementResource::collection($statements);
            
            // Additional data untuk UI
            $additionalData = $this->getAdditionalIndexData($request);
            
            // Response format berdasarkan request type
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'data' => $statementsResource,
                    'analytics' => $analytics,
                    'filters' => $additionalData['filters'],
                    'meta' => [
                        'total' => $statements->total(),
                        'per_page' => $statements->perPage(),
                        'current_page' => $statements->currentPage(),
                        'processing_time' => round(microtime(true) - LARAVEL_START, 3)
                    ]
                ]);
            }
            
            return view('bank-statements.index', compact(
                'statements',
                'analytics',
                'additionalData'
            ));
            
        } catch (\Exception $e) {
            $this->handleException($e, 'index');
            return back()->with('error', 'Failed to load statements');
        }
    }

    /**
     * Ultra-complex upload dengan OCR, AI processing, blockchain verification
     */
    public function uploadAndProcess(BankStatementUploadRequest $request)
    {
        // Start transaction dengan savepoint
        DB::beginTransaction();
        $savepoint = DB::connection()->getPdo()->exec('SAVEPOINT upload_process');
        
        try {
            // 1. Validate dan prepare file
            $validatedData = $this->validateAndPrepareUpload($request);
            
            // 2. Fraud detection pada file
            $fraudCheck = $this->fraudService->scanFile($validatedData['file']);
            if ($fraudCheck['risk_score'] > self::FRAUD_THRESHOLD) {
                event(new SuspiciousActivityDetected(auth()->user(), $fraudCheck));
                throw new \Exception('Suspicious file detected');
            }
            
            // 3. Advanced duplicate detection dengan fuzzy matching
            $duplicateCheck = $this->advancedDuplicateCheck($validatedData);
            if ($duplicateCheck['is_duplicate']) {
                return $this->handleDuplicateWithOptions($duplicateCheck);
            }
            
            // 4. Store file dengan encryption dan versioning
            $storedFile = $this->storeFileWithVersioning($validatedData);
            
            // 5. Create bank statement record dengan metadata
            $bankStatement = $this->createBankStatementRecord($storedFile, $validatedData);
            
            // 6. Queue multiple processing jobs
            $this->queueProcessingJobs($bankStatement, $validatedData);
            
            // 7. Blockchain verification untuk audit trail
            if (config('app.blockchain_enabled')) {
                $this->blockchainService->recordUpload($bankStatement);
            }
            
            // 8. Real-time notification
            $this->notificationService->broadcastUploadStatus($bankStatement, 'processing');
            
            // 9. Generate preview jika PDF
            if ($validatedData['file']->getMimeType() === 'application/pdf') {
                $this->generatePdfPreview($bankStatement);
            }
            
            DB::commit();
            
            // 10. Return response dengan WebSocket channel untuk real-time updates
            return response()->json([
                'success' => true,
                'message' => 'Upload successful. Processing started.',
                'data' => [
                    'statement_id' => $bankStatement->id,
                    'tracking_id' => $bankStatement->tracking_id,
                    'websocket_channel' => "private-statement.{$bankStatement->id}",
                    'estimated_time' => $this->estimateProcessingTime($validatedData),
                    'preview_url' => route('bank-statements.preview', $bankStatement),
                    'status_url' => route('bank-statements.status', $bankStatement),
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->handleUploadFailure($e, $validatedData ?? []);
            
            return response()->json([
                'success' => false,
                'message' => $this->getUserFriendlyError($e),
                'error_code' => $this->getErrorCode($e),
                'support_ticket_id' => $this->createSupportTicket($e)
            ], 422);
        }
    }

    /**
     * AI-powered smart matching dengan machine learning
     */
    public function smartMatch(BankStatement $bankStatement)
    {
        $this->authorize('update', $bankStatement);
        
        try {
            // 1. Load historical matching data untuk training
            $trainingData = $this->loadTrainingData($bankStatement);
            
            // 2. Train/update model jika perlu
            if ($this->shouldUpdateModel($trainingData)) {
                $this->mlService->updateMatchingModel($trainingData);
            }
            
            // 3. Get unmatched transactions
            $unmatchedTransactions = $bankStatement->transactions()
                ->whereNull('matched_keyword_id')
                ->with(['bankStatement.bank'])
                ->get();
            
            // 4. Batch processing dengan parallel execution
            $chunks = $unmatchedTransactions->chunk(self::BATCH_SIZE);
            $results = collect();
            
            foreach ($chunks as $chunk) {
                $batchResults = $this->mlService->predictMatches($chunk, [
                    'confidence_threshold' => self::CONFIDENCE_THRESHOLD,
                    'use_context' => true,
                    'learn_from_feedback' => true,
                    'parallel_processing' => true
                ]);
                
                $results = $results->merge($batchResults);
            }
            
            // 5. Apply matches dengan confidence scoring
            $matchingResults = $this->applySmartMatches($results, $bankStatement);
            
            // 6. Generate suggestions untuk low confidence matches
            $suggestions = $this->generateMatchingSuggestions($matchingResults['low_confidence']);
            
            // 7. Update statistics dan cache
            $this->updateStatisticsAndCache($bankStatement);
            
            // 8. Generate detailed report
            $report = $this->generateMatchingReport($matchingResults, $suggestions);
            
            return response()->json([
                'success' => true,
                'results' => $matchingResults,
                'suggestions' => $suggestions,
                'report' => $report,
                'improvement' => $this->calculateImprovement($bankStatement)
            ]);
            
        } catch (\Exception $e) {
            $this->handleException($e, 'smart_match');
            return response()->json(['error' => 'Smart matching failed'], 500);
        }
    }

    /**
     * Complex reconciliation dengan multiple payment sources
     */
    public function advancedReconciliation(BankStatement $bankStatement)
    {
        $this->authorize('reconcile', $bankStatement);
        
        DB::beginTransaction();
        
        try {
            // 1. Load semua sumber pembayaran
            $paymentSources = $this->loadPaymentSources($bankStatement);
            
            // 2. Multi-dimensional matching
            $matchingMatrix = $this->buildMatchingMatrix(
                $bankStatement->transactions,
                $paymentSources
            );
            
            // 3. Optimize matching dengan Hungarian algorithm
            $optimalMatches = $this->optimizeMatching($matchingMatrix);
            
            // 4. Apply reconciliation dengan rollback capability
            $reconciliationResult = $this->applyReconciliation($optimalMatches);
            
            // 5. Handle partial matches dan split transactions
            $partialMatches = $this->handlePartialMatches($reconciliationResult['unmatched']);
            
            // 6. Generate reconciliation report
            $report = $this->generateReconciliationReport($reconciliationResult, $partialMatches);
            
            // 7. Blockchain record untuk audit
            if (config('app.blockchain_enabled')) {
                $this->blockchainService->recordReconciliation($report);
            }
            
            DB::commit();
            
            // 8. Send notifications
            $this->notifyReconciliationComplete($bankStatement, $report);
            
            return view('bank-statements.reconciliation-report', compact(
                'bankStatement',
                'report',
                'reconciliationResult',
                'partialMatches'
            ));
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->handleException($e, 'reconciliation');
            return back()->with('error', 'Reconciliation failed');
        }
    }

    /**
     * Real-time collaborative editing dengan version control
     */
    public function collaborativeEdit(Request $request, BankStatement $bankStatement)
    {
        $this->authorize('edit', $bankStatement);
        
        try {
            // 1. Lock check untuk concurrent editing
            $lock = Cache::lock("statement_edit_{$bankStatement->id}", 300);
            
            if (!$lock->get()) {
                $currentEditor = $this->getCurrentEditor($bankStatement);
                return response()->json([
                    'locked' => true,
                    'locked_by' => $currentEditor,
                    'message' => 'Document is being edited by another user'
                ], 423);
            }
            
            // 2. Create version snapshot
            $version = $this->createVersionSnapshot($bankStatement);
            
            // 3. Apply changes dengan conflict resolution
            $changes = $request->get('changes', []);
            $conflicts = $this->detectConflicts($bankStatement, $changes);
            
            if (!empty($conflicts)) {
                return $this->handleConflicts($conflicts, $bankStatement);
            }
            
            // 4. Apply changes transactionally
            $result = $this->applyChanges($bankStatement, $changes);
            
            // 5. Broadcast changes ke collaborators
            $this->broadcastChanges($bankStatement, $result);
            
            // 6. Update audit log
            $this->logCollaborativeEdit($bankStatement, $changes, $version);
            
            $lock->release();
            
            return response()->json([
                'success' => true,
                'version' => $version,
                'applied_changes' => $result,
                'next_version' => $bankStatement->version + 1
            ]);
            
        } catch (\Exception $e) {
            $lock->release();
            $this->handleException($e, 'collaborative_edit');
            return response()->json(['error' => 'Edit failed'], 500);
        }
    }

    /**
     * Advanced analytics dengan predictive insights
     */
    public function advancedAnalytics(BankStatement $bankStatement)
    {
        $this->authorize('view', $bankStatement);
        
        try {
            // 1. Historical analysis
            $historicalData = $this->analyticsService->getHistoricalAnalysis(
                $bankStatement,
                Carbon::now()->subMonths(12)
            );
            
            // 2. Predictive modeling
            $predictions = $this->mlService->predictFutureTransactions([
                'statement_id' => $bankStatement->id,
                'horizon' => 30, // days
                'confidence_intervals' => true
            ]);
            
            // 3. Anomaly detection
            $anomalies = $this->fraudService->detectAnomalies($bankStatement);
            
            // 4. Categorization insights
            $categorizationInsights = $this->getCategorizationInsights($bankStatement);
            
            // 5. Cash flow analysis
            $cashFlowAnalysis = $this->analyzeCashFlow($bankStatement);
            
            // 6. Comparative analysis
            $comparativeAnalysis = $this->compareWithPeerData($bankStatement);
            
            // 7. Risk assessment
            $riskAssessment = $this->assessFinancialRisk($bankStatement);
            
            // 8. Generate interactive dashboard data
            $dashboardData = $this->prepareDashboardData([
                'historical' => $historicalData,
                'predictions' => $predictions,
                'anomalies' => $anomalies,
                'categorization' => $categorizationInsights,
                'cashflow' => $cashFlowAnalysis,
                'comparative' => $comparativeAnalysis,
                'risk' => $riskAssessment
            ]);
            
            return view('bank-statements.analytics', compact(
                'bankStatement',
                'dashboardData'
            ));
            
        } catch (\Exception $e) {
            $this->handleException($e, 'analytics');
            return back()->with('error', 'Failed to generate analytics');
        }
    }

    /**
     * Bulk operations dengan progress tracking
     */
    public function bulkOperations(Request $request)
    {
        $this->authorize('bulk-operations');
        
        $request->validate([
            'operation' => 'required|in:categorize,verify,export,delete,archive,merge',
            'statement_ids' => 'required|array|min:1',
            'statement_ids.*' => 'exists:bank_statements,id',
            'options' => 'nullable|array'
        ]);
        
        try {
            // 1. Create batch job
            $batchId = Str::uuid();
            $totalItems = count($request->statement_ids);
            
            // 2. Store job metadata in Redis
            Redis::setex(
                "batch:{$batchId}",
                3600,
                json_encode([
                    'operation' => $request->operation,
                    'total' => $totalItems,
                    'processed' => 0,
                    'failed' => 0,
                    'status' => 'processing',
                    'user_id' => auth()->id()
                ])
            );
            
            // 3. Chunk and queue operations
            $chunks = collect($request->statement_ids)->chunk(50);
            
            foreach ($chunks as $index => $chunk) {
                $this->dispatchBulkOperation(
                    $request->operation,
                    $chunk->toArray(),
                    $request->options ?? [],
                    $batchId,
                    $index
                );
            }
            
            // 4. Return batch tracking info
            return response()->json([
                'success' => true,
                'batch_id' => $batchId,
                'total_items' => $totalItems,
                'estimated_time' => $this->estimateBulkProcessingTime($request->operation, $totalItems),
                'tracking_url' => route('bulk-operations.status', $batchId),
                'websocket_channel' => "private-bulk.{$batchId}"
            ]);
            
        } catch (\Exception $e) {
            $this->handleException($e, 'bulk_operations');
            return response()->json(['error' => 'Bulk operation failed'], 500);
        }
    }

    /**
     * Export dengan multiple format dan customization
     */
    public function advancedExport(Request $request, BankStatement $bankStatement)
    {
        $this->authorize('export', $bankStatement);
        
        $request->validate([
            'format' => 'required|in:xlsx,csv,pdf,json,xml,html',
            'template' => 'nullable|string',
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
            'include_charts' => 'boolean',
            'encrypt' => 'boolean',
            'compress' => 'boolean'
        ]);
        
        try {
            // 1. Prepare export data dengan filters
            $exportData = $this->prepareExportData($bankStatement, $request->all());
            
            // 2. Generate export berdasarkan format
            $exporter = match($request->format) {
                'xlsx' => new AdvancedBankStatementExport($exportData, 'xlsx'),
                'csv' => new AdvancedBankStatementExport($exportData, 'csv'),
                'pdf' => $this->generatePdfExport($exportData, $request),
                'json' => $this->generateJsonExport($exportData),
                'xml' => $this->generateXmlExport($exportData),
                'html' => $this->generateHtmlExport($exportData, $request),
                default => throw new \Exception('Invalid format')
            };
            
            // 3. Apply encryption jika diminta
            if ($request->boolean('encrypt')) {
                $exporter = $this->encryptExport($exporter);
            }
            
            // 4. Compress jika diminta
            if ($request->boolean('compress')) {
                $exporter = $this->compressExport($exporter);
            }
            
            // 5. Log export activity
            $this->logExportActivity($bankStatement, $request->all());
            
            // 6. Generate filename
            $filename = $this->generateExportFilename($bankStatement, $request->format);
            
            return $exporter->download($filename);
            
        } catch (\Exception $e) {
            $this->handleException($e, 'export');
            return back()->with('error', 'Export failed');
        }
    }

    // ===================== HELPER METHODS =====================

    /**
     * Build complex query dengan multiple joins dan conditions
     */
    private function buildComplexQuery(Request $request)
    {
        $query = BankStatement::with([
            'bank:id,name,code',
            'user:id,name,email',
            'transactions' => function($q) {
                $q->select('id', 'bank_statement_id', 'transaction_type', 'debit_amount', 'credit_amount')
                  ->withCount('categories');
            }
        ])
        ->withCount(['transactions', 'matchedTransactions', 'verifiedTransactions'])
        ->withSum('transactions as total_debit', 'debit_amount')
        ->withSum('transactions as total_credit', 'credit_amount')
        ->withAvg('transactions as avg_confidence', 'confidence_score');
        
        // Apply filters
        $this->applyFilters($query, $request);
        
        // Apply sorting
        $this->applySorting($query, $request);
        
        // Apply search
        if ($request->filled('search')) {
            $this->applySearch($query, $request->search);
        }
        
        return $query;
    }

    /**
     * Advanced duplicate detection dengan fuzzy matching
     */
    private function advancedDuplicateCheck(array $data): array
    {
        // Hash-based check
        $fileHash = hash_file('sha256', $data['file']->getRealPath());
        
        // Check exact hash match
        $exactMatch = BankStatement::where('file_hash', $fileHash)->first();
        if ($exactMatch) {
            return [
                'is_duplicate' => true,
                'type' => 'exact',
                'match' => $exactMatch,
                'confidence' => 1.0
            ];
        }
        
        // Fuzzy matching untuk similar content
        $similarStatements = BankStatement::where('bank_id', $data['bank_id'])
            ->where('file_size', '>', $data['file']->getSize() * 0.9)
            ->where('file_size', '<', $data['file']->getSize() * 1.1)
            ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
            ->get();
        
        foreach ($similarStatements as $statement) {
            $similarity = $this->calculateSimilarity($data['file'], $statement);
            if ($similarity > 0.95) {
                return [
                    'is_duplicate' => true,
                    'type' => 'fuzzy',
                    'match' => $statement,
                    'confidence' => $similarity
                ];
            }
        }
        
        return ['is_duplicate' => false];
    }

    /**
     * Handle exceptions dengan logging dan notification
     */
    private function handleException(\Exception $e, string $context): void
    {
        $errorId = Str::uuid();
        
        Log::error("Exception in {$context}", [
            'error_id' => $errorId,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        
        // Notify admin untuk critical errors
        if ($this->isCriticalError($e)) {
            $this->notificationService->notifyAdmins('critical_error', [
                'error_id' => $errorId,
                'context' => $context,
                'message' => $e->getMessage()
            ]);
        }
        
        // Store in database untuk tracking
        DB::table('error_logs')->insert([
            'id' => $errorId,
            'context' => $context,
            'message' => $e->getMessage(),
            'exception_class' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'user_id' => auth()->id(),
            'created_at' => now()
        ]);
    }

    /**
     * Check rate limiting
     */
    private function checkRateLimit(string $action, int $maxAttempts): bool
    {
        $key = 'rate_limit:' . auth()->id() . ':' . $action;
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return false;
        }
        
        RateLimiter::hit($key, 60);
        return true;
    }
}