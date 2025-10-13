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
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')
                  ->comment('User who owns this chat session');
            
            // Session Info
            $table->string('title')->nullable()
                  ->comment('Auto-generated from first message or user-defined');
            
            // Chat Mode & Context
            $table->enum('mode', ['single', 'collection'])
                  ->comment('Chat with single PDF or collection');
            
            $table->foreignId('bank_statement_id')
                  ->nullable()
                  ->constrained('bank_statements')
                  ->nullOnDelete()
                  ->comment('If mode=single, the specific PDF');
            
            $table->foreignId('document_collection_id')
                  ->nullable()
                  ->constrained('document_collections')
                  ->nullOnDelete()
                  ->comment('If mode=collection, the collection');
            
            // Context Filters (optional refinement)
            $table->date('date_from')->nullable()
                  ->comment('Filter transactions by date range');
            $table->date('date_to')->nullable();
            
            $table->json('type_ids')->nullable()
                  ->comment('Filter by transaction types');
            $table->json('category_ids')->nullable()
                  ->comment('Filter by categories');
            $table->json('account_ids')->nullable()
                  ->comment('Filter by accounts');
            
            // AI Settings
            $table->string('ai_model', 50)->default('gpt-4o-mini')
                  ->comment('OpenAI model used');
            $table->decimal('temperature', 3, 2)->default(0.7)
                  ->comment('AI temperature setting');
            $table->integer('max_tokens')->default(2000)
                  ->comment('Max tokens for AI response');
            
            // Statistics
            $table->integer('message_count')->default(0)
                  ->comment('Total messages in this session');
            $table->integer('total_tokens')->default(0)
                  ->comment('Total tokens consumed');
            $table->decimal('total_cost', 10, 6)->default(0)
                  ->comment('Total cost in USD');
            
            // Metadata
            $table->json('context_summary')->nullable()
                  ->comment('Summary of loaded data for this session');
            $table->integer('context_transaction_count')->default(0)
                  ->comment('Number of transactions in context');
            
            // Session Status
            $table->timestamp('last_activity_at')->nullable()
                  ->comment('Last message timestamp');
            $table->boolean('is_archived')->default(false)
                  ->comment('Archived sessions hidden from main list');
            $table->boolean('is_pinned')->default(false)
                  ->comment('Pinned sessions appear at top');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'user_id', 'is_archived']);
            $table->index(['mode', 'bank_statement_id']);
            $table->index(['mode', 'document_collection_id']);
            $table->index('last_activity_at');
            $table->index(['is_pinned', 'last_activity_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};