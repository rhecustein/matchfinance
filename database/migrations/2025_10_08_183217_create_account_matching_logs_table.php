<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_matching_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Foreign Keys - Core Relations
            $table->foreignId('statement_transaction_id')
                  ->constrained('statement_transactions')
                  ->onDelete('cascade')
                  ->comment('Transaction that was matched');
                  
            $table->foreignId('account_id')
                  ->constrained('accounts')
                  ->onDelete('cascade')
                  ->comment('Account that was evaluated/matched');
                  
            $table->foreignId('account_keyword_id')
                  ->nullable()
                  ->constrained('account_keywords')
                  ->nullOnDelete()
                  ->comment('Specific keyword that triggered the match');
            
            // Matching Details
            $table->string('matched_text', 500)
                  ->nullable()
                  ->comment('The actual text portion that matched from transaction description');
                  
            $table->integer('confidence_score')
                  ->comment('Account matching confidence score (0-100)');
                  
            $table->enum('match_type', ['exact', 'contains', 'starts_with', 'ends_with', 'regex', 'manual'])
                  ->nullable()
                  ->comment('Type of match that occurred');
                  
            $table->text('match_pattern')
                  ->nullable()
                  ->comment('The pattern/regex that was used for matching');
            
            // Match Result
            $table->boolean('is_matched')
                  ->default(false)
                  ->comment('Whether this account was successfully matched');
                  
            $table->boolean('is_selected')
                  ->default(false)
                  ->comment('Whether this match was selected as primary account');
                  
            $table->integer('priority_score')
                  ->default(0)
                  ->comment('Combined priority score (keyword priority + account priority)');
            
            // Match Context
            $table->text('match_reason')
                  ->nullable()
                  ->comment('Human-readable explanation of match result');
                  
            $table->json('match_details')
                  ->nullable()
                  ->comment('Detailed matching information (regex groups, position, etc)');
                  
            $table->json('account_snapshot')
                  ->nullable()
                  ->comment('Snapshot of account config at match time');
            
            // Matching Process Info
            $table->string('matching_engine', 50)
                  ->default('auto')
                  ->comment('Which matching engine: auto, manual, ai, rule-based');
                  
            $table->integer('processing_time_ms')
                  ->nullable()
                  ->comment('Time taken to process this match in milliseconds');
            
            // Audit Trail
            $table->foreignId('matched_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('User who triggered match (if manual)');
                  
            $table->ipAddress('matched_from_ip')
                  ->nullable()
                  ->comment('IP address if manually triggered');
            
            // Timestamps
            $table->timestamps();
            
            // =========================================================
            // INDEXES - OPTIMIZED FOR ACCOUNT MATCHING ANALYSIS
            // =========================================================
            
            // 1. PRIMARY TRANSACTION LOOKUP (MOST IMPORTANT!) ⭐⭐⭐
            // Get all account matches for a transaction
            $table->index(['statement_transaction_id', 'is_matched', 'confidence_score'], 'idx_trans_match_lookup');
            
            // 2. SELECTED ACCOUNT MATCH (CRITICAL) ⭐⭐⭐
            // Find the selected account for a transaction
            $table->index(['statement_transaction_id', 'is_selected'], 'idx_trans_selected');
            
            // 3. BEST MATCH SELECTION (CRITICAL) ⭐⭐⭐
            // For selecting best account match when multiple options exist
            $table->index(['statement_transaction_id', 'priority_score', 'confidence_score'], 'idx_trans_best_match');
            
            // 4. ACCOUNT PERFORMANCE ANALYSIS ⭐⭐
            // Analyze account matching effectiveness
            $table->index(['account_id', 'is_matched', 'confidence_score'], 'idx_account_performance');
            
            // 5. KEYWORD EFFECTIVENESS ⭐⭐
            // Track which account keywords work best
            $table->index(['account_keyword_id', 'is_matched', 'confidence_score'], 'idx_keyword_effectiveness');
            
            // 6. CONFIDENCE FILTERING ⭐⭐
            // Find low/high confidence matches for review
            $table->index(['company_id', 'confidence_score', 'is_matched'], 'idx_company_confidence');
            
            // 7. MATCHING ENGINE ANALYTICS ⭐
            // Compare engine performance
            $table->index(['matching_engine', 'confidence_score'], 'idx_engine_performance');
            
            // 8. TEMPORAL ANALYSIS ⭐⭐
            // Matching trends over time
            $table->index(['company_id', 'created_at'], 'idx_company_timeline');
            
            // 9. MATCHED TRANSACTIONS ONLY ⭐⭐
            // Filter successful matches
            $table->index(['company_id', 'is_matched', 'created_at'], 'idx_company_matched_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_matching_logs');
    }
};