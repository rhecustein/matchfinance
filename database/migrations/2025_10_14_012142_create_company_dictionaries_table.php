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
        Schema::create('company_dictionaries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            $table->string('dictionary_type', 50)->comment('IGNORE_WORDS, PRIORITY_WORDS, etc');
            $table->string('word', 255);
            $table->integer('weight')->default(0)->comment('Weight/importance of this word');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->unique(['company_id', 'dictionary_type', 'word'], 'unique_company_dict_word');
            $table->index(['company_id', 'dictionary_type', 'is_active'], 'idx_company_type_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_dictionaries');
    }
};
