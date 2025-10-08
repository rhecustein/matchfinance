<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            // Add matching statistics columns if they don't exist
            if (!Schema::hasColumn('bank_statements', 'matched_transactions_count')) {
                $table->integer('matched_transactions_count')->default(0)->after('total_debit_amount');
            }
            
            if (!Schema::hasColumn('bank_statements', 'unmatched_transactions_count')) {
                $table->integer('unmatched_transactions_count')->default(0)->after('matched_transactions_count');
            }
            
            if (!Schema::hasColumn('bank_statements', 'manual_transactions_count')) {
                $table->integer('manual_transactions_count')->default(0)->after('unmatched_transactions_count');
            }
            
            if (!Schema::hasColumn('bank_statements', 'match_percentage')) {
                $table->decimal('match_percentage', 5, 2)->default(0)->after('manual_transactions_count')->comment('Matching percentage 0-100');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            $table->dropColumn([
                'matched_transactions_count',
                'unmatched_transactions_count',
                'manual_transactions_count',
                'match_percentage',
            ]);
        });
    }
};