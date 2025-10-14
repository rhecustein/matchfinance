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
         Schema::create('keyword_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('sub_category_id')->constrained()->onDelete('cascade');
            
            // Group Information
            $table->string('name', 100)->comment('Group name like "E-Wallet Payments"');
            $table->text('description')->nullable();
            
            // Matching Logic
            $table->enum('logic_type', ['AND', 'OR', 'COMPLEX'])
                  ->default('OR')
                  ->comment('AND: all keywords must match, OR: any keyword matches');
            
            // Priority & Status
            $table->integer('priority')->default(5)->comment('1-10, higher = checked first');
            $table->boolean('is_active')->default(true);
            
            // Statistics
            $table->integer('match_count')->default(0);
            $table->timestamp('last_matched_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'is_active', 'priority'], 'idx_company_active_priority');
            $table->index(['sub_category_id', 'is_active'], 'idx_subcat_active');
            $table->unique(['company_id', 'name'], 'unique_company_group_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_groups');
    }
};
