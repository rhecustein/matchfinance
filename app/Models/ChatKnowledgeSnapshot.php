<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatKnowledgeSnapshot extends Model
{
    use HasFactory;

    // This table doesn't have updated_at, only created_at (snapshot_created_at)
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

    /**
     * Get the chat session this snapshot belongs to
     */
    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get summary statistics
     */
    public function getSummaryStatistics(): array
    {
        return [
            'total_transactions' => number_format($this->total_transactions),
            'total_debit' => 'Rp ' . number_format($this->total_debit, 2),
            'total_credit' => 'Rp ' . number_format($this->total_credit, 2),
            'net_amount' => 'Rp ' . number_format($this->net_amount, 2),
            'avg_transaction' => 'Rp ' . number_format($this->avg_transaction, 2),
            'matched_rate' => $this->total_transactions > 0 
                ? round(($this->matched_transactions / $this->total_transactions) * 100, 2) . '%'
                : '0%',
            'verified_rate' => $this->total_transactions > 0
                ? round(($this->verified_transactions / $this->total_transactions) * 100, 2) . '%'
                : '0%',
        ];
    }

    /**
     * Get date range formatted
     */
    public function getDateRangeFormatted(): string
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

    /**
     * Get category breakdown sorted by amount
     */
    public function getCategoryBreakdownSorted(): array
    {
        $breakdown = $this->category_breakdown ?? [];
        
        usort($breakdown, function($a, $b) {
            return ($b['total_debit'] ?? 0) <=> ($a['total_debit'] ?? 0);
        });

        return $breakdown;
    }

    /**
     * Get type breakdown sorted by amount
     */
    public function getTypeBreakdownSorted(): array
    {
        $breakdown = $this->type_breakdown ?? [];
        
        usort($breakdown, function($a, $b) {
            return ($b['total_debit'] ?? 0) <=> ($a['total_debit'] ?? 0);
        });

        return $breakdown;
    }

    /**
     * Get top categories (by spending)
     */
    public function getTopCategories(int $limit = 5): array
    {
        return array_slice($this->getCategoryBreakdownSorted(), 0, $limit);
    }

    /**
     * Get top types (by spending)
     */
    public function getTopTypes(int $limit = 5): array
    {
        return array_slice($this->getTypeBreakdownSorted(), 0, $limit);
    }

    /**
     * Get bank names
     */
    public function getBankNames(): array
    {
        $bankInfo = $this->bank_info;
        
        if (is_array($bankInfo)) {
            // If it's an array of bank names
            if (isset($bankInfo[0]) && is_string($bankInfo[0])) {
                return $bankInfo;
            }
            
            // If it's a single bank name
            if (is_string($bankInfo)) {
                return [$bankInfo];
            }
        }

        return [];
    }

    /**
     * Get formatted bank list
     */
    public function getFormattedBankList(): string
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

    /**
     * Check if snapshot has data
     */
    public function hasData(): bool
    {
        return $this->total_transactions > 0;
    }

    /**
     * Get matching quality score (0-100)
     */
    public function getMatchingQualityScore(): int
    {
        if ($this->total_transactions === 0) {
            return 0;
        }

        $matchedRate = ($this->matched_transactions / $this->total_transactions) * 100;
        
        return (int) round($matchedRate);
    }

    /**
     * Get verification quality score (0-100)
     */
    public function getVerificationQualityScore(): int
    {
        if ($this->total_transactions === 0) {
            return 0;
        }

        $verifiedRate = ($this->verified_transactions / $this->total_transactions) * 100;
        
        return (int) round($verifiedRate);
    }

    /**
     * Convert snapshot to formatted text for AI prompt
     */
    public function toPromptFormat(): string
    {
        $text = "# Financial Data Context\n\n";
        
        $text .= "## Period Information\n";
        $text .= "Date Range: " . $this->getDateRangeFormatted() . "\n";
        $text .= "Banks: " . $this->getFormattedBankList() . "\n\n";
        
        $text .= "## Summary Statistics\n";
        $text .= "Total Transactions: " . number_format($this->total_transactions) . "\n";
        $text .= "Total Debit: Rp " . number_format($this->total_debit, 2) . "\n";
        $text .= "Total Credit: Rp " . number_format($this->total_credit, 2) . "\n";
        $text .= "Net Amount: Rp " . number_format($this->net_amount, 2) . "\n";
        $text .= "Average Transaction: Rp " . number_format($this->avg_transaction, 2) . "\n\n";
        
        $text .= "## Transaction Breakdown by Category\n";
        foreach ($this->getTopCategories(10) as $cat) {
            $text .= "- {$cat['category']}: {$cat['count']} transactions, ";
            $text .= "Rp " . number_format($cat['total_debit'] ?? 0, 2) . "\n";
        }
        
        return $text;
    }
}