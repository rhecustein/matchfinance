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
        Schema::create('pattern_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->string('name', 100);
            $table->text('description')->nullable();
            
            // Rule Configuration
            $table->enum('rule_type', [
                'all_match',      // All patterns must match (AND)
                'any_match',      // Any pattern matches (OR)
                'custom'          // Custom logic in JSON
            ])->default('any_match');
            
            $table->json('patterns')->comment('Array of pattern IDs or conditions');
            $table->json('conditions')->nullable()
                  ->comment('Additional conditions like amount range, date, etc');
            
            // Action when rule matches
            $table->foreignId('assign_category_id')->nullable()
                  ->constrained('categories')->nullOnDelete();
            $table->foreignId('assign_sub_category_id')->nullable()
                  ->constrained('sub_categories')->nullOnDelete();
            $table->integer('confidence_boost')->default(0)
                  ->comment('Extra confidence score when this rule matches');
            
            // Priority & Status
            $table->integer('priority')->default(5);
            $table->boolean('is_active')->default(true);
            $table->boolean('stop_on_match')->default(false)
                  ->comment('Stop checking other rules if this matches');
            
            // Analytics
            $table->integer('match_count')->default(0);
            $table->timestamp('last_matched_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'is_active', 'priority'], 'idx_company_active_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pattern_rules');
    }
};
