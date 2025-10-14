<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statement_transactions', function (Blueprint $table) {
            // Enhanced Matching Metadata
            if (!Schema::hasColumn('statement_transactions', 'match_method')) {
                $table->enum('match_method', [
                    'keyword',
                    'pattern',
                    'merchant',
                    'rule',
                    'ml_prediction',
                    'manual',
                    'import'
                ])->nullable()
                   ->after('matching_reason')
                   ->comment('Method used for matching');
            }
            
            if (!Schema::hasColumn('statement_transactions', 'match_metadata')) {
                $table->json('match_metadata')
                      ->nullable()
                      ->after('match_method')
                      ->comment('Detailed matching information');
            }
            
            if (!Schema::hasColumn('statement_transactions', 'alternative_categories')) {
                $table->json('alternative_categories')
                      ->nullable()
                      ->after('match_metadata')
                      ->comment('Other possible categories with scores');
            }
            
            // Pattern & Merchant tracking
            if (!Schema::hasColumn('statement_transactions', 'matched_pattern_id')) {
                $table->foreignId('matched_pattern_id')
                      ->nullable()
                      ->after('matched_keyword_id')
                      ->constrained('keyword_patterns')
                      ->nullOnDelete()
                      ->comment('Dynamic pattern that matched');
            }
            
            if (!Schema::hasColumn('statement_transactions', 'matched_merchant_id')) {
                $table->foreignId('matched_merchant_id')
                      ->nullable()
                      ->after('matched_pattern_id')
                      ->constrained('merchants')
                      ->nullOnDelete()
                      ->comment('Identified merchant');
            }
            
            if (!Schema::hasColumn('statement_transactions', 'matched_rule_id')) {
                $table->foreignId('matched_rule_id')
                      ->nullable()
                      ->after('matched_merchant_id')
                      ->constrained('pattern_rules')
                      ->nullOnDelete()
                      ->comment('Rule that matched');
            }
            
            // Extracted Information
            if (!Schema::hasColumn('statement_transactions', 'extracted_keywords')) {
                $table->json('extracted_keywords')
                      ->nullable()
                      ->after('description')
                      ->comment('Keywords extracted from description');
            }
            
            if (!Schema::hasColumn('statement_transactions', 'normalized_description')) {
                $table->text('normalized_description')
                      ->nullable()
                      ->after('extracted_keywords')
                      ->comment('Cleaned/normalized version of description');
            }
            
            // Learning feedback
            if (!Schema::hasColumn('statement_transactions', 'feedback_status')) {
                $table->enum('feedback_status', [
                    'pending',
                    'correct',
                    'incorrect',
                    'partial'
                ])->nullable()
                   ->after('is_verified')
                   ->comment('User feedback on categorization');
            }
            
            if (!Schema::hasColumn('statement_transactions', 'feedback_notes')) {
                $table->text('feedback_notes')
                      ->nullable()
                      ->after('feedback_status')
                      ->comment('User notes about categorization');
            }
            
            if (!Schema::hasColumn('statement_transactions', 'feedback_by')) {
                $table->foreignId('feedback_by')
                      ->nullable()
                      ->after('feedback_notes')
                      ->constrained('users')
                      ->nullOnDelete()
                      ->comment('User who provided feedback');
            }
            
            if (!Schema::hasColumn('statement_transactions', 'feedback_at')) {
                $table->timestamp('feedback_at')
                      ->nullable()
                      ->after('feedback_by')
                      ->comment('When feedback was provided');
            }
            
            // Performance tracking
            if (!Schema::hasColumn('statement_transactions', 'matching_duration_ms')) {
                $table->integer('matching_duration_ms')
                      ->nullable()
                      ->after('confidence_score')
                      ->comment('Time taken to match in milliseconds');
            }
            
            if (!Schema::hasColumn('statement_transactions', 'matching_attempts')) {
                $table->integer('matching_attempts')
                      ->default(0)
                      ->after('matching_duration_ms')
                      ->comment('Number of matching attempts');
            }
            
            // Add indexes for new fields
            $table->index(['match_method'], 'idx_match_method');
            $table->index(['matched_pattern_id'], 'idx_matched_pattern');
            $table->index(['matched_merchant_id'], 'idx_matched_merchant');
            $table->index(['matched_rule_id'], 'idx_matched_rule');
            $table->index(['feedback_status'], 'idx_feedback_status');
            $table->index(['feedback_at'], 'idx_feedback_at');
        });
    }

    public function down(): void
    {
        Schema::table('statement_transactions', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['match_method']);
            $table->dropIndex(['matched_pattern_id']);
            $table->dropIndex(['matched_merchant_id']);
            $table->dropIndex(['matched_rule_id']);
            $table->dropIndex(['feedback_status']);
            $table->dropIndex(['feedback_at']);
            
            // Drop foreign keys
            $table->dropForeign(['matched_pattern_id']);
            $table->dropForeign(['matched_merchant_id']);
            $table->dropForeign(['matched_rule_id']);
            $table->dropForeign(['feedback_by']);
            
            // Drop columns
            $table->dropColumn([
                'match_method',
                'match_metadata',
                'alternative_categories',
                'matched_pattern_id',
                'matched_merchant_id',
                'matched_rule_id',
                'extracted_keywords',
                'normalized_description',
                'feedback_status',
                'feedback_notes',
                'feedback_by',
                'feedback_at',
                'matching_duration_ms',
                'matching_attempts'
            ]);
        });
    }
};