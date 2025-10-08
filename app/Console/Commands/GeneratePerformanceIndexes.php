<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GeneratePerformanceIndexes extends Command
{
    protected $signature = 'make:performance-indexes';
    protected $description = 'Generate migration file for performance indexes';

    public function handle()
    {
        $this->info('🚀 Generating Performance Indexes Migration...');
        $this->newLine();

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_add_performance_indexes_to_tables.php";
        $path = database_path("migrations/{$filename}");

        $content = $this->getMigrationContent();

        File::put($path, $content);

        $this->info("✅ Migration created successfully!");
        $this->line("📁 File: {$filename}");
        $this->newLine();
        $this->info("Next steps:");
        $this->line("1. Run: php artisan migrate");
        $this->line("2. Rollback if needed: php artisan migrate:rollback");
        $this->newLine();

        return 0;
    }

    private function getMigrationContent()
    {
        return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Statement Transactions Indexes
        DB::statement('ALTER TABLE statement_transactions ADD INDEX idx_id_deleted (id, deleted_at)');
        DB::statement('ALTER TABLE statement_transactions ADD INDEX idx_date_deleted (transaction_date, deleted_at)');
        DB::statement('ALTER TABLE statement_transactions ADD INDEX idx_verified_deleted (is_verified, deleted_at)');
        DB::statement('ALTER TABLE statement_transactions ADD INDEX idx_matched_deleted (matched_keyword_id, deleted_at)');
        DB::statement('ALTER TABLE statement_transactions ADD INDEX idx_confidence_deleted (confidence_score, deleted_at)');
        DB::statement('ALTER TABLE statement_transactions ADD INDEX idx_type_deleted (type_id, deleted_at)');
        DB::statement('ALTER TABLE statement_transactions ADD INDEX idx_category_deleted (category_id, deleted_at)');
        DB::statement('ALTER TABLE statement_transactions ADD INDEX idx_sub_category_deleted (sub_category_id, deleted_at)');

        // Bank Statements Indexes
        DB::statement('ALTER TABLE bank_statements ADD INDEX idx_id_deleted (id, deleted_at)');
        DB::statement('ALTER TABLE bank_statements ADD INDEX idx_created_deleted (created_at, deleted_at)');
        DB::statement('ALTER TABLE bank_statements ADD INDEX idx_uploaded_deleted (uploaded_at, deleted_at)');
        DB::statement('ALTER TABLE bank_statements ADD INDEX idx_status_deleted (ocr_status, deleted_at)');
        DB::statement('ALTER TABLE bank_statements ADD INDEX idx_bank_deleted (bank_id, deleted_at)');

        // Users Indexes
        DB::statement('ALTER TABLE users ADD INDEX idx_role (role)');
        DB::statement('ALTER TABLE users ADD INDEX idx_id_created (id, created_at)');

        // Keywords Indexes
        DB::statement('ALTER TABLE keywords ADD INDEX idx_active_priority (is_active, priority, deleted_at)');
        DB::statement('ALTER TABLE keywords ADD INDEX idx_sub_category (sub_category_id, deleted_at)');
    }

    public function down(): void
    {
        // Drop Statement Transactions Indexes
        DB::statement('ALTER TABLE statement_transactions DROP INDEX idx_id_deleted');
        DB::statement('ALTER TABLE statement_transactions DROP INDEX idx_date_deleted');
        DB::statement('ALTER TABLE statement_transactions DROP INDEX idx_verified_deleted');
        DB::statement('ALTER TABLE statement_transactions DROP INDEX idx_matched_deleted');
        DB::statement('ALTER TABLE statement_transactions DROP INDEX idx_confidence_deleted');
        DB::statement('ALTER TABLE statement_transactions DROP INDEX idx_type_deleted');
        DB::statement('ALTER TABLE statement_transactions DROP INDEX idx_category_deleted');
        DB::statement('ALTER TABLE statement_transactions DROP INDEX idx_sub_category_deleted');

        // Drop Bank Statements Indexes
        DB::statement('ALTER TABLE bank_statements DROP INDEX idx_id_deleted');
        DB::statement('ALTER TABLE bank_statements DROP INDEX idx_created_deleted');
        DB::statement('ALTER TABLE bank_statements DROP INDEX idx_uploaded_deleted');
        DB::statement('ALTER TABLE bank_statements DROP INDEX idx_status_deleted');
        DB::statement('ALTER TABLE bank_statements DROP INDEX idx_bank_deleted');

        // Drop Users Indexes
        DB::statement('ALTER TABLE users DROP INDEX idx_role');
        DB::statement('ALTER TABLE users DROP INDEX idx_id_created');

        // Drop Keywords Indexes
        DB::statement('ALTER TABLE keywords DROP INDEX idx_active_priority');
        DB::statement('ALTER TABLE keywords DROP INDEX idx_sub_category');
    }
};
PHP;
    }
}