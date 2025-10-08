<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            // 1. Perbesar branch_code
            $table->string('branch_code', 256)->nullable()->change();
            
            // 2. Tambah manual_count jika belum ada
            if (!Schema::hasColumn('bank_statements', 'manual_count')) {
                $table->integer('manual_count')->default(0)->after('unmatched_count');
            }
            
            // 3. Tambah match_percentage jika belum ada
            if (!Schema::hasColumn('bank_statements', 'match_percentage')) {
                $table->decimal('match_percentage', 5, 2)->default(0)->after('manual_count');
            }

            // 4. Tambah notes jika belum ada
            if (!Schema::hasColumn('bank_statements', 'notes')) {
                $table->text('notes')->nullable()->after('match_percentage');
            }
        });

        // Update statement_transactions
        Schema::table('statement_transactions', function (Blueprint $table) {
            $table->string('branch_code', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            $table->string('branch_code', 20)->nullable()->change();
            
            $table->dropColumn(['manual_count', 'match_percentage', 'notes']);
        });

        Schema::table('statement_transactions', function (Blueprint $table) {
            $table->string('branch_code', 20)->nullable()->change();
        });
    }
};