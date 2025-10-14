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
        Schema::create('keyword_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keyword_group_id')
                  ->constrained('keyword_groups')
                  ->onDelete('cascade');
            $table->foreignId('keyword_id')
                  ->constrained('keywords')
                  ->onDelete('cascade');
            
            // Member Configuration
            $table->boolean('is_required')
                  ->default(false)
                  ->comment('For AND logic, marks if this specific keyword is required');
            $table->boolean('is_negative')
                  ->default(false)
                  ->comment('If true, keyword must NOT be present');
            $table->integer('position')
                  ->default(0)
                  ->comment('Position in the group for ordered matching');
            
            // Weight for scoring
            $table->integer('weight')
                  ->default(1)
                  ->comment('Weight of this keyword in confidence calculation');
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['keyword_group_id', 'keyword_id'], 'unique_group_keyword');
            $table->index(['keyword_group_id', 'position'], 'idx_group_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_group_members');
    }
};
