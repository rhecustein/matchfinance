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
            
            // Foreign Keys
            $table->foreignId('bank_statement_id')->constrained()->onDelete('cascade');
            
            // Transaction Details
            $table->date('transaction_date');
            $table->time('transaction_time')->nullable()->comment('Time of transaction if available');
            $table->date('value_date')->nullable()->comment('Effective date of transaction');
            $table->string('branch_code', 100)->nullable()->comment('Bank branch code');
            $table->text('description')->comment('Transaction description from bank');
            $table->string('reference_no', 100)->nullable()->comment('Bank reference number');
            
            // Amounts
            $table->decimal('debit_amount', 15, 2)->default(0)->comment('Outgoing amount');
            $table->decimal('credit_amount', 15, 2)->default(0)->comment('Incoming amount');
            $table->decimal('balance', 15, 2)->nullable()->comment('Account balance after transaction');
            $table->enum('transaction_type', ['debit', 'credit'])->comment('Type of transaction');
            $table->decimal('amount', 15, 2)->nullable()->comment('Transaction amount (absolute value)');

            //acount
            //account_id
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('matched_account_keyword_id')->nullable()->constrained('account_keywords')->nullOnDelete();
            $table->integer('account_confidence_score')->nullable()->comment('Confidence score for account matching 0-100');
            $table->boolean('is_manual_account')->default(false)->comment('Whether account was manually assigned');
        
            
            // Matching Results (Denormalized for Performance)
            $table->foreignId('matched_keyword_id')->nullable()->constrained('keywords')->nullOnDelete()->comment('Keyword that matched this transaction');
            $table->foreignId('sub_category_id')->nullable()->constrained('sub_categories')->nullOnDelete()->comment('Sub-category assigned');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete()->comment('Category assigned');
            $table->foreignId('type_id')->nullable()->constrained('types')->nullOnDelete()->comment('Type assigned');
            
            // Matching Metadata
            $table->integer('confidence_score')->default(0)->comment('Matching confidence score (0-100)');
            $table->boolean('is_manual_category')->default(false)->comment('Whether category was manually assigned');
            
            // Verification Status
            $table->boolean('is_verified')->default(false)->comment('Whether transaction is verified by user');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who verified');
            $table->timestamp('verified_at')->nullable()->comment('Verification timestamp');
            
            // Additional Info
            $table->text('notes')->nullable()->comment('User notes or remarks');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for Performance
            $table->index('bank_statement_id', 'idx_statement_id');
            $table->index('transaction_date', 'idx_transaction_date');
            $table->index('transaction_type', 'idx_transaction_type');
            $table->index(['sub_category_id', 'category_id', 'type_id'], 'idx_categories');
            $table->index(['is_verified', 'confidence_score'], 'idx_verification');
            $table->index('matched_keyword_id', 'idx_matched_keyword');
            $table->index('is_manual_category', 'idx_manual_category');
            $table->index(['id', 'deleted_at'], 'idx_id_deleted');
            $table->index(['transaction_date', 'deleted_at'], 'idx_date_deleted');
            $table->index(['is_verified', 'deleted_at'], 'idx_verified_deleted');
            $table->index(['matched_keyword_id', 'deleted_at'], 'idx_matched_deleted');
            $table->index(['confidence_score', 'deleted_at'], 'idx_confidence_deleted');
            $table->index(['type_id', 'deleted_at'], 'idx_type_deleted');
            $table->index(['category_id', 'deleted_at'], 'idx_category_deleted');
            $table->index(['sub_category_id', 'deleted_at'], 'idx_sub_category_deleted');
            $table->fullText('description', 'idx_description_fulltext');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_transactions');
    }
};