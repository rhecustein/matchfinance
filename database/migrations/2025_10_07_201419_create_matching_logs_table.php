<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matching_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statement_transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('keyword_id')->constrained()->onDelete('cascade');
            $table->string('matched_text');
            $table->integer('confidence_score')->comment('0-100');
            $table->json('match_metadata')->nullable()->comment('Additional matching info');
            $table->timestamp('matched_at')->useCurrent();
            
            $table->index('statement_transaction_id');
            $table->index('keyword_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matching_logs');
    }
};