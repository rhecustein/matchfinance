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
        Schema::table('bank_statements', function (Blueprint $table) {
            // Account Matching Status Fields (after matching_completed_at)
            $table->string('account_matching_status')
                  ->nullable()
                  ->after('matching_completed_at')
                  ->comment('pending, processing, completed, failed, skipped');
            
            $table->text('account_matching_notes')
                  ->nullable()
                  ->after('account_matching_status')
                  ->comment('Notes or reason for account matching status');
            
            $table->timestamp('account_matching_started_at')
                  ->nullable()
                  ->after('account_matching_notes')
                  ->comment('When account matching job started');
            
            $table->timestamp('account_matching_completed_at')
                  ->nullable()
                  ->after('account_matching_started_at')
                  ->comment('When account matching job completed');
            
            // Indexes for querying by status
            $table->index(['account_matching_status'], 'idx_account_matching_status');
            $table->index(['company_id', 'account_matching_status'], 'idx_company_account_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            $table->dropIndex('idx_account_matching_status');
            $table->dropIndex('idx_company_account_status');
            
            $table->dropColumn([
                'account_matching_status',
                'account_matching_notes',
                'account_matching_started_at',
                'account_matching_completed_at',
            ]);
        });
    }
};