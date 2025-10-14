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
        Schema::create('pattern_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pattern_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('keyword_pattern_id')->constrained()->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['pattern_group_id', 'keyword_pattern_id'], 'unique_group_pattern');
            $table->index(['pattern_group_id', 'sort_order'], 'idx_group_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pattern_group_items');
    }
};
