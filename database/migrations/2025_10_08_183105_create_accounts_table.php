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
      Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Kode unik account, misal: ACC-001');
            $table->string('name')->comment('Nama account, misal: Apotek Kimia Farma');
            $table->text('description')->nullable();
            $table->string('account_type', 50)->nullable()->comment('Tipe akun: vendor, customer, partner, etc');
            $table->string('color', 20)->nullable()->default('#3B82F6')->comment('Warna untuk UI');
            $table->integer('priority')->default(5)->comment('Priority 1-10, higher = checked first');
            $table->boolean('is_active')->default(true);
            $table->integer('match_count')->default(0)->comment('Jumlah transaksi yang match');
            $table->timestamp('last_matched_at')->nullable();
            $table->json('metadata')->nullable()->comment('Data tambahan seperti contact info, address, etc');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'priority']);
            $table->index('account_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
