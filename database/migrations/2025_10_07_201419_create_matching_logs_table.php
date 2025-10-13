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
            
            // Timestamps (untuk tracking updates jika ada)
            $table->timestamps();
            
            // Performance Indexes
            $table->index('statement_transaction_id', 'idx_transaction_id');
            $table->index('keyword_id', 'idx_keyword_id');
            $table->index('confidence_score', 'idx_confidence');
            $table->index('is_selected', 'idx_is_selected');
            $table->index('matched_at', 'idx_matched_at');
            $table->index('matching_engine', 'idx_matching_engine');
            
            // Composite Indexes for Analytics
            $table->index(['keyword_id', 'confidence_score', 'matched_at'], 'idx_keyword_conf_time');
            $table->index(['statement_transaction_id', 'is_selected'], 'idx_trans_selected');
            $table->index(['is_selected', 'confidence_score'], 'idx_selected_confidence');
            
            // For finding best matches
            $table->index(['statement_transaction_id', 'confidence_score', 'priority_score'], 'idx_trans_scores');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matching_logs');
    }
};