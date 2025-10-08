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
            // Check if file_hash doesn't exist first
            if (!Schema::hasColumn('bank_statements', 'file_hash')) {
                // Add file_hash column for duplicate detection
                $table->string('file_hash', 64)->nullable()->after('file_path')
                      ->comment('SHA-256 hash of uploaded file for duplicate detection');
            }
        });

        // Add indexes after column creation
        Schema::table('bank_statements', function (Blueprint $table) {
            // Add index for faster duplicate lookup
            if (!$this->indexExists('bank_statements', 'idx_file_hash')) {
                $table->index('file_hash', 'idx_file_hash');
            }
            
            // Add composite index for period-based duplicate check
            if (!$this->indexExists('bank_statements', 'idx_period_duplicate')) {
                $table->index(
                    ['bank_id', 'account_number', 'period_from', 'period_to'], 
                    'idx_period_duplicate'
                );
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            // Drop indexes first
            if ($this->indexExists('bank_statements', 'idx_file_hash')) {
                $table->dropIndex('idx_file_hash');
            }
            
            if ($this->indexExists('bank_statements', 'idx_period_duplicate')) {
                $table->dropIndex('idx_period_duplicate');
            }
            
            // Then drop column
            if (Schema::hasColumn('bank_statements', 'file_hash')) {
                $table->dropColumn('file_hash');
            }
        });
    }

    /**
     * Check if index exists on table
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        $result = $connection->select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$database, $table, $index]
        );
        
        return $result[0]->count > 0;
    }
};