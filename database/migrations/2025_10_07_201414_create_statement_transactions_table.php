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
            
            // Account Matching System - NEW FEATURE
            $table->foreignId('account_id')
                  ->nullable()
                  ->constrained('accounts')
                  ->nullOnDelete()
                  ->comment('Matched accounting account (Chart of Accounts)');
                  
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
            // Note: Full details in transaction_categories table, this is for quick access
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
            
            // Performance Indexes - Core Queries
            $table->index('bank_statement_id', 'idx_statement_id');
            $table->index('transaction_date', 'idx_transaction_date');
            $table->index('transaction_type', 'idx_transaction_type');
            
            // Account Matching Indexes
            $table->index(['account_id', 'transaction_date'], 'idx_account_date');
            $table->index(['account_id', 'is_manual_account'], 'idx_account_manual');
            $table->index('matched_account_keyword_id', 'idx_matched_account_keyword');
            
            // Category Matching Indexes
            $table->index(['sub_category_id', 'category_id', 'type_id'], 'idx_categories');
            $table->index('matched_keyword_id', 'idx_matched_keyword');
            $table->index('is_manual_category', 'idx_manual_category');
            
            // Verification & Quality Indexes
            $table->index(['is_verified', 'confidence_score'], 'idx_verification');
            $table->index(['verified_by', 'verified_at'], 'idx_verifier_time');
            
            // Soft Delete Aware Indexes (Critical for Performance)
            $table->index(['id', 'deleted_at'], 'idx_id_deleted');
            $table->index(['transaction_date', 'deleted_at'], 'idx_date_deleted');
            $table->index(['is_verified', 'deleted_at'], 'idx_verified_deleted');
            $table->index(['matched_keyword_id', 'deleted_at'], 'idx_matched_deleted');
            $table->index(['confidence_score', 'deleted_at'], 'idx_confidence_deleted');
            $table->index(['type_id', 'deleted_at'], 'idx_type_deleted');
            $table->index(['category_id', 'deleted_at'], 'idx_category_deleted');
            $table->index(['sub_category_id', 'deleted_at'], 'idx_sub_category_deleted');
            $table->index(['account_id', 'deleted_at'], 'idx_account_deleted');
            
            // Special Purpose Indexes
            $table->index(['is_transfer', 'deleted_at'], 'idx_transfer_deleted');
            $table->index(['is_recurring', 'recurring_pattern'], 'idx_recurring');
            
            // Composite Indexes for Complex Queries
            $table->index(['bank_statement_id', 'transaction_date', 'transaction_type'], 'idx_statement_date_type');
            $table->index(['account_id', 'category_id', 'transaction_date'], 'idx_account_cat_date');
            $table->index(['is_verified', 'confidence_score', 'transaction_date'], 'idx_verified_conf_date');
            
            // Full Text Search Index for Description
            $table->fullText('description', 'idx_description_fulltext');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_transactions');
    }
};