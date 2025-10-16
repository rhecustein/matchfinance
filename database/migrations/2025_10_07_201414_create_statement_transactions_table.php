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
            //bank type string
            $table->string('bank_type', 50)->comment('Type of bank (e.g., Chase, Bank of America)')->nullable();
            //AccountNo stri
            $table->string('account_number', 100)->comment('Bank account number')->nullable();
            $table->date('transaction_date')->comment('Date when transaction occurred');
            $table->time('transaction_time')->nullable()->comment('Time of transaction if available from OCR');
            $table->date('value_date')->nullable()->comment('Effective/value date of transaction');
            $table->string('branch_code', 50)->nullable()->comment('Bank branch code where transaction occurred');
            $table->text('description')->comment('Transaction description/narrative from bank statement');
            $table->string('reference_no', 100)->nullable()->comment('Bank reference/transaction number');
            
            // ✅ NEW: Extracted Information (for better matching)
            $table->json('extracted_keywords')
                  ->nullable()
                  ->comment('Keywords extracted from description for search');
            
            $table->text('normalized_description')
                  ->nullable()
                  ->comment('Cleaned/normalized version of description');
            
            // Status Flags
            $table->boolean('is_approved')->default(false)->comment('Whether transaction is approved by user');
            $table->foreignId('approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('User who approved the transaction');
            $table->timestamp('approved_at')
                  ->nullable()
                  ->comment('Timestamp when transaction was approved');
            
            $table->boolean('is_rejected')->default(false)->comment('Whether transaction is rejected by user');
            $table->foreignId('rejected_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('User who rejected the transaction');
            $table->timestamp('rejected_at')
                  ->nullable()
                  ->comment('Timestamp when transaction was rejected');
            
            $table->boolean('is_pending')->default(false)->comment('Whether transaction is pending');
            $table->foreignId('pending_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('User who marked the transaction as pending');
            $table->timestamp('pending_at')
                  ->nullable()
                  ->comment('Timestamp when transaction was marked as pending');
            
            // Amount Fields - Financial Data
            $table->decimal('debit_amount', 15, 2)->default(0)->comment('Outgoing/debit amount (negative cash flow)');
            $table->decimal('credit_amount', 15, 2)->default(0)->comment('Incoming/credit amount (positive cash flow)');
            $table->decimal('balance', 15, 2)->nullable()->comment('Running balance after this transaction');
            $table->enum('transaction_type', ['debit', 'credit'])->comment('Transaction direction: debit (out) or credit (in)');
            $table->decimal('amount', 15, 2)->comment('Absolute transaction amount (always positive)');
            
            // =========================================================
            // ACCOUNT MATCHING SYSTEM
            // =========================================================
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
            
            // =========================================================
            // CATEGORY MATCHING SYSTEM - Denormalized for Performance
            // =========================================================
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
            
            // ✅ NEW: Enhanced Matching Metadata
            $table->string('match_method', 50)
                  ->nullable()
                  ->comment('Method used for matching: exact_match, contains, regex, similarity, word_boundary, partial_word');
            
            $table->json('match_metadata')
                  ->nullable()
                  ->comment('Detailed matching information including account suggestions, matched text, scores');
            
            $table->json('alternative_categories')
                  ->nullable()
                  ->comment('Top 5 category suggestions with confidence scores for user review');
            
            // ✅ NEW: Performance Tracking
            $table->integer('matching_duration_ms')
                  ->nullable()
                  ->comment('Time taken to match in milliseconds');
            
            $table->integer('matching_attempts')
                  ->default(0)
                  ->comment('Number of matching attempts');
            
            // =========================================================
            // VERIFICATION & QUALITY CONTROL
            // =========================================================
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
            
            // ✅ NEW: Learning Feedback
            $table->enum('feedback_status', [
                'pending',
                'correct',
                'incorrect',
                'partial'
            ])->default('pending')
               ->comment('User feedback on categorization accuracy for ML learning');
            
            $table->text('feedback_notes')
                  ->nullable()
                  ->comment('User notes about categorization');
            
            $table->foreignId('feedback_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('User who provided feedback');
            
            $table->timestamp('feedback_at')
                  ->nullable()
                  ->comment('When feedback was provided');
            
            // =========================================================
            // ADDITIONAL INFORMATION
            // =========================================================
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
            $table->index(['match_method'], 'idx_match_method');
            
            // 3. ACCOUNT MATCHING INDEXES
            $table->index(['account_id', 'transaction_date'], 'idx_account_date');
            $table->index(['account_id', 'is_manual_account'], 'idx_account_manual');
            
            // 4. CATEGORY HIERARCHY INDEXES
            $table->index(['type_id', 'category_id', 'sub_category_id'], 'idx_category_hierarchy');
            $table->index(['sub_category_id', 'transaction_date'], 'idx_subcat_date');
            
            // 5. VERIFICATION & FEEDBACK INDEXES
            $table->index(['is_verified', 'verified_at'], 'idx_verified');
            $table->index(['is_verified', 'confidence_score'], 'idx_verified_confidence');
            $table->index(['feedback_status'], 'idx_feedback_status');
            $table->index(['feedback_at'], 'idx_feedback_at');
            
            // 6. TRANSACTION TYPE & AMOUNT INDEXES
            $table->index(['transaction_type', 'transaction_date'], 'idx_type_date');
            $table->index(['transaction_type', 'amount'], 'idx_type_amount');
            
            // 7. SPECIAL FLAGS INDEXES
            $table->index(['is_transfer', 'transaction_date'], 'idx_transfer_date');
            $table->index(['is_recurring', 'recurring_pattern'], 'idx_recurring');
            
            // 8. SOFT DELETE AWARE COMPOSITE INDEXES (CRITICAL!)
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