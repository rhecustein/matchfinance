<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('type_id')->constrained()->onDelete('cascade');
            
            // Category Information
            $table->string('slug')->comment('URL-friendly identifier');
            $table->string('name', 100)->comment('Category display name');
            $table->text('description')->nullable()->comment('Category description/purpose');
            $table->string('color', 7)->default('#3B82F6')->comment('Color code for UI (Tailwind format)');
            $table->integer('sort_order')->default(0)->comment('Display order in listings');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // =========================================================
            // INDEXES - OPTIMIZED FOR CATEGORY QUERIES
            // =========================================================
            
            // 1. PRIMARY LOOKUP (MOST IMPORTANT!) ⭐⭐⭐
            // Get categories by type for a company
            $table->index(['company_id', 'type_id', 'sort_order'], 'idx_company_type_order');
            
            // 2. SLUG LOOKUP ⭐⭐⭐
            // Find category by slug within company
            $table->index(['company_id', 'slug'], 'idx_company_slug');
            
            // 3. TYPE CATEGORIES ⭐⭐
            // Get all categories for a specific type
            $table->index(['type_id', 'sort_order'], 'idx_type_order');
            
            // 4. SOFT DELETE AWARE ⭐⭐
            $table->index(['company_id', 'deleted_at'], 'idx_company_deleted');
            
            // 5. UNIQUE CONSTRAINT - Slug per company ⭐⭐⭐
            // CRITICAL FIX: Slug must be unique per company, not globally!
            $table->unique(['company_id', 'slug'], 'unique_company_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};