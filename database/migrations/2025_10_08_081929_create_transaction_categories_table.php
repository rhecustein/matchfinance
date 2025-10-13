<?php
// database/migrations/2025_10_07_201415_create_transaction_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('statement_transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('sub_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('type_id')->constrained()->onDelete('cascade');
            $table->foreignId('matched_keyword_id')->nullable()->constrained('keywords')->nullOnDelete();
            
            $table->integer('confidence_score')->default(0)->comment('0-100');
            $table->boolean('is_primary')->default(true)->comment('Primary category assignment');
            $table->boolean('is_manual')->default(false)->comment('Manually assigned by user');
            $table->text('reason')->nullable()->comment('Reason for matching');
            $table->json('match_metadata')->nullable()->comment('Additional matching information');
            
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['statement_transaction_id', 'is_primary']);
            $table->index(['confidence_score', 'is_primary']);
            $table->index(['category_id', 'type_id']);
            $table->index('assigned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_categories');
    }
};