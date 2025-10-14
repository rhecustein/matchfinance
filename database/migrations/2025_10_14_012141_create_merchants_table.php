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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            
            // Merchant Information
            $table->string('code', 100)->unique()->comment('Unique merchant code');
            $table->string('name', 255)->comment('Official merchant name');
            $table->string('display_name', 255)->nullable()->comment('Display name for UI');
            $table->enum('type', [
                'retail',
                'restaurant',
                'online',
                'service',
                'transport',
                'utility',
                'entertainment',
                'healthcare',
                'other'
            ])->default('other');
            
            // Patterns to identify this merchant
            $table->json('keywords')->comment('Array of keywords to identify this merchant');
            $table->json('regex_patterns')->nullable()->comment('Regex patterns for complex matching');
            
            // Default categorization
            $table->foreignId('default_category_id')->nullable()
                  ->constrained('categories')->nullOnDelete();
            $table->foreignId('default_sub_category_id')->nullable()
                  ->constrained('sub_categories')->nullOnDelete();
            
            // Additional Info
            $table->string('website')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('metadata')->nullable()->comment('Additional merchant data');
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false)
                  ->comment('Verified merchant data');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'is_active'], 'idx_company_active');
            $table->index(['type', 'is_active'], 'idx_type_active');
            $table->fullText(['name', 'display_name'], 'idx_merchant_name_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
