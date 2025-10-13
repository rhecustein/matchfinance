<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('type_id')->constrained()->onDelete('cascade');
            $table->string('slug')->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6'); // Tailwind blue-500
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('type_id');
            $table->unique(['company_id', 'slug']);

            Schema::create('category_product', function (Blueprint $table) {
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->foreignId('category_id')->constrained()->onDelete('cascade');
                
                $table->primary(['product_id', 'category_id']);
            });
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};