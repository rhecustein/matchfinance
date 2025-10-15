<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('sub_category_id')->constrained()->onDelete('cascade');
            
            // =========================================================
            // KEYWORD CONFIGURATION
            // =========================================================
            $table->string('keyword', 255)->comment('The keyword/pattern to match');
            $table->boolean('is_regex')->default(false)->comment('Whether keyword is regex pattern');
            $table->boolean('case_sensitive')->default(false)->comment('Whether matching is case sensitive');
            
            // Pattern Matching Enhancement
            $table->enum('match_type', ['exact', 'contains', 'starts_with', 'ends_with', 'regex'])
                  ->default('contains')
                  ->comment('Type of matching algorithm to use');
            
            $table->text('pattern_description')->nullable()->comment('Human-readable description of pattern');
            
            // =========================================================
            // ✅ AMOUNT & TIME CONSTRAINTS (for advanced matching)
            // =========================================================
            $table->decimal('min_amount', 15, 2)
                  ->nullable()
                  ->comment('Minimum transaction amount for this keyword to apply');
            
            $table->decimal('max_amount', 15, 2)
                  ->nullable()
                  ->comment('Maximum transaction amount for this keyword to apply');
            
            $table->json('valid_days')
                  ->nullable()
                  ->comment('Days of week when this keyword is valid [1-7, where 1=Monday]');
            
            $table->json('valid_months')
                  ->nullable()
                  ->comment('Months when this keyword is valid [1-12]');
            
            // =========================================================
            // PRIORITY & STATUS
            // =========================================================
            $table->integer('priority')->default(5)->comment('1-10, higher number = checked first in matching');
            $table->boolean('is_active')->default(true)->comment('Whether keyword is active for matching');
            
            // =========================================================
            // ✅ LEARNING & ANALYTICS
            // =========================================================
            $table->integer('match_count')->default(0)->comment('How many times this keyword has matched');
            $table->timestamp('last_matched_at')->nullable()->comment('Last time this keyword matched a transaction');
            
            $table->boolean('auto_learned')
                  ->default(false)
                  ->comment('Was this keyword auto-generated from machine learning');
            
            $table->enum('learning_source', [
                'manual',
                'suggestion',
                'correction',
                'pattern',
                'import'
            ])->default('manual')
               ->comment('How this keyword was created');
            
            // =========================================================
            // ✅ EFFECTIVENESS TRACKING
            // =========================================================
            $table->integer('effectiveness_score')
                  ->default(50)
                  ->comment('Effectiveness score based on accuracy (0-100)');
            
            $table->integer('false_positive_count')
                  ->default(0)
                  ->comment('Number of times this keyword matched incorrectly');
            
            $table->integer('true_positive_count')
                  ->default(0)
                  ->comment('Number of times this keyword matched correctly');
            
            $table->timestamp('last_reviewed_at')
                  ->nullable()
                  ->comment('Last time this keyword was reviewed by user');
            
            $table->foreignId('reviewed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('User who last reviewed this keyword');
            
            // =========================================================
            // TIMESTAMPS
            // =========================================================
            $table->timestamps();
            $table->softDeletes();
            
            // =========================================================
            // INDEXES - OPTIMIZED FOR MATCHING PERFORMANCE
            // =========================================================
            
            // 1. PRIMARY MATCHING QUERY (MOST IMPORTANT!) ⭐⭐⭐
            // Query: Get all active keywords ordered by priority for matching
            $table->index(['company_id', 'is_active', 'priority'], 'idx_company_active_priority');
            
            // 2. SUB-CATEGORY LOOKUP
            // Query: Get keywords by sub_category
            $table->index(['sub_category_id', 'is_active', 'priority'], 'idx_subcat_active_priority');
            
            // 3. PERFORMANCE ANALYTICS
            // Query: Find most/least used keywords
            $table->index(['match_count', 'last_matched_at'], 'idx_usage_analytics');
            
            // 4. KEYWORD SEARCH & FILTERING
            // Query: Search keywords by text (admin UI)
            $table->index(['is_active', 'match_type'], 'idx_active_type');
            
            // ✅ 5. LEARNING & EFFECTIVENESS (NEW!)
            $table->index(['auto_learned', 'learning_source'], 'idx_learning');
            $table->index(['effectiveness_score'], 'idx_effectiveness');
            $table->index(['last_reviewed_at'], 'idx_reviewed');
            
            // 6. SOFT DELETE AWARE
            // Query: Active keywords excluding deleted
            $table->index(['company_id', 'deleted_at'], 'idx_company_deleted');
            
            // 7. FULL TEXT SEARCH (Optional - for searching keyword text)
            // Uncomment if you need to search within keyword text
            // $table->fullText(['keyword', 'pattern_description'], 'idx_keyword_fulltext');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};