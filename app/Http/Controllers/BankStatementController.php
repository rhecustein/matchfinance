<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankStatement;
use App\Services\OcrService;
use App\Services\TransactionMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BankStatementController extends Controller
{
    public function __construct(
        private OcrService $ocrService,
        private TransactionMatchingService $matchingService
    ) {}

    /**
     * Display a listing of bank statements
     */
    public function index()
    {
        $statements = BankStatement::with('bank', 'user')
            ->latest('uploaded_at')
            ->paginate(15);

        return view('bank-statements.index', compact('statements'));
    }

    /**
     * Show the form for creating a new bank statement
     */
    public function create()
    {
        $banks = Bank::active()->orderBy('name')->get();
        
        return view('bank-statements.create', compact('banks'));
    }

    /**
     * Store a newly uploaded bank statement
     */
    public function store(Request $request)
    {
        $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
        ]);

        try {
            // Store file
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('bank-statements', $filename, 'private');

            // Create bank statement record
            $statement = BankStatement::create([
                'bank_id' => $request->bank_id,
                'user_id' => auth()->id(),
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'ocr_status' => 'pending',
            ]);

            // Process OCR in background (you can use Queue here)
            $this->ocrService->processStatement($statement);

            return redirect()
                ->route('bank-statements.show', $statement)
                ->with('success', 'Bank statement uploaded and processing started.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to upload bank statement: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified bank statement
     */
    public function show(BankStatement $bankStatement)
    {
        $bankStatement->load(['bank', 'user', 'transactions.subCategory.category.type']);

        $stats = [
            'total' => $bankStatement->transactions()->count(),
            'matched' => $bankStatement->transactions()->matched()->count(),
            'unmatched' => $bankStatement->transactions()->unmatched()->count(),
            'verified' => $bankStatement->transactions()->verified()->count(),
            'low_confidence' => $bankStatement->transactions()->lowConfidence()->count(),
        ];

        return view('bank-statements.show', compact('bankStatement', 'stats'));
    }

    /**
     * Process matching for all transactions in a statement
     */
    public function processMatching(BankStatement $bankStatement)
    {
        if (!$bankStatement->isOcrCompleted()) {
            return back()->with('error', 'OCR processing not completed yet.');
        }

        try {
            $stats = $this->matchingService->processStatementTransactions($bankStatement->id);

            return back()->with('success', "Matching completed. Matched: {$stats['matched']}, Unmatched: {$stats['unmatched']}");

        } catch (\Exception $e) {
            return back()->with('error', 'Matching failed: ' . $e->getMessage());
        }
    }

    /**
     * Download the original PDF file
     */
    public function download(BankStatement $bankStatement)
    {
        if (!Storage::disk('private')->exists($bankStatement->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('private')->download(
            $bankStatement->file_path,
            $bankStatement->original_filename
        );
    }

    /**
     * Delete the bank statement
     */
    public function destroy(BankStatement $bankStatement)
    {
        try {
            // Delete file
            Storage::disk('private')->delete($bankStatement->file_path);
            
            // Delete record (transactions will be cascade deleted)
            $bankStatement->delete();

            return redirect()
                ->route('bank-statements.index')
                ->with('success', 'Bank statement deleted successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete bank statement: ' . $e->getMessage());
        }
    }
}