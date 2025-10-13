<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

class MatchingLog extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'statement_transaction_id',
        'keyword_id',
        'matched_text',
        'confidence_score',
        'match_type',
        'match_pattern',
        'is_selected',
        'priority_score',
        'match_reason',
        'match_metadata',
        'keyword_snapshot',
        'matching_engine',
        'processing_time_ms',
        'matched_at',
        'matched_from_ip',
    ];

    protected $casts = [
        'confidence_score' => 'integer',
        'is_selected' => 'boolean',
        'priority_score' => 'integer',
        'match_metadata' => 'array',
        'keyword_snapshot' => 'array',
        'processing_time_ms' => 'integer',
        'matched_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
            
            if (empty($log->matched_at)) {
                $log->matched_at = now();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function statementTransaction(): BelongsTo
    {
        return $this->belongsTo(StatementTransaction::class);
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->keyword->subCategory();
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeRecent($query, $limit = 100)
    {
        return $query->orderBy('matched_at', 'desc')
                     ->limit($limit);
    }

    public function scopeByKeyword($query, $keywordId)
    {
        return $query->where('keyword_id', $keywordId);
    }

    public function scopeForTransaction($query, $transactionId)
    {
        return $query->where('statement_transaction_id', $transactionId);
    }

    public function scopeSelected($query)
    {
        return $query->where('is_selected', true);
    }

    public function scopeNotSelected($query)
    {
        return $query->where('is_selected', false);
    }

    public function scopeHighConfidence($query, $threshold = 90)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopeMediumConfidence($query)
    {
        return $query->whereBetween('confidence_score', [70, 89]);
    }

    public function scopeLowConfidence($query, $threshold = 70)
    {
        return $query->where('confidence_score', '<', $threshold);
    }

    public function scopeByMatchType($query, $type)
    {
        return $query->where('match_type', $type);
    }

    public function scopeByEngine($query, $engine)
    {
        return $query->where('matching_engine', $engine);
    }

    public function scopeAutomatic($query)
    {
        return $query->where('matching_engine', 'auto');
    }

    public function scopeManual($query)
    {
        return $query->where('matching_engine', 'manual');
    }

    public function scopeAiPowered($query)
    {
        return $query->where('matching_engine', 'ai');
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('matched_at', [$from, $to]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('matched_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('matched_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('matched_at', now()->year)
                     ->whereMonth('matched_at', now()->month);
    }

    public function scopeFastMatches($query, $threshold = 100)
    {
        return $query->where('processing_time_ms', '<=', $threshold);
    }

    public function scopeSlowMatches($query, $threshold = 1000)
    {
        return $query->where('processing_time_ms', '>', $threshold);
    }

    public function scopeOrderedByConfidence($query)
    {
        return $query->orderBy('confidence_score', 'desc');
    }

    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority_score', 'desc')
                     ->orderBy('confidence_score', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Status Methods
    |--------------------------------------------------------------------------
    */

    public function isSelected(): bool
    {
        return $this->is_selected;
    }

    public function hasHighConfidence(): bool
    {
        return $this->confidence_score >= 90;
    }

    public function hasMediumConfidence(): bool
    {
        return $this->confidence_score >= 70 && $this->confidence_score < 90;
    }

    public function hasLowConfidence(): bool
    {
        return $this->confidence_score < 70;
    }

    public function isAutomatic(): bool
    {
        return $this->matching_engine === 'auto';
    }

    public function isManual(): bool
    {
        return $this->matching_engine === 'manual';
    }

    public function isAiPowered(): bool
    {
        return $this->matching_engine === 'ai';
    }

    public function isFastMatch(): bool
    {
        return $this->processing_time_ms && $this->processing_time_ms <= 100;
    }

    public function isSlowMatch(): bool
    {
        return $this->processing_time_ms && $this->processing_time_ms > 1000;
    }

    /*
    |--------------------------------------------------------------------------
    | Action Methods
    |--------------------------------------------------------------------------
    */

    public function markAsSelected(): bool
    {
        // Unmark other selected matches for this transaction
        static::where('statement_transaction_id', $this->statement_transaction_id)
              ->where('id', '!=', $this->id)
              ->update(['is_selected' => false]);

        return $this->update(['is_selected' => true]);
    }

    public function unmarkAsSelected(): bool
    {
        return $this->update(['is_selected' => false]);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function confidenceLabel(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->confidence_score >= 90) return 'Very High';
                if ($this->confidence_score >= 70) return 'High';
                if ($this->confidence_score >= 50) return 'Medium';
                if ($this->confidence_score >= 30) return 'Low';
                return 'Very Low';
            }
        );
    }

    public function confidenceBadgeClass(): Attribute
    {
        return Attribute::make(
            get: function() {
                if ($this->confidence_score >= 90) return 'bg-green-100 text-green-800';
                if ($this->confidence_score >= 70) return 'bg-blue-100 text-blue-800';
                if ($this->confidence_score >= 50) return 'bg-yellow-100 text-yellow-800';
                if ($this->confidence_score >= 30) return 'bg-orange-100 text-orange-800';
                return 'bg-red-100 text-red-800';
            }
        );
    }

    public function matchTypeLabel(): Attribute
    {
        $labels = [
            'exact' => 'Exact Match',
            'contains' => 'Contains',
            'starts_with' => 'Starts With',
            'ends_with' => 'Ends With',
            'regex' => 'Regular Expression',
        ];

        return Attribute::make(
            get: fn() => $labels[$this->match_type] ?? 'Unknown'
        );
    }

    public function engineLabel(): Attribute
    {
        $labels = [
            'auto' => 'Automatic',
            'manual' => 'Manual',
            'ai' => 'AI-Powered',
            'ml' => 'Machine Learning',
            'rule' => 'Rule-Based',
        ];

        return Attribute::make(
            get: fn() => $labels[$this->matching_engine] ?? ucfirst($this->matching_engine)
        );
    }

    public function engineBadgeClass(): Attribute
    {
        $classes = [
            'auto' => 'bg-blue-100 text-blue-800',
            'manual' => 'bg-purple-100 text-purple-800',
            'ai' => 'bg-indigo-100 text-indigo-800',
            'ml' => 'bg-pink-100 text-pink-800',
            'rule' => 'bg-gray-100 text-gray-800',
        ];

        return Attribute::make(
            get: fn() => $classes[$this->matching_engine] ?? 'bg-gray-100 text-gray-800'
        );
    }

    public function formattedProcessingTime(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->processing_time_ms) return 'N/A';
                
                if ($this->processing_time_ms < 1000) {
                    return $this->processing_time_ms . 'ms';
                }
                
                return round($this->processing_time_ms / 1000, 2) . 's';
            }
        );
    }

    public function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_selected 
                ? 'bg-green-100 text-green-800' 
                : 'bg-gray-100 text-gray-800'
        );
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_selected ? 'Selected' : 'Not Selected'
        );
    }

    public function keywordName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->keyword?->keyword ?? 'Unknown Keyword'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function getDetails(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'matched_text' => $this->matched_text,
            'confidence_score' => $this->confidence_score,
            'confidence_label' => $this->confidence_label,
            'match_type' => $this->match_type,
            'match_type_label' => $this->match_type_label,
            'is_selected' => $this->is_selected,
            'priority_score' => $this->priority_score,
            'match_reason' => $this->match_reason,
            'matching_engine' => $this->matching_engine,
            'engine_label' => $this->engine_label,
            'processing_time' => $this->formatted_processing_time,
            'matched_at' => $this->matched_at->format('Y-m-d H:i:s'),
            'keyword' => $this->keyword_name,
        ];
    }

    public function getMatchInfo(): array
    {
        return [
            'pattern' => $this->match_pattern,
            'metadata' => $this->match_metadata,
            'keyword_snapshot' => $this->keyword_snapshot,
            'matched_text' => $this->matched_text,
            'match_type' => $this->match_type,
        ];
    }

    public static function createLog(array $data): self
    {
        return static::create(array_merge($data, [
            'matched_at' => now(),
            'matched_from_ip' => request()->ip(),
        ]));
    }

    public static function getAverageConfidence($companyId = null): float
    {
        $query = static::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return round($query->avg('confidence_score') ?? 0, 2);
    }

    public static function getAverageProcessingTime($companyId = null): float
    {
        $query = static::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return round($query->avg('processing_time_ms') ?? 0, 2);
    }

    public static function getEngineStatistics($companyId = null): array
    {
        $query = static::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return $query->selectRaw('matching_engine, COUNT(*) as count, AVG(confidence_score) as avg_confidence')
                     ->groupBy('matching_engine')
                     ->get()
                     ->mapWithKeys(function($item) {
                         return [$item->matching_engine => [
                             'count' => $item->count,
                             'avg_confidence' => round($item->avg_confidence, 2)
                         ]];
                     })
                     ->toArray();
    }

    public static function getConfidenceDistribution($companyId = null): array
    {
        $query = static::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return [
            'very_high' => $query->clone()->where('confidence_score', '>=', 90)->count(),
            'high' => $query->clone()->whereBetween('confidence_score', [70, 89])->count(),
            'medium' => $query->clone()->whereBetween('confidence_score', [50, 69])->count(),
            'low' => $query->clone()->whereBetween('confidence_score', [30, 49])->count(),
            'very_low' => $query->clone()->where('confidence_score', '<', 30)->count(),
        ];
    }

    public static function getMatchTypeDistribution($companyId = null): array
    {
        $query = static::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return $query->selectRaw('match_type, COUNT(*) as count')
                     ->groupBy('match_type')
                     ->pluck('count', 'match_type')
                     ->toArray();
    }

    public static function getTopPerformingKeywords(int $limit = 10, $companyId = null): array
    {
        $query = static::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return $query->selectRaw('keyword_id, COUNT(*) as match_count, AVG(confidence_score) as avg_confidence')
                     ->with('keyword')
                     ->groupBy('keyword_id')
                     ->orderByDesc('match_count')
                     ->limit($limit)
                     ->get()
                     ->map(function($item) {
                         return [
                             'keyword' => $item->keyword?->keyword ?? 'Unknown',
                             'match_count' => $item->match_count,
                             'avg_confidence' => round($item->avg_confidence, 2)
                         ];
                     })
                     ->toArray();
    }

    public static function getMatchingTrend(int $days = 30, $companyId = null): array
    {
        $query = static::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return $query->selectRaw('DATE(matched_at) as date, COUNT(*) as count, AVG(confidence_score) as avg_confidence')
                     ->where('matched_at', '>=', now()->subDays($days))
                     ->groupBy('date')
                     ->orderBy('date')
                     ->get()
                     ->map(function($item) {
                         return [
                             'date' => $item->date,
                             'count' => $item->count,
                             'avg_confidence' => round($item->avg_confidence, 2)
                         ];
                     })
                     ->toArray();
    }
}