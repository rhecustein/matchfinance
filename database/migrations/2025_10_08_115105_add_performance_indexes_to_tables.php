<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for performance optimization
     */
    public function up(): void
    {
        // Optimize statement_transactions table
        Schema::table('statement_transactions', function (Blueprint $table) {
            // Index untuk sorting dan filtering
            $table->index(['id', 'deleted_at'], 'idx_id_deleted'); // Latest queries
            $table->index(['transaction_date', 'deleted_at'], 'idx_date_deleted'); // Date filtering
            $table->index(['is_verified', 'deleted_at'], 'idx_verified_deleted'); // Verification filtering
            $table->index(['matched_keyword_id', 'deleted_at'], 'idx_matched_deleted'); // Matching filtering
            $table->index(['confidence_score', 'deleted_at'], 'idx_confidence_deleted'); // Low confidence queries
            
            // Composite index untuk joins
            $table->index(['type_id', 'deleted_at'], 'idx_type_deleted');
            $table->index(['category_id', 'deleted_at'], 'idx_category_deleted');
            $table->index(['sub_category_id', 'deleted_at'], 'idx_sub_category_deleted');
            
            // Index untuk aggregation queries
            $table->index(['transaction_date', 'amount', 'deleted_at'], 'idx_date_amount_deleted');
        });

        // Optimize bank_statements table
        Schema::table('bank_statements', function (Blueprint $table) {
            // Index untuk sorting
            $table->index(['id', 'deleted_at'], 'idx_id_deleted');
            $table->index(['created_at', 'deleted_at'], 'idx_created_deleted');
            $table->index(['uploaded_at', 'deleted_at'], 'idx_uploaded_deleted');
            
            // Index untuk filtering
            $table->index(['ocr_status', 'deleted_at'], 'idx_status_deleted');
            $table->index(['bank_id', 'deleted_at'], 'idx_bank_deleted');
        });

        // Optimize users table
        Schema::table('users', function (Blueprint $table) {
            // Index untuk role filtering
            if (!$this->indexExists('users', 'idx_role')) {
                $table->index('role', 'idx_role');
            }
            
            // Index untuk sorting
            if (!$this->indexExists('users', 'idx_id_created')) {
                $table->index(['id', 'created_at'], 'idx_id_created');
            }
        });

        // Optimize keywords table
        Schema::table('keywords', function (Blueprint $table) {
            // Index untuk matching queries
            if (!$this->indexExists('keywords', 'idx_active_priority')) {
                $table->index(['is_active', 'priority', 'deleted_at'], 'idx_active_priority');
            }
            
            if (!$this->indexExists('keywords', 'idx_sub_category')) {
                $table->index(['sub_category_id', 'deleted_at'], 'idx_sub_category');
            }
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Drop indexes from statement_transactions
        Schema::table('statement_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_id_deleted');
            $table->dropIndex('idx_date_deleted');
            $table->dropIndex('idx_verified_deleted');
            $table->dropIndex('idx_matched_deleted');
            $table->dropIndex('idx_confidence_deleted');
            $table->dropIndex('idx_type_deleted');
            $table->dropIndex('idx_category_deleted');
            $table->dropIndex('idx_sub_category_deleted');
            $table->dropIndex('idx_date_amount_deleted');
        });

        // Drop indexes from bank_statements
        Schema::table('bank_statements', function (Blueprint $table) {
            $table->dropIndex('idx_id_deleted');
            $table->dropIndex('idx_created_deleted');
            $table->dropIndex('idx_uploaded_deleted');
            $table->dropIndex('idx_status_deleted');
            $table->dropIndex('idx_bank_deleted');
        });

        // Drop indexes from users
        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'idx_role')) {
                $table->dropIndex('idx_role');
            }
            if ($this->indexExists('users', 'idx_id_created')) {
                $table->dropIndex('idx_id_created');
            }
        });

        // Drop indexes from keywords
        Schema::table('keywords', function (Blueprint $table) {
            if ($this->indexExists('keywords', 'idx_active_priority')) {
                $table->dropIndex('idx_active_priority');
            }
            if ($this->indexExists('keywords', 'idx_sub_category')) {
                $table->dropIndex('idx_sub_category');
            }
        });
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes($table);
        
        return array_key_exists($index, $indexes);
    }
};