<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Account Information
            $table->string('name')->comment('Account name/title');
            $table->string('code', 50)->nullable()->comment('Account code/number (e.g., 1001, 5001)');
            $table->text('description')->nullable()->comment('Account description/purpose');
            
            // Account Classification
            $table->string('account_type', 50)
                  ->nullable()
                  ->comment('Asset, Liability, Equity, Revenue, Expense');
            $table->string('color', 20)
                  ->default('#3B82F6')
                  ->comment('Color code for UI display');
            
            // Matching Configuration
            $table->unsignedTinyInteger('priority')
                  ->default(5)
                  ->comment('1-10, higher number = matched first in algorithm');
            $table->boolean('is_active')
                  ->default(true)
                  ->comment('Whether account is active for matching');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // =========================================================
            // INDEXES - OPTIMIZED FOR ACCOUNT MATCHING
            // =========================================================
            
            // 1. PRIMARY MATCHING QUERY (MOST IMPORTANT!) ⭐⭐⭐
            // Get all active accounts ordered by priority for matching
            $table->index(['company_id', 'is_active', 'priority'], 'idx_company_active_priority');
            
            // 2. ACCOUNT CODE LOOKUP ⭐⭐
            // Find account by company + code (unique per company)
            $table->index(['company_id', 'code'], 'idx_company_code');
            
            // 3. ACCOUNT TYPE FILTERING ⭐⭐
            // Filter accounts by type (e.g., Revenue, Expense)
            $table->index(['company_id', 'account_type', 'is_active'], 'idx_company_type_active');
            
            // 4. ACCOUNT SEARCH (Admin UI) ⭐
            // Search/filter in admin interface
            $table->index(['company_id', 'name'], 'idx_company_name');
            
            // 5. SOFT DELETE AWARE ⭐⭐
            // Query active accounts excluding deleted
            $table->index(['company_id', 'deleted_at'], 'idx_company_deleted');
            
            // 6. UNIQUE CONSTRAINT - Account code per company
            // Note: Code must be unique within company, but can be null
            $table->unique(['company_id', 'code'], 'unique_company_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};