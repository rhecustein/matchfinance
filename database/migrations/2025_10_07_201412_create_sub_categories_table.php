<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            
            // Sub-Category Information
            $table->string('name', 100)->comment('Sub-category display name');
            $table->text('description')->nullable()->comment('Sub-category description/purpose');
            
            // Matching Priority
            $table->integer('priority')
                  ->default(5)
                  ->comment('1-10, higher number = checked first in matching algorithm');
            
            // Display Order
            $table->integer('sort_order')
                  ->default(0)
                  ->comment('Display order in listings within category');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // =========================================================
            // INDEXES - OPTIMIZED FOR SUB-CATEGORY QUERIES
            // =========================================================
            
            // 1. PRIMARY LOOKUP (MOST IMPORTANT!) ⭐⭐⭐
            // Get sub-categories by category, ordered by priority for matching
            $table->index(['company_id', 'category_id', 'priority'], 'idx_company_cat_priority');
            
            // 2. CATEGORY SUB-CATEGORIES (Ordered for Display) ⭐⭐⭐
            // Get sub-categories for dropdown/listing
            $table->index(['category_id', 'sort_order'], 'idx_category_order');
            
            // 3. HIGH PRIORITY LOOKUP ⭐⭐
            // Find highest priority sub-categories for matching optimization
            $table->index(['company_id', 'priority'], 'idx_company_priority');
            
            // 4. SOFT DELETE AWARE ⭐⭐
            $table->index(['company_id', 'deleted_at'], 'idx_company_deleted');
            $table->index(['category_id', 'deleted_at'], 'idx_category_deleted');
            
            // 5. NAME SEARCH ⭐
            // Search sub-categories by name within company
            $table->index(['company_id', 'name'], 'idx_company_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_categories');
    }
};