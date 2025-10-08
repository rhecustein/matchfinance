<?php
// database/migrations/2025_10_07_201413_create_bank_statements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // File info
            $table->string('file_path');
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size')->nullable();
            
            // OCR Processing
            $table->enum('ocr_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->json('ocr_response')->nullable();
            $table->text('ocr_error')->nullable();
            
            // Statement metadata dari OCR
            $table->date('period_from')->nullable(); // PeriodFrom dari OCR
            $table->date('period_to')->nullable(); // PeriodTo dari OCR
            $table->string('account_number', 50)->nullable(); // AccountNo dari OCR
            $table->string('currency', 10)->default('IDR'); // Currency dari OCR
            $table->string('branch_code', 20)->nullable(); // Branch dari OCR
            
            // Financial summary dari OCR
            $table->decimal('opening_balance', 15, 2)->nullable();
            $table->decimal('closing_balance', 15, 2)->nullable();
            $table->integer('total_credit_count')->default(0); // CreditNo dari OCR
            $table->integer('total_debit_count')->default(0); // DebitNo dari OCR
            $table->decimal('total_credit_amount', 15, 2)->default(0); // TotalAmountCredited
            $table->decimal('total_debit_amount', 15, 2)->default(0); // TotalAmountDebited
            
            // Matching statistics
            $table->integer('matched_count')->default(0);
            $table->integer('unmatched_count')->default(0);
            $table->integer('verified_count')->default(0);
            
            // Timestamps
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['bank_id', 'ocr_status']);
            $table->index('user_id');
            $table->index('uploaded_at');
            $table->index(['period_from', 'period_to']);
            $table->index('account_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};