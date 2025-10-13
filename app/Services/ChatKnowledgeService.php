<?php

namespace App\Services;

use App\Models\BankStatement;
use App\Models\DocumentCollection;
use App\Models\ChatSession;
use App\Models\StatementTransaction;
use Illuminate\Support\Facades\DB;

class ChatKnowledgeService
{
    /**
     * Build knowledge context for AI Chat
     * Memanfaatkan data yang sudah diklasifikasi ✅
     */
    public function buildKnowledge(ChatSession $session): array
    {
        if ($session->mode === 'single') {
            return $this->buildSinglePdfKnowledge($session->bank_statement_id, $session);
        }
        
        return $this->buildCollectionKnowledge($session->document_collection_id, $session);
    }
    
    /**
     * Build knowledge dari single PDF
     */
    private function buildSinglePdfKnowledge(int $bankStatementId, ChatSession $session): array
    {
        $statement = BankStatement::with([
            'bank',
            'transactions.type',
            'transactions.category',
            'transactions.subCategory',
            'transactions.matchedKeyword',
            'transactions.account'
        ])->findOrFail($bankStatementId);
        
        // Filter transactions by date range if set
        $query = $statement->transactions();
        
        if ($session->date_from) {
            $query->where('transaction_date', '>=', $session->date_from);
        }
        
        if ($session->date_to) {
            $query->where('transaction_date', '<=', $session->date_to);
        }
        
        $transactions = $query->get();
        
        // Build structured knowledge
        $knowledge = [
            'summary' => [
                'bank_name' => $statement->bank->name,
                'period' => $statement->period_from->format('Y-m-d') . ' to ' . $statement->period_to->format('Y-m-d'),
                'account_number' => $statement->account_number,
                'total_transactions' => $transactions->count(),
                'opening_balance' => $statement->opening_balance,
                'closing_balance' => $statement->closing_balance,
            ],
            
            'transactions' => $this->formatTransactionsForAI($transactions),
            
            'breakdown_by_type' => $this->groupByType($transactions),
            'breakdown_by_category' => $this->groupByCategory($transactions),
            'breakdown_by_subcategory' => $this->groupBySubCategory($transactions),
            'breakdown_by_account' => $this->groupByAccount($transactions),
            
            'statistics' => [
                'total_debit' => $transactions->sum('debit_amount'),
                'total_credit' => $transactions->sum('credit_amount'),
                'avg_transaction' => $transactions->avg('amount'),
                'max_transaction' => $transactions->max('amount'),
                'min_transaction' => $transactions->min('amount'),
            ]
        ];
        
        // Save snapshot
        $this->saveKnowledgeSnapshot($session, $knowledge);
        
        return $knowledge;
    }
    
    /**
     * Build knowledge dari collection (multiple PDFs)
     */
    private function buildCollectionKnowledge(int $collectionId, ChatSession $session): array
    {
        $collection = DocumentCollection::with([
            'items.bankStatement.bank',
            'items.bankStatement.transactions.type',
            'items.bankStatement.transactions.category',
            'items.bankStatement.transactions.subCategory',
            'items.bankStatement.transactions.account'
        ])->findOrFail($collectionId);
        
        // Aggregate all transactions from all PDFs
        $allTransactions = collect();
        
        foreach ($collection->items as $item) {
            $query = $item->bankStatement->transactions();
            
            // Apply filters
            if ($session->date_from) {
                $query->where('transaction_date', '>=', $session->date_from);
            }
            
            if ($session->date_to) {
                $query->where('transaction_date', '<=', $session->date_to);
            }
            
            $allTransactions = $allTransactions->merge($query->get());
        }
        
        $knowledge = [
            'summary' => [
                'collection_name' => $collection->name,
                'total_documents' => $collection->items->count(),
                'banks' => $collection->items->pluck('bankStatement.bank.name')->unique()->values(),
                'date_range' => [
                    'from' => $allTransactions->min('transaction_date'),
                    'to' => $allTransactions->max('transaction_date'),
                ],
                'total_transactions' => $allTransactions->count(),
            ],
            
            'transactions' => $this->formatTransactionsForAI($allTransactions),
            
            'breakdown_by_type' => $this->groupByType($allTransactions),
            'breakdown_by_category' => $this->groupByCategory($allTransactions),
            'breakdown_by_subcategory' => $this->groupBySubCategory($allTransactions),
            'breakdown_by_account' => $this->groupByAccount($allTransactions),
            'breakdown_by_bank' => $this->groupByBank($allTransactions),
            
            'statistics' => [
                'total_debit' => $allTransactions->sum('debit_amount'),
                'total_credit' => $allTransactions->sum('credit_amount'),
                'avg_transaction' => $allTransactions->avg('amount'),
                'max_transaction' => $allTransactions->max('amount'),
                'min_transaction' => $allTransactions->min('amount'),
            ]
        ];
        
        $this->saveKnowledgeSnapshot($session, $knowledge);
        
        return $knowledge;
    }
    
