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
            
            // OCR Processing Status
            $table->enum('ocr_status', [
                'pending', 
                'processing', 
                'completed', 
                'failed'
            ])->default('pending')->comment('Current OCR processing status');
            
            $table->json('ocr_response')->nullable()->comment('Full OCR API response for reference');
            $table->text('ocr_error')->nullable()->comment('Error message if OCR failed');
            $table->string('ocr_job_id', 100)->nullable()->comment('Queue job ID for tracking');
            
            // Statement Metadata (extracted from OCR response)
            $table->string('bank_name', 100)->nullable()->comment('Bank name extracted from OCR');
            $table->date('period_from')->nullable()->comment('Statement period start date');
            $table->date('period_to')->nullable()->comment('Statement period end date');
            $table->string('account_number', 50)->nullable()->comment('Bank account number');
            $table->string('account_holder_name', 255)->nullable()->comment('Account holder name if available');
            $table->string('currency', 10)->default('IDR')->comment('Currency code (ISO 4217)');
            $table->string('branch_code', 50)->nullable()->comment('Bank branch code if available');
            $table->string('branch_name', 255)->nullable()->comment('Bank branch name if available');
            
            // Financial Summary (calculated from OCR response)
            $table->decimal('opening_balance', 20, 2)->nullable()->comment('Opening balance of the period');
            $table->decimal('closing_balance', 20, 2)->nullable()->comment('Closing balance of the period');
            
            $table->integer('total_credit_count')->default(0)->comment('Total number of credit transactions');
            $table->integer('total_debit_count')->default(0)->comment('Total number of debit transactions');
            
            $table->decimal('total_credit_amount', 20, 2)->default(0)->comment('Total amount of credits (incoming)');
            $table->decimal('total_debit_amount', 20, 2)->default(0)->comment('Total amount of debits (outgoing)');
            
            // Transaction Statistics (managed by system)
            $table->integer('total_transactions')->default(0)->comment('Total count of parsed transactions');
            $table->integer('processed_transactions')->default(0)->comment('Number of processed transactions');
            $table->integer('matched_transactions')->default(0)->comment('Number of auto-matched transactions');
            $table->integer('unmatched_transactions')->default(0)->comment('Number of unmatched transactions');
            $table->integer('verified_transactions')->default(0)->comment('Number of user-verified transactions');
            
            // Additional Metadata
            $table->text('notes')->nullable()->comment('User notes or remarks about this statement');
            $table->boolean('is_reconciled')->default(false)->comment('Whether statement is reconciled');
            $table->timestamp('reconciled_at')->nullable()->comment('When the statement was reconciled');
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who reconciled');
            
            // Timestamps
            $table->timestamp('uploaded_at')->useCurrent()->comment('When file was uploaded');
            $table->timestamp('ocr_started_at')->nullable()->comment('When OCR processing started');
            $table->timestamp('ocr_completed_at')->nullable()->comment('When OCR processing completed');
            $table->timestamps();
            $table->softDeletes();
            
            // Performance Indexes
            $table->index(['bank_id', 'ocr_status'], 'idx_bank_ocr_status');
            $table->index(['user_id', 'uploaded_at'], 'idx_user_uploaded');
            $table->index(['period_from', 'period_to'], 'idx_period_range');
            $table->index('account_number', 'idx_account_number');
            $table->index('ocr_status', 'idx_ocr_status');
            $table->index('ocr_job_id', 'idx_ocr_job');
            $table->index(['is_reconciled', 'deleted_at'], 'idx_reconciled_deleted');
            $table->index(['user_id', 'period_from', 'deleted_at'], 'idx_user_period_deleted');
            
            // Composite indexes for common queries
            $table->index(['bank_id', 'account_number', 'period_from'], 'idx_bank_account_period');
            $table->index(['user_id', 'bank_id', 'ocr_status'], 'idx_user_bank_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};