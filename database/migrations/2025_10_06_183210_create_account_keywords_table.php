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
        Schema::create('account_keywords', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('keyword')->comment('Keyword untuk matching, bisa regex');
            $table->boolean('is_regex')->default(false);
            $table->boolean('case_sensitive')->default(false);
            $table->enum('match_type', ['exact', 'contains', 'starts_with', 'ends_with', 'regex'])->default('contains');
            $table->text('pattern_description')->nullable();
            $table->integer('priority')->default(5)->comment('Priority dalam account ini, 1-10');
            $table->boolean('is_active')->default(true);
            $table->integer('match_count')->default(0);
            $table->timestamp('last_matched_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_keywords');
    }
};
