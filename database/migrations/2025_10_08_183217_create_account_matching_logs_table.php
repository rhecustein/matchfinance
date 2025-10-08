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
        Schema::create('account_matching_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statement_transaction_id')->constrained('statement_transactions')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('account_keyword_id')->nullable()->constrained('account_keywords')->nullOnDelete();
            $table->integer('confidence_score');
            $table->boolean('is_matched')->default(false);
            $table->text('match_reason')->nullable();
            $table->json('match_details')->nullable();
            $table->timestamps();

            $table->index(['statement_transaction_id', 'is_matched']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_matching_logs');
    }
};
