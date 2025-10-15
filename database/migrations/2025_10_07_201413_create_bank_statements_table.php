<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Foreign Keys - Relations
            $table->foreignId('bank_id')
                  ->constrained('banks')
                  ->onDelete('cascade')
                  ->comment('Reference to banks table');
                  
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('User who uploaded the statement');
            
            // File Information
            $table->string('file_path', 512)->comment('Storage path of the PDF file');
            $table->string('file_hash', 64)->unique()->comment('SHA256 hash for duplicate detection');
            $table->string('original_filename', 255)->comment('Original uploaded filename');
            $table->unsignedBigInteger('file_size')->nullable()->comment('File size in bytes');
            $table->string('mime_type', 100)->default('application/pdf')->comment('File MIME type');
            
            // =========================================================
            // OCR PROCESSING STATUS
            // =========================================================
            $table->enum('ocr_status', [
                'pending', 
                'processing', 
                'completed', 
                'failed'
            ])->default('pending')->comment('Current OCR processing status');
            
            $table->json('ocr_response')->nullable()->comment('Full OCR API response for reference');
            $table->text('ocr_error')->nullable()->comment('Error message if OCR failed');
            $table->string('ocr_job_id', 100)->nullable()->comment('Queue job ID for tracking');
            $table->timestamp('ocr_started_at')->nullable()->comment('When OCR processing started');
            $table->timestamp('ocr_completed_at')->nullable()->comment('When OCR processing completed');
            
            // =========================================================
            // STATEMENT METADATA (extracted from OCR response)
            // =========================================================
            $table->string('bank_name', 100)->nullable()->comment('Bank name extracted from OCR');
            $table->date('period_from')->nullable()->comment('Statement period start date');
            $table->date('period_to')->nullable()->comment('Statement period end date');
            $table->string('account_number', 50)->nullable()->comment('Bank account number');
            $table->string('account_holder_name', 255)->nullable()->comment('Account holder name if available');
            $table->string('currency', 10)->default('IDR')->comment('Currency code (ISO 4217)');
            $table->string('branch_code', 50)->nullable()->comment('Bank branch code if available');
            $table->string('branch_name', 255)->nullable()->comment('Bank branch name if available');
            
            // =========================================================
            // FINANCIAL SUMMARY (calculated from OCR response)
            // =========================================================
            $table->decimal('opening_balance', 20, 2)->nullable()->comment('Opening balance of the period');
            $table->decimal('closing_balance', 20, 2)->nullable()->comment('Closing balance of the period');
            
            $table->integer('total_credit_count')->default(0)->comment('Total number of credit transactions');
            $table->integer('total_debit_count')->default(0)->comment('Total number of debit transactions');
            
            $table->decimal('total_credit_amount', 20, 2)->default(0)->comment('Total amount of credits (incoming)');
            $table->decimal('total_debit_amount', 20, 2)->default(0)->comment('Total amount of debits (outgoing)');
            
            // =========================================================
            // TRANSACTION STATISTICS (managed by system)
            // =========================================================
            $table->integer('total_transactions')->default(0)->comment('Total count of parsed transactions');
            $table->integer('processed_transactions')->default(0)->comment('Number of processed transactions');
            $table->integer('matched_transactions')->default(0)->comment('Number of auto-matched transactions');
            $table->integer('unmatched_transactions')->default(0)->comment('Number of unmatched transactions');
            $table->integer('verified_transactions')->default(0)->comment('Number of user-verified transactions');
            
            // ✅ NEW: Low Confidence Transactions Count (CRITICAL!)
            $table->integer('low_confidence_transactions')
                  ->default(0)
                  ->comment('Number of transactions with confidence score < 70');
            
            // =========================================================
            // ✅ TRANSACTION MATCHING STATUS
            // =========================================================
            $table->string('matching_status', 50)
                  ->nullable()
                  ->comment('Status: pending, processing, completed, failed, skipped');
            
            $table->text('matching_notes')
                  ->nullable()
                  ->comment('Notes or reason for matching status');
            
            $table->text('matching_error')
                  ->nullable()
                  ->comment('Error message if matching failed');
            
            $table->timestamp('matching_started_at')
                  ->nullable()
                  ->comment('When transaction matching job started');
            
            $table->timestamp('matching_completed_at')
                  ->nullable()
                  ->comment('When transaction matching job completed');
            
            // =========================================================
            // ✅ ACCOUNT MATCHING STATUS
            // =========================================================
            $table->string('account_matching_status', 50)
                  ->nullable()
                  ->comment('Status: pending, processing, completed, failed, skipped');
            
            $table->text('account_matching_notes')
                  ->nullable()
                  ->comment('Notes or reason for account matching status');
            
            $table->text('account_matching_error')
                  ->nullable()
                  ->comment('Error message if account matching failed');
            
            $table->timestamp('account_matching_started_at')
                  ->nullable()
                  ->comment('When account matching job started');
            
            $table->timestamp('account_matching_completed_at')
                  ->nullable()
                  ->comment('When account matching job completed');
            
            // =========================================================
            // RECONCILIATION
            // =========================================================
            $table->text('notes')->nullable()->comment('User notes or remarks about this statement');
            $table->boolean('is_reconciled')->default(false)->comment('Whether statement is reconciled');
            $table->timestamp('reconciled_at')->nullable()->comment('When the statement was reconciled');
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who reconciled');
            
            // =========================================================
            // TIMESTAMPS
            // =========================================================
            $table->timestamp('uploaded_at')->useCurrent()->comment('When file was uploaded');
            $table->timestamps();
            $table->softDeletes();
            
            // =========================================================
            // INDEXES - OPTIMIZED FOR QUERY PERFORMANCE
            // =========================================================
            
            // 1. MULTI-TENANT PRIMARY QUERIES (MOST IMPORTANT!) ⭐⭐⭐
            $table->index(['company_id', 'uploaded_at'], 'idx_company_uploaded');
            $table->index(['company_id', 'ocr_status'], 'idx_company_ocr_status');
            
            // 2. OCR PROCESSING & MONITORING
            $table->index(['ocr_status', 'ocr_started_at'], 'idx_ocr_processing');
            $table->index(['ocr_job_id'], 'idx_ocr_job');
            
            // 3. USER ACTIVITY & FILTERING
            $table->index(['user_id', 'bank_id', 'uploaded_at'], 'idx_user_bank_uploaded');
            
            // 4. PERIOD & DATE RANGE QUERIES
            $table->index(['period_from', 'period_to'], 'idx_period_range');
            $table->index(['company_id', 'period_from', 'period_to'], 'idx_company_period');
            
            // 5. ACCOUNT LOOKUP
            $table->index(['bank_id', 'account_number'], 'idx_bank_account');
            
            // 6. RECONCILIATION STATUS
            $table->index(['company_id', 'is_reconciled'], 'idx_company_reconciled');
            
            // 7. STATISTICS & REPORTING
            $table->index(['matched_transactions', 'total_transactions'], 'idx_matching_stats');
            
            // ✅ 8. MATCHING STATUS INDEXES (NEW!)
            $table->index(['matching_status'], 'idx_matching_status');
            $table->index(['company_id', 'matching_status'], 'idx_company_matching');
            
            // ✅ 9. ACCOUNT MATCHING STATUS INDEXES (NEW!)
            $table->index(['account_matching_status'], 'idx_account_matching_status');
            $table->index(['company_id', 'account_matching_status'], 'idx_company_account_status');
            
            // 10. SOFT DELETE AWARE (CRITICAL for Multi-tenant)
            $table->index(['company_id', 'deleted_at'], 'idx_company_deleted');
            $table->index(['user_id', 'deleted_at'], 'idx_user_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};