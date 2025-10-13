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
        Schema::create('document_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('document_collection_id')
                  ->constrained('document_collections')
                  ->onDelete('cascade')
                  ->comment('Parent collection');
            $table->foreignId('bank_statement_id')
                  ->constrained('bank_statements')
                  ->onDelete('cascade')
                  ->comment('PDF/Bank statement reference');
            
            // Organization
            $table->integer('sort_order')->default(0)
                  ->comment('Display order in collection');
            $table->text('notes')->nullable()
                  ->comment('User notes about this document');
            $table->json('tags')->nullable()
                  ->comment('Custom tags for organization');
            
            // Knowledge Processing Status
            $table->enum('knowledge_status', [
                'pending',
                'processing', 
                'ready',
                'failed'
            ])->default('pending')
              ->comment('Status of AI knowledge preparation');
            
            $table->text('knowledge_error')->nullable()
                  ->comment('Error message if knowledge processing failed');
            $table->timestamp('processed_at')->nullable()
                  ->comment('When knowledge was last processed');
            
            // Metadata (cached from bank statement)
            $table->date('statement_period_from')->nullable();
            $table->date('statement_period_to')->nullable();
            $table->integer('transaction_count')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            
            $table->timestamps();
            
            // Indexes & Constraints
            $table->unique(['document_collection_id', 'bank_statement_id'], 'unique_collection_statement');
            $table->index(['document_collection_id', 'sort_order']);
            $table->index('knowledge_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_items');
    }
};