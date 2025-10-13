<?php
// database/migrations/2025_10_07_201413_create_keywords_table.php

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
            
            // Keyword Configuration
            $table->string('keyword', 255)->comment('The keyword/pattern to match');
            $table->boolean('is_regex')->default(false)->comment('Whether keyword is regex pattern');
            $table->boolean('case_sensitive')->default(false)->comment('Whether matching is case sensitive');
            
            // Pattern Matching Enhancement
            $table->enum('match_type', ['exact', 'contains', 'starts_with', 'ends_with', 'regex'])
                  ->default('contains')
                  ->comment('Type of matching algorithm to use');
            $table->text('pattern_description')->nullable()->comment('Human-readable description of pattern');
            
            // Priority & Status
            $table->integer('priority')->default(5)->comment('1-10, higher number = checked first in matching');
            $table->boolean('is_active')->default(true)->comment('Whether keyword is active for matching');
            
            // Learning & Analytics
            $table->integer('match_count')->default(0)->comment('How many times this keyword has matched');
            $table->timestamp('last_matched_at')->nullable()->comment('Last time this keyword matched a transaction');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // =========================================================
            // INDEXES - OPTIMIZED FOR MATCHING PERFORMANCE
            // =========================================================
            
            // 1. PRIMARY MATCHING QUERY (MOST IMPORTANT!)
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
            
            // 5. SOFT DELETE AWARE
            // Query: Active keywords excluding deleted
            $table->index(['company_id', 'deleted_at'], 'idx_company_deleted');
            
            // 6. FULL TEXT SEARCH (Optional - for searching keyword text)
            // Uncomment if you need to search within keyword text
            // $table->fullText(['keyword', 'pattern_description'], 'idx_keyword_fulltext');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};