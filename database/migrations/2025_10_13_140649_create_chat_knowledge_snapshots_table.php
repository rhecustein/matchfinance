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
        Schema::create('chat_knowledge_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')
                  ->constrained('chat_sessions')
                  ->onDelete('cascade')
                  ->comment('Chat session this snapshot belongs to');
            
            // Knowledge Summary Data (JSON for flexibility)
            $table->json('transactions_summary')
                  ->comment('Summary of all transactions in context');
            
            $table->json('category_breakdown')
                  ->comment('Grouped by categories with totals');
            
            $table->json('type_breakdown')
                  ->comment('Grouped by types with totals');
            
            $table->json('account_breakdown')->nullable()
                  ->comment('Grouped by accounts with totals');
            
            $table->json('date_range')
                  ->comment('Date range of transactions: {from, to}');
            
            $table->json('bank_info')
                  ->comment('Bank(s) information');
            
            // Aggregate Statistics
            $table->integer('total_transactions')->default(0)
                  ->comment('Total number of transactions');
            
            $table->decimal('total_debit', 15, 2)->default(0)
                  ->comment('Sum of all debit amounts');
            
            $table->decimal('total_credit', 15, 2)->default(0)
                  ->comment('Sum of all credit amounts');
            
            $table->decimal('net_amount', 15, 2)->default(0)
                  ->comment('Net amount (credit - debit)');
            
            $table->decimal('avg_transaction', 15, 2)->default(0)
                  ->comment('Average transaction amount');
            
            $table->decimal('max_transaction', 15, 2)->default(0)
                  ->comment('Largest transaction amount');
            
            $table->decimal('min_transaction', 15, 2)->default(0)
                  ->comment('Smallest transaction amount');
            
            // Context Metadata
            $table->integer('matched_transactions')->default(0)
                  ->comment('Number of matched transactions');
            
            $table->integer('unmatched_transactions')->default(0)
                  ->comment('Number of unmatched transactions');
            
            $table->integer('verified_transactions')->default(0)
                  ->comment('Number of verified transactions');
            
            $table->timestamp('snapshot_created_at')
                  ->useCurrent()
                  ->comment('When this snapshot was created');
            
            // Indexes
            $table->index('chat_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_knowledge_snapshots');
    }
};