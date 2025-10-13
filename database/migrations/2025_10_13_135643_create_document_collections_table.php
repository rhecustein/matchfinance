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
        Schema::create('document_collections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')
                  ->comment('User who created this collection');
            
            // Basic Info
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6')
                  ->comment('Hex color for UI display');
            $table->string('icon', 50)->default('folder')
                  ->comment('Icon identifier for UI');
            
            // Statistics
            $table->integer('document_count')->default(0)
                  ->comment('Number of PDFs in collection');
            $table->integer('total_transactions')->default(0)
                  ->comment('Total transactions across all PDFs');
            $table->decimal('total_debit', 15, 2)->default(0)
                  ->comment('Sum of all debit transactions');
            $table->decimal('total_credit', 15, 2)->default(0)
                  ->comment('Sum of all credit transactions');
            
            // Settings
            $table->boolean('auto_add_new')->default(false)
                  ->comment('Auto-add new uploads matching criteria');
            $table->json('filter_settings')->nullable()
                  ->comment('Auto-filter criteria: banks, date ranges, etc');
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('chat_count')->default(0)
                  ->comment('Number of chat sessions using this collection');
            $table->timestamp('last_used_at')->nullable()
                  ->comment('Last time collection was used in chat');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'user_id']);
            $table->index(['company_id', 'is_active']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_collections');
    }
};