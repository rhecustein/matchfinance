<?php
// database/migrations/2025_10_07_201413_create_keywords_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('sub_category_id')->constrained()->onDelete('cascade');
            $table->string('keyword', 255);
            $table->boolean('is_regex')->default(false);
            $table->boolean('case_sensitive')->default(false);
            
            // Pattern matching enhancement
            $table->enum('match_type', ['exact', 'contains', 'starts_with', 'ends_with', 'regex'])->default('contains');
            $table->text('pattern_description')->nullable(); // Deskripsi pattern
            
            $table->integer('priority')->default(5)->comment('1-10, higher checked first');
            $table->boolean('is_active')->default(true);
            
            // Learning from matching
            $table->integer('match_count')->default(0); // Berapa kali matched
            $table->timestamp('last_matched_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['sub_category_id', 'is_active']);
            $table->index('priority');
            $table->index('match_count'); // Untuk analisis
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};