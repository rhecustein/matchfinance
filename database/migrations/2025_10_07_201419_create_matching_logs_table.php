<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matching_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Foreign Keys - Core Relations
            $table->foreignId('statement_transaction_id')
                  ->constrained('statement_transactions')
                  ->onDelete('cascade')
                  ->comment('Transaction that was matched');
                  
            $table->foreignId('keyword_id')
                  ->constrained('keywords')
                  ->onDelete('cascade')
                  ->comment('Keyword that triggered the match');
            
            // Matching Details
            $table->string('matched_text', 500)
                  ->comment('The actual text portion that matched from transaction description');
                  
            $table->integer('confidence_score')
                  ->comment('Matching confidence score (0-100)');
                  
            $table->enum('match_type', ['exact', 'contains', 'starts_with', 'ends_with', 'regex'])
                  ->comment('Type of match that occurred');
                  
            $table->text('match_pattern')
                  ->nullable()
                  ->comment('The pattern/regex that was used for matching');
            
            // Match Context
            $table->boolean('is_selected')
                  ->default(false)
                  ->comment('Whether this match was selected as primary category');
                  
            $table->integer('priority_score')
                  ->default(0)
                  ->comment('Combined priority score (keyword priority + sub_category priority)');
                  
            $table->text('match_reason')
                  ->nullable()
                  ->comment('Human-readable explanation of why this matched');
            
            // Additional Metadata
            $table->json('match_metadata')
                  ->nullable()
                  ->comment('Additional matching information (regex groups, position, etc)');
                  
            $table->json('keyword_snapshot')
                  ->nullable()
                  ->comment('Snapshot of keyword config at match time');
            
            // Matching Process Info
            $table->string('matching_engine', 50)
                  ->default('auto')
                  ->comment('Which matching engine was used: auto, manual, ai, etc');
                  
            $table->integer('processing_time_ms')
                  ->nullable()
                  ->comment('Time taken to process this match in milliseconds');
            
            // Audit Trail
            $table->timestamp('matched_at')
                  ->useCurrent()
                  ->comment('When this match was created');
                  
            $table->ipAddress('matched_from_ip')
                  ->nullable()
                  ->comment('IP address if manually triggered');
            
            // Timestamps
            $table->timestamps();
            
            // =========================================================
            // INDEXES - OPTIMIZED FOR MATCHING ANALYSIS
            // =========================================================
            
            // 1. PRIMARY LOOKUP QUERIES (MOST IMPORTANT!) ⭐⭐⭐
            // Get all matches for a transaction
            $table->index(['statement_transaction_id', 'is_selected', 'confidence_score'], 'idx_trans_match_lookup');
            
            // 2. SELECTED MATCH LOOKUP (CRITICAL) ⭐⭐⭐
            // Find the selected match for a transaction
            $table->index(['statement_transaction_id', 'is_selected'], 'idx_trans_selected');
            
            // 3. KEYWORD PERFORMANCE ANALYSIS ⭐⭐
            // Analyze keyword effectiveness over time
            $table->index(['keyword_id', 'confidence_score', 'matched_at'], 'idx_keyword_performance');
            
            // 4. CONFIDENCE FILTERING ⭐⭐
            // Find low/high confidence matches
            $table->index(['company_id', 'confidence_score', 'is_selected'], 'idx_company_confidence');
            
            // 5. MATCHING ENGINE ANALYTICS ⭐
            // Track which engine performs better
            $table->index(['matching_engine', 'confidence_score'], 'idx_engine_performance');
            
            // 6. TEMPORAL ANALYSIS ⭐⭐
            // Matching trends over time
            $table->index(['company_id', 'matched_at'], 'idx_company_timeline');
            
            // 7. BEST MATCH SELECTION ⭐⭐⭐
            // For selecting best match when multiple options exist
            $table->index(['statement_transaction_id', 'priority_score', 'confidence_score'], 'idx_trans_best_match');
            
            // 8. MULTI-TENANT SOFT DELETE AWARE ⭐⭐
            $table->index(['company_id', 'created_at'], 'idx_company_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matching_logs');
    }
};