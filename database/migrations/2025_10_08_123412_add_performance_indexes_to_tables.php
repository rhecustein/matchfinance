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
        // Statement Transactions Indexes
        Schema::table('statement_transactions', function (Blueprint $table) {
            $table->index(['id', 'deleted_at'], 'idx_id_deleted');
            $table->index(['transaction_date', 'deleted_at'], 'idx_date_deleted');
            $table->index(['is_verified', 'deleted_at'], 'idx_verified_deleted');
            $table->index(['matched_keyword_id', 'deleted_at'], 'idx_matched_deleted');
            $table->index(['confidence_score', 'deleted_at'], 'idx_confidence_deleted');
            $table->index(['type_id', 'deleted_at'], 'idx_type_deleted');
            $table->index(['category_id', 'deleted_at'], 'idx_category_deleted');
            $table->index(['sub_category_id', 'deleted_at'], 'idx_sub_category_deleted');
        });

        // Bank Statements Indexes
        Schema::table('bank_statements', function (Blueprint $table) {
            $table->index(['id', 'deleted_at'], 'idx_bs_id_deleted');
            $table->index(['created_at', 'deleted_at'], 'idx_bs_created_deleted');
            $table->index(['uploaded_at', 'deleted_at'], 'idx_bs_uploaded_deleted');
            $table->index(['ocr_status', 'deleted_at'], 'idx_bs_status_deleted');
            $table->index(['bank_id', 'deleted_at'], 'idx_bs_bank_deleted');
        });

        // Users Indexes
        Schema::table('users', function (Blueprint $table) {
            // Check if column exists first
            if (Schema::hasColumn('users', 'role')) {
                $table->index('role', 'idx_user_role');
            }
            $table->index(['id', 'created_at'], 'idx_user_id_created');
        });

        // Keywords Indexes
        Schema::table('keywords', function (Blueprint $table) {
            $table->index(['is_active', 'priority', 'deleted_at'], 'idx_kw_active_priority');
            $table->index(['sub_category_id', 'deleted_at'], 'idx_kw_sub_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop Statement Transactions Indexes
        Schema::table('statement_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_id_deleted');
            $table->dropIndex('idx_date_deleted');
            $table->dropIndex('idx_verified_deleted');
            $table->dropIndex('idx_matched_deleted');
            $table->dropIndex('idx_confidence_deleted');
            $table->dropIndex('idx_type_deleted');
            $table->dropIndex('idx_category_deleted');
            $table->dropIndex('idx_sub_category_deleted');
        });

        // Drop Bank Statements Indexes
        Schema::table('bank_statements', function (Blueprint $table) {
            $table->dropIndex('idx_bs_id_deleted');
            $table->dropIndex('idx_bs_created_deleted');
            $table->dropIndex('idx_bs_uploaded_deleted');
            $table->dropIndex('idx_bs_status_deleted');
            $table->dropIndex('idx_bs_bank_deleted');
        });

        // Drop Users Indexes
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropIndex('idx_user_role');
            }
            $table->dropIndex('idx_user_id_created');
        });

        // Drop Keywords Indexes
        Schema::table('keywords', function (Blueprint $table) {
            $table->dropIndex('idx_kw_active_priority');
            $table->dropIndex('idx_kw_sub_category');
        });
    }
};