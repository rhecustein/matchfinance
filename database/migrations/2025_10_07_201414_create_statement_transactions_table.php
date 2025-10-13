<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statement_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Foreign Keys - Core Relations
            $table->foreignId('bank_statement_id')
                  ->constrained('bank_statements')
                  ->onDelete('cascade')
                  ->comment('Reference to parent bank statement');
            
            // Transaction Details - From OCR
            $table->date('transaction_date')->comment('Date when transaction occurred');
            $table->time('transaction_time')->nullable()->comment('Time of transaction if available from OCR');
            $table->date('value_date')->nullable()->comment('Effective/value date of transaction');
            $table->string('branch_code', 50)->nullable()->comment('Bank branch code where transaction occurred');
            $table->text('description')->comment('Transaction description/narrative from bank statement');
            $table->string('reference_no', 100)->nullable()->comment('Bank reference/transaction number');
            
            // Amount Fields - Financial Data
            $table->decimal('debit_amount', 15, 2)->default(0)->comment('Outgoing/debit amount (negative cash flow)');
            $table->decimal('credit_amount', 15, 2)->default(0)->comment('Incoming/credit amount (positive cash flow)');
            $table->decimal('balance', 15, 2)->nullable()->comment('Running balance after this transaction');
            $table->enum('transaction_type', ['debit', 'credit'])->comment('Transaction direction: debit (out) or credit (in)');
            $table->decimal('amount', 15, 2)->comment('Absolute transaction amount (always positive)');
            
            // Account Matching System
            $table->foreignId('account_id')
                  ->nullable()
                  ->constrained('accounts')
                  ->nullOnDelete()
                  ->comment('Matched account for this transaction');
            
            $table->foreignId('matched_account_keyword_id')
                  ->nullable()
                  ->constrained('account_keywords')
                  ->nullOnDelete()
                  ->comment('Account keyword that triggered the match');
                  
            $table->integer('account_confidence_score')
                  ->nullable()
                  ->comment('Confidence score for account matching (0-100)');
                  
            $table->boolean('is_manual_account')
                  ->default(false)
                  ->comment('True if account was manually assigned by user');
            
            // Category Matching System - Denormalized for Performance
            $table->foreignId('matched_keyword_id')
                  ->nullable()
                  ->constrained('keywords')
                  ->nullOnDelete()
                  ->comment('Primary keyword that matched this transaction');
                  
            $table->foreignId('sub_category_id')
                  ->nullable()
                  ->constrained('sub_categories')
                  ->nullOnDelete()
                  ->comment('Primary sub-category assigned');
                  
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained('categories')
                  ->nullOnDelete()
                  ->comment('Primary category assigned (denormalized from sub_category)');
                  
            $table->foreignId('type_id')
                  ->nullable()
                  ->constrained('types')
                  ->nullOnDelete()
                  ->comment('Primary type assigned (denormalized from category)');
            
            // Matching Metadata
            $table->integer('confidence_score')
                  ->default(0)
                  ->comment('Primary category matching confidence score (0-100)');
                  
            $table->boolean('is_manual_category')
                  ->default(false)
                  ->comment('True if category was manually assigned by user');
                  
            $table->text('matching_reason')
                  ->nullable()
                  ->comment('Explanation of why this category was assigned');
            
            // Verification & Quality Control
            $table->boolean('is_verified')
                  ->default(false)
                  ->comment('Whether transaction has been reviewed and verified by user');
                  
            $table->foreignId('verified_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('User who verified this transaction');
                  
            $table->timestamp('verified_at')
                  ->nullable()
                  ->comment('When the transaction was verified');
            
            // Additional Information
            $table->text('notes')
                  ->nullable()
                  ->comment('User notes, remarks, or additional context');
                  
            $table->json('metadata')
                  ->nullable()
                  ->comment('Additional structured data from OCR or user input');
            
            // Flags for Special Cases
            $table->boolean('is_transfer')
                  ->default(false)
                  ->comment('True if this is an internal transfer between accounts');
                  
            $table->boolean('is_recurring')
                  ->default(false)
                  ->comment('True if this is identified as a recurring transaction');
                  
            $table->string('recurring_pattern', 50)
                  ->nullable()
                  ->comment('Pattern of recurrence: monthly, weekly, etc.');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // =========================================================
            // INDEXES - OPTIMIZED & CONSOLIDATED
            // =========================================================
            
            // 1. CORE QUERY INDEXES (Most Frequently Used)
            $table->index(['company_id', 'transaction_date'], 'idx_company_date');
            $table->index(['bank_statement_id', 'transaction_date'], 'idx_statement_date');
            
            // 2. MATCHING SYSTEM INDEXES
            $table->index(['bank_statement_id', 'matched_keyword_id'], 'idx_statement_matching');
            $table->index(['matched_keyword_id', 'confidence_score'], 'idx_keyword_confidence');
            
            // 3. ACCOUNT MATCHING INDEXES
            $table->index(['account_id', 'transaction_date'], 'idx_account_date');
            $table->index(['account_id', 'is_manual_account'], 'idx_account_manual');
            
            // 4. CATEGORY HIERARCHY INDEXES
            $table->index(['type_id', 'category_id', 'sub_category_id'], 'idx_category_hierarchy');
            $table->index(['sub_category_id', 'transaction_date'], 'idx_subcat_date');
            
            // 5. VERIFICATION INDEXES
            $table->index(['is_verified', 'verified_at'], 'idx_verified');
            $table->index(['is_verified', 'confidence_score'], 'idx_verified_confidence');
            
            // 6. TRANSACTION TYPE & AMOUNT INDEXES
            $table->index(['transaction_type', 'transaction_date'], 'idx_type_date');
            $table->index(['transaction_type', 'amount'], 'idx_type_amount');
            
            // 7. SPECIAL FLAGS INDEXES
            $table->index(['is_transfer', 'transaction_date'], 'idx_transfer_date');
            $table->index(['is_recurring', 'recurring_pattern'], 'idx_recurring');
            
            // 8. SOFT DELETE AWARE COMPOSITE INDEXES (CRITICAL!)
            // These are for queries that filter by deleted_at frequently
            $table->index(['company_id', 'deleted_at'], 'idx_company_deleted');
            $table->index(['bank_statement_id', 'deleted_at'], 'idx_statement_deleted');
            $table->index(['is_verified', 'deleted_at'], 'idx_verified_deleted');
            
            // 9. FULL TEXT SEARCH for Description
            $table->fullText('description', 'idx_description_fulltext');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_transactions');
    }
};