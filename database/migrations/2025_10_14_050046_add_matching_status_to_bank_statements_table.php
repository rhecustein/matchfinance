<?php
// database/migrations/xxxx_add_matching_status_to_bank_statements.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            $table->string('matching_status')
                  ->nullable()
                  ->after('ocr_completed_at')
                  ->comment('pending, processing, completed, failed, skipped');
            
            $table->text('matching_notes')
                  ->nullable()
                  ->after('matching_status');
            
            $table->text('matching_error')
                  ->nullable()
                  ->after('matching_notes');
            
            $table->timestamp('matching_started_at')
                  ->nullable()
                  ->after('matching_error');
            
            $table->timestamp('matching_completed_at')
                  ->nullable()
                  ->after('matching_started_at');
            
            $table->index(['matching_status']);
        });
    }

    public function down(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            $table->dropColumn([
                'matching_status',
                'matching_notes',
                'matching_error',
                'matching_started_at',
                'matching_completed_at',
            ]);
        });
    }
};