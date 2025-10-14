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
        Schema::create('keyword_patterns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade')
                  ->comment('NULL = global pattern, NOT NULL = company specific');
            
            // Pattern Information
            $table->string('code', 50)->comment('Unique code: EWALLET, PAYMENT_METHOD, etc');
            $table->string('name', 100)->comment('Display name');
            $table->text('description')->nullable();
            
            // Pattern Configuration
            $table->string('pattern', 255)->comment('The actual pattern/keyword to match');
            $table->enum('pattern_type', [
                'exact',           // Exact match
                'contains',        // Contains the pattern
                'regex',          // Regular expression
                'prefix',         // Starts with
                'suffix'          // Ends with
            ])->default('contains');
            
            $table->boolean('case_sensitive')->default(false);
            $table->boolean('extract_variant')->default(false)
                  ->comment('Extract variations like "GOPAY COINS" from "GOPAY"');
            
            // Categorization Hint
            $table->string('category_hint', 100)->nullable()
                  ->comment('Suggested category name for auto-categorization');
            $table->foreignId('default_category_id')->nullable()
                  ->constrained('categories')->nullOnDelete()
                  ->comment('Default category to assign if pattern matches');
            $table->foreignId('default_sub_category_id')->nullable()
                  ->constrained('sub_categories')->nullOnDelete();
            
            // Priority & Status
            $table->integer('priority')->default(5)->comment('1-10, higher = checked first');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false)
                  ->comment('System patterns cannot be deleted by users');
            
            // Analytics
            $table->integer('match_count')->default(0);
            $table->timestamp('last_matched_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'is_active', 'priority'], 'idx_company_active_priority');
            $table->index(['code', 'is_active'], 'idx_code_active');
            $table->index(['pattern_type', 'is_active'], 'idx_type_active');
            $table->unique(['company_id', 'code', 'pattern'], 'unique_company_code_pattern');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_suggestions');
    }
};
