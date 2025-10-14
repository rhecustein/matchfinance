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
        Schema::create('keyword_suggestions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('sub_category_id')->constrained()->onDelete('cascade');
            
            // Suggestion Details
            $table->string('keyword', 255);
            $table->foreignId('source_transaction_id')->nullable()
                  ->constrained('statement_transactions')->nullOnDelete()
                  ->comment('Transaction that triggered this suggestion');
            
            // Confidence & Statistics
            $table->integer('confidence')->default(50)->comment('Confidence score 0-100');
            $table->integer('occurrence_count')->default(1)
                  ->comment('How many times this pattern appeared');
            
            // Review Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'auto_approved'])
                  ->default('pending');
            $table->foreignId('reviewed_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Metadata
            $table->json('sample_transactions')->nullable()
                  ->comment('Sample transaction IDs where this keyword appeared');
            $table->json('statistics')->nullable()
                  ->comment('Stats like total amount, date range, etc');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'status'], 'idx_company_status');
            $table->index(['sub_category_id', 'status'], 'idx_subcat_status');
            $table->index(['confidence', 'occurrence_count'], 'idx_confidence_occurrence');
            $table->index(['status', 'created_at'], 'idx_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_suggestions');
    }
};