    /**
     * Format transactions for AI consumption
     */
    private function formatTransactionsForAI($transactions): array
    {
        return $transactions->map(function($txn) {
            return [
                'id' => $txn->id,
                'date' => $txn->transaction_date->format('Y-m-d'),
                'description' => $txn->description,
                'amount' => $txn->amount,
                'type' => $txn->type?->name,
                'category' => $txn->category?->name,
                'subcategory' => $txn->subCategory?->name,
                'account' => $txn->account?->name,
                'debit' => $txn->debit_amount,
                'credit' => $txn->credit_amount,
                'balance' => $txn->balance,
                'confidence_score' => $txn->confidence_score,
            ];
        })->toArray();
    }
    
    /**
     * Group transactions by Type
     */
    private function groupByType($transactions): array
    {
        return $transactions->groupBy('type.name')
            ->map(function($group, $typeName) {
                return [
                    'type' => $typeName ?? 'Unclassified',
                    'count' => $group->count(),
                    'total_debit' => $group->sum('debit_amount'),
                    'total_credit' => $group->sum('credit_amount'),
                ];
            })->values()->toArray();
    }
    
    /**
     * Group transactions by Category
     */
    private function groupByCategory($transactions): array
    {
        return $transactions->groupBy('category.name')
            ->map(function($group, $categoryName) {
                return [
                    'category' => $categoryName ?? 'Unclassified',
                    'count' => $group->count(),
                    'total_debit' => $group->sum('debit_amount'),
                    'total_credit' => $group->sum('credit_amount'),
                ];
            })->values()->toArray();
    }
    
    /**
     * Group transactions by SubCategory
     */
    private function groupBySubCategory($transactions): array
    {
        return $transactions->groupBy('subCategory.name')
            ->map(function($group, $subCatName) {
                return [
                    'subcategory' => $subCatName ?? 'Unclassified',
                    'count' => $group->count(),
                    'total_debit' => $group->sum('debit_amount'),
                    'total_credit' => $group->sum('credit_amount'),
                ];
            })->values()->toArray();
    }
    
    /**
     * Group transactions by Account
     */
    private function groupByAccount($transactions): array
    {
        return $transactions->groupBy('account.name')
            ->map(function($group, $accountName) {
                return [
                    'account' => $accountName ?? 'Unclassified',
                    'count' => $group->count(),
                    'total_debit' => $group->sum('debit_amount'),
                    'total_credit' => $group->sum('credit_amount'),
                ];
            })->values()->toArray();
    }
    
    /**
     * Group transactions by Bank
     */
    private function groupByBank($transactions): array
    {
        return $transactions->groupBy('bankStatement.bank.name')
            ->map(function($group, $bankName) {
                return [
                    'bank' => $bankName,
                    'count' => $group->count(),
                    'total_debit' => $group->sum('debit_amount'),
                    'total_credit' => $group->sum('credit_amount'),
                ];
            })->values()->toArray();
    }
    
    /**
     * Save knowledge snapshot
     */
    private function saveKnowledgeSnapshot(ChatSession $session, array $knowledge): void
    {
        DB::table('chat_knowledge_snapshots')->insert([
            'chat_session_id' => $session->id,
            'transactions_summary' => json_encode($knowledge['transactions']),
            'category_breakdown' => json_encode($knowledge['breakdown_by_category']),
            'type_breakdown' => json_encode($knowledge['breakdown_by_type']),
            'date_range' => json_encode($knowledge['summary']['period'] ?? $knowledge['summary']['date_range']),
            'bank_info' => json_encode($knowledge['summary']['bank_name'] ?? $knowledge['summary']['banks']),
            'total_transactions' => $knowledge['summary']['total_transactions'],
            'total_debit' => $knowledge['statistics']['total_debit'],
            'total_credit' => $knowledge['statistics']['total_credit'],
            'created_at' => now(),
        ]);
    }
    
    /**
     * Build AI prompt with knowledge context
     */
    public function buildPrompt(ChatSession $session, string $userQuestion): string
    {
        $knowledge = $this->buildKnowledge($session);
        
        $systemPrompt = <<<PROMPT
You are a financial analysis AI assistant. You have access to bank statement data that has been automatically classified.

**Available Data:**
{$this->formatKnowledgeForPrompt($knowledge)}

**Instructions:**
- Answer questions about transactions, spending patterns, and financial insights
- Reference specific transactions by ID when relevant
- Provide numerical summaries when asked
- Be accurate with numbers and dates
- If data is not available, clearly state that

**User Question:**
{$userQuestion}

Provide a helpful, accurate response based on the available data.
PROMPT;

        return $systemPrompt;
    }
    
    /**
     * Format knowledge for AI prompt
     */
    private function formatKnowledgeForPrompt(array $knowledge): string
    {
        $formatted = "## Summary\n";
        $formatted .= json_encode($knowledge['summary'], JSON_PRETTY_PRINT) . "\n\n";
        
        $formatted .= "## Statistics\n";
        $formatted .= json_encode($knowledge['statistics'], JSON_PRETTY_PRINT) . "\n\n";
        
        $formatted .= "## Breakdown by Category\n";
        $formatted .= json_encode($knowledge['breakdown_by_category'], JSON_PRETTY_PRINT) . "\n\n";
        
        // Limit transactions to avoid token overflow
        $formatted .= "## Recent Transactions (sample)\n";
        $sampleTransactions = array_slice($knowledge['transactions'], 0, 50);
        $formatted .= json_encode($sampleTransactions, JSON_PRETTY_PRINT);
        
        return $formatted;
    }
}