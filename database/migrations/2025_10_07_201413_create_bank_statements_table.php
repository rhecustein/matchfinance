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
            
            // Relations
            $table->foreignId('bank_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // File Information
            $table->string('file_path');
            $table->string('file_hash', 64)->unique()->comment('SHA256 hash for duplicate detection');
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 50)->default('application/pdf');
            
            // OCR Processing Status
            $table->enum('ocr_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->json('ocr_response')->nullable()->comment('Full OCR response');
            $table->text('ocr_error')->nullable();
            $table->string('ocr_job_id', 100)->nullable()->comment('Queue job ID for tracking');
            
            // Statement Metadata (dari OCR response)
            $table->string('bank_name', 50)->nullable()->comment('Bank name from OCR');
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('currency', 10)->default('IDR');
            $table->string('branch_code', 256)->nullable();
            
            // Financial Summary (dari OCR response)
            $table->decimal('opening_balance', 20, 2)->nullable();
            $table->decimal('closing_balance', 20, 2)->nullable();
            $table->integer('total_credit_count')->default(0)->comment('CreditNo from OCR');
            $table->integer('total_debit_count')->default(0)->comment('DebitNo from OCR');
            $table->decimal('total_credit_amount', 20, 2)->default(0)->comment('TotalAmountCredited');
            $table->decimal('total_debit_amount', 20, 2)->default(0)->comment('TotalAmountDebited');
            
            // Transaction Statistics
            $table->integer('total_transactions')->default(0)->comment('Count of TableData entries');
            $table->integer('processed_transactions')->default(0);
            $table->integer('matched_transactions')->default(0);
            $table->integer('unmatched_transactions')->default(0);
            $table->integer('verified_transactions')->default(0);
            
            // Timestamps
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamp('ocr_started_at')->nullable();
            $table->timestamp('ocr_completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for Performance
            $table->index(['bank_id', 'ocr_status']);
            $table->index(['user_id', 'uploaded_at']);
            $table->index(['period_from', 'period_to']);
            $table->index('account_number');
            $table->index('ocr_status');
            $table->index('ocr_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};