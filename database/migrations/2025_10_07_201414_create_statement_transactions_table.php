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
            $table->foreignId('bank_statement_id')->constrained()->onDelete('cascade');
            $table->date('transaction_date');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance', 15, 2)->nullable();
            $table->enum('transaction_type', ['debit', 'credit']);
            
            // Matching results (denormalized for performance)
            $table->foreignId('matched_keyword_id')->nullable()->constrained('keywords')->nullOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('type_id')->nullable()->constrained()->nullOnDelete();
            
            $table->integer('confidence_score')->default(0)->comment('0-100');
            
            // Verification
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('bank_statement_id');
            $table->index('transaction_date');
            $table->index(['sub_category_id', 'category_id', 'type_id']);
            $table->index(['is_verified', 'confidence_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_transactions');
    }
};