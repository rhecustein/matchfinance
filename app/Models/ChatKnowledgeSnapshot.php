<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatKnowledgeSnapshot extends Model
{
    use HasFactory;

    const UPDATED_AT = null;
    const CREATED_AT = 'snapshot_created_at';

    protected $fillable = [
        'chat_session_id',
        'transactions_summary',
        'category_breakdown',
        'type_breakdown',
        'account_breakdown',
        'date_range',
        'bank_info',
        'total_transactions',
        'total_debit',
        'total_credit',
        'net_amount',
        'avg_transaction',
        'max_transaction',
        'min_transaction',
        'matched_transactions',
        'unmatched_transactions',
        'verified_transactions',
        'snapshot_created_at',
    ];

    protected $casts = [
        'transactions_summary' => 'array',
        'category_breakdown' => 'array',
        'type_breakdown' => 'array',
        'account_breakdown' => 'array',
        'date_range' => 'array',
        'bank_info' => 'array',
        'total_transactions' => 'integer',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'avg_transaction' => 'decimal:2',
        'max_transaction' => 'decimal:2',
        'min_transaction' => 'decimal:2',
        'matched_transactions' => 'integer',
        'unmatched_transactions' => 'integer',
        'verified_transactions' => 'integer',
        'snapshot_created_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('snapshot_created_at', '>=', now()->subDays($days));
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('chat_session_id', $sessionId);
    }

    public function scopeWithData($query)
    {
        return $query->where('total_transactions', '>', 0);
    }

    public function scopeOrderedByDate($query)
    {
        return $query->orderByDesc('snapshot_created_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor & Mutator
    |--------------------------------------------------------------------------
    */

    public function getDateRangeFormattedAttribute(): string
    {
        $range = $this->date_range;
        
        if (empty($range)) {
            return 'All dates';
        }

        if (isset($range['from']) && isset($range['to'])) {
            return date('M d, Y', strtotime($range['from'])) . ' - ' . 
                   date('M d, Y', strtotime($range['to']));
        }

        return 'Unknown range';
    }

    public function getMatchingRateAttribute(): float
    {
        if ($this->total_transactions === 0) {
            return 0;
        }

        return round(($this->matched_transactions / $this->total_transactions) * 100, 2);
    }

    public function getVerificationRateAttribute(): float
    {
        if ($this->total_transactions === 0) {
            return 0;
        }

        return round(($this->verified_transactions / $this->total_transactions) * 100, 2);
    }

    public function getUnmatchedRateAttribute(): float
    {
        if ($this->total_transactions === 0) {
            return 0;
        }

        return round(($this->unmatched_transactions / $this->total_transactions) * 100, 2);
    }

    public function getFormattedTotalDebitAttribute(): string
    {
        return 'Rp ' . number_format($this->total_debit, 2, ',', '.');
    }

    public function getFormattedTotalCreditAttribute(): string
    {
        return 'Rp ' . number_format($this->total_credit, 2, ',', '.');
    }

    public function getFormattedNetAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->net_amount, 2, ',', '.');
    }

    public function getFormattedAvgTransactionAttribute(): string
    {
        return 'Rp ' . number_format($this->avg_transaction, 2, ',', '.');
    }

    public function getFormattedBankListAttribute(): string
    {
        $banks = $this->getBankNames();
        
        if (empty($banks)) {
            return 'Unknown bank';
        }

        if (count($banks) === 1) {
            return $banks[0];
        }

        return implode(', ', $banks) . ' (' . count($banks) . ' banks)';
    }

    /*
    |--------------------------------------------------------------------------
    | Status Check Methods
    |--------------------------------------------------------------------------
    */

    public function hasData(): bool
    {
        return $this->total_transactions > 0;
    }

    public function hasCategories(): bool
    {
        return !empty($this->category_breakdown);
    }

    public function hasTypes(): bool
    {
        return !empty($this->type_breakdown);
    }

    public function hasAccounts(): bool
    {
        return !empty($this->account_breakdown);
    }

    public function hasUnmatchedTransactions(): bool
    {
        return $this->unmatched_transactions > 0;
    }

    public function isFullyMatched(): bool
    {
        return $this->total_transactions > 0 && 
               $this->matched_transactions === $this->total_transactions;
    }

    public function isFullyVerified(): bool
    {
        return $this->total_transactions > 0 && 
               $this->verified_transactions === $this->total_transactions;
    }

    /*
    |--------------------------------------------------------------------------
    | Data Retrieval Methods
    |--------------------------------------------------------------------------
    */

    public function getBankNames(): array
    {
        $bankInfo = $this->bank_info;
        
        if (empty($bankInfo)) {
            return [];
        }

        if (is_string($bankInfo)) {
            return [$bankInfo];
        }

        if (is_array($bankInfo)) {
            if (isset($bankInfo[0]) && is_string($bankInfo[0])) {
                return $bankInfo;
            }
            
            if (isset($bankInfo['name'])) {
                return [$bankInfo['name']];
            }
            
            if (isset($bankInfo['names'])) {
                return $bankInfo['names'];
            }
        }

        return [];
    }

    public function getCategoryBreakdownSorted(): array
    {
        $breakdown = $this->category_breakdown ?? [];
        
        usort($breakdown, function($a, $b) {
            return ($b['total_debit'] ?? 0) <=> ($a['total_debit'] ?? 0);
        });

        return $breakdown;
    }

    public function getTypeBreakdownSorted(): array
    {
        $breakdown = $this->type_breakdown ?? [];
        
        usort($breakdown, function($a, $b) {
            return ($b['total_debit'] ?? 0) <=> ($a['total_debit'] ?? 0);
        });

        return $breakdown;
    }

    public function getAccountBreakdownSorted(): array
    {
        $breakdown = $this->account_breakdown ?? [];
        
        usort($breakdown, function($a, $b) {
            return ($b['total_debit'] ?? 0) <=> ($a['total_debit'] ?? 0);
        });

        return $breakdown;
    }

    public function getTopCategories(int $limit = 5): array
    {
        return array_slice($this->getCategoryBreakdownSorted(), 0, $limit);
    }

    public function getTopTypes(int $limit = 5): array
    {
        return array_slice($this->getTypeBreakdownSorted(), 0, $limit);
    }

    public function getTopAccounts(int $limit = 5): array
    {
        return array_slice($this->getAccountBreakdownSorted(), 0, $limit);
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics & Summary Methods
    |--------------------------------------------------------------------------
    */

    public function getSummaryStatistics(): array
    {
        return [
            'total_transactions' => number_format($this->total_transactions),
            'total_debit' => $this->formatted_total_debit,
            'total_credit' => $this->formatted_total_credit,
            'net_amount' => $this->formatted_net_amount,
            'avg_transaction' => $this->formatted_avg_transaction,
            'max_transaction' => 'Rp ' . number_format($this->max_transaction, 2, ',', '.'),
            'min_transaction' => 'Rp ' . number_format($this->min_transaction, 2, ',', '.'),
            'matched_rate' => $this->matching_rate . '%',
            'verified_rate' => $this->verification_rate . '%',
            'unmatched_rate' => $this->unmatched_rate . '%',
        ];
    }

    public function getMatchingQualityScore(): int
    {
        return (int) round($this->matching_rate);
    }

    public function getVerificationQualityScore(): int
    {
        return (int) round($this->verification_rate);
    }

    public function getQualityScores(): array
    {
        return [
            'matching' => [
                'score' => $this->getMatchingQualityScore(),
                'rate' => $this->matching_rate,
                'label' => $this->getQualityLabel($this->getMatchingQualityScore()),
            ],
            'verification' => [
                'score' => $this->getVerificationQualityScore(),
                'rate' => $this->verification_rate,
                'label' => $this->getQualityLabel($this->getVerificationQualityScore()),
            ],
        ];
    }

    protected function getQualityLabel(int $score): string
    {
        return match(true) {
            $score >= 90 => 'Excellent',
            $score >= 75 => 'Good',
            $score >= 50 => 'Fair',
            $score >= 25 => 'Poor',
            default => 'Very Poor',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | AI Prompt Methods
    |--------------------------------------------------------------------------
    */

    public function toPromptFormat(): string
    {
        $text = "# Financial Data Context\n\n";
        
        $text .= "## Period Information\n";
        $text .= "Date Range: " . $this->date_range_formatted . "\n";
        $text .= "Banks: " . $this->formatted_bank_list . "\n\n";
        
        $text .= "## Summary Statistics\n";
        $text .= "Total Transactions: " . number_format($this->total_transactions) . "\n";
        $text .= "Total Debit: " . $this->formatted_total_debit . "\n";
        $text .= "Total Credit: " . $this->formatted_total_credit . "\n";
        $text .= "Net Amount: " . $this->formatted_net_amount . "\n";
        $text .= "Average Transaction: " . $this->formatted_avg_transaction . "\n";
        $text .= "Matched: {$this->matched_transactions} ({$this->matching_rate}%)\n";
        $text .= "Verified: {$this->verified_transactions} ({$this->verification_rate}%)\n\n";
        
        if ($this->hasTypes()) {
            $text .= "## Transaction Breakdown by Type\n";
            foreach ($this->getTopTypes(10) as $type) {
                $text .= "- {$type['type']}: {$type['count']} transactions, ";
                $text .= "Rp " . number_format($type['total_debit'] ?? 0, 2, ',', '.') . "\n";
            }
            $text .= "\n";
        }
        
        if ($this->hasCategories()) {
            $text .= "## Transaction Breakdown by Category\n";
            foreach ($this->getTopCategories(10) as $cat) {
                $text .= "- {$cat['category']}: {$cat['count']} transactions, ";
                $text .= "Rp " . number_format($cat['total_debit'] ?? 0, 2, ',', '.') . "\n";
            }
            $text .= "\n";
        }
        
        if ($this->hasAccounts()) {
            $text .= "## Transaction Breakdown by Account\n";
            foreach ($this->getTopAccounts(5) as $acc) {
                $text .= "- {$acc['account']}: {$acc['count']} transactions, ";
                $text .= "Rp " . number_format($acc['total_debit'] ?? 0, 2, ',', '.') . "\n";
            }
        }
        
        return $text;
    }

    public function toCompactPrompt(): string
    {
        return sprintf(
            "Context: %s | Period: %s | Banks: %s | Transactions: %s | Debit: %s | Credit: %s | Net: %s",
            $this->chatSession->title ?? 'Financial Analysis',
            $this->date_range_formatted,
            $this->formatted_bank_list,
            number_format($this->total_transactions),
            $this->formatted_total_debit,
            $this->formatted_total_credit,
            $this->formatted_net_amount
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Display Methods
    |--------------------------------------------------------------------------
    */

    public function getInfo(): array
    {
        return [
            'period' => [
                'date_range' => $this->date_range,
                'formatted' => $this->date_range_formatted,
                'banks' => $this->getBankNames(),
            ],
            'statistics' => $this->getSummaryStatistics(),
            'quality' => $this->getQualityScores(),
            'breakdowns' => [
                'has_categories' => $this->hasCategories(),
                'has_types' => $this->hasTypes(),
                'has_accounts' => $this->hasAccounts(),
                'top_categories' => $this->getTopCategories(3),
                'top_types' => $this->getTopTypes(3),
            ],
            'metadata' => [
                'snapshot_age' => $this->snapshot_created_at->diffForHumans(),
                'is_fully_matched' => $this->isFullyMatched(),
                'is_fully_verified' => $this->isFullyVerified(),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Static Helper Methods
    |--------------------------------------------------------------------------
    */

    public static function createForSession(
        int $sessionId,
        array $data
    ): self {
        return static::create(array_merge([
            'chat_session_id' => $sessionId,
        ], $data));
    }

    public static function getOrCreateForSession(
        int $sessionId,
        array $data
    ): self {
        $snapshot = static::where('chat_session_id', $sessionId)->first();
        
        if ($snapshot) {
            return $snapshot;
        }

        return static::createForSession($sessionId, $data);
    }

    public static function updateOrCreateForSession(
        int $sessionId,
        array $data
    ): self {
        return static::updateOrCreate(
            ['chat_session_id' => $sessionId],
            $data
        );
    }
}