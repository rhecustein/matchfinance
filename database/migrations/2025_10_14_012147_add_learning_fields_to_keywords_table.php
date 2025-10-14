<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            // Learning & Analytics Fields
            if (!Schema::hasColumn('keywords', 'auto_learned')) {
                $table->boolean('auto_learned')
                      ->default(false)
                      ->after('is_active')
                      ->comment('Was this keyword auto-generated from learning');
            }
            
            if (!Schema::hasColumn('keywords', 'learning_source')) {
                $table->enum('learning_source', [
                    'manual',
                    'suggestion',
                    'correction',
                    'pattern',
                    'import'
                ])->default('manual')
                   ->after('auto_learned')
                   ->comment('How this keyword was created');
            }
            
            if (!Schema::hasColumn('keywords', 'effectiveness_score')) {
                $table->integer('effectiveness_score')
                      ->default(50)
                      ->after('match_count')
                      ->comment('Effectiveness score based on accuracy (0-100)');
            }
            
            if (!Schema::hasColumn('keywords', 'false_positive_count')) {
                $table->integer('false_positive_count')
                      ->default(0)
                      ->after('match_count')
                      ->comment('Number of times this keyword was wrong');
            }
            
            if (!Schema::hasColumn('keywords', 'true_positive_count')) {
                $table->integer('true_positive_count')
                      ->default(0)
                      ->after('false_positive_count')
                      ->comment('Number of times this keyword was correct');
            }
            
            if (!Schema::hasColumn('keywords', 'last_reviewed_at')) {
                $table->timestamp('last_reviewed_at')
                      ->nullable()
                      ->after('last_matched_at')
                      ->comment('Last time this keyword was reviewed');
            }
            
            if (!Schema::hasColumn('keywords', 'reviewed_by')) {
                $table->foreignId('reviewed_by')
                      ->nullable()
                      ->after('last_reviewed_at')
                      ->constrained('users')
                      ->nullOnDelete()
                      ->comment('User who last reviewed this keyword');
            }
            
            // Additional pattern fields
            if (!Schema::hasColumn('keywords', 'min_amount')) {
                $table->decimal('min_amount', 15, 2)
                      ->nullable()
                      ->after('pattern_description')
                      ->comment('Minimum transaction amount for this keyword to apply');
            }
            
            if (!Schema::hasColumn('keywords', 'max_amount')) {
                $table->decimal('max_amount', 15, 2)
                      ->nullable()
                      ->after('min_amount')
                      ->comment('Maximum transaction amount for this keyword to apply');
            }
            
            if (!Schema::hasColumn('keywords', 'valid_days')) {
                $table->json('valid_days')
                      ->nullable()
                      ->after('max_amount')
                      ->comment('Days of week when this keyword is valid [1-7]');
            }
            
            if (!Schema::hasColumn('keywords', 'valid_months')) {
                $table->json('valid_months')
                      ->nullable()
                      ->after('valid_days')
                      ->comment('Months when this keyword is valid [1-12]');
            }
            
            // Add indexes for new fields
            $table->index(['auto_learned', 'learning_source'], 'idx_learning');
            $table->index(['effectiveness_score'], 'idx_effectiveness');
            $table->index(['last_reviewed_at'], 'idx_reviewed');
        });
    }

    public function down(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['auto_learned', 'learning_source']);
            $table->dropIndex(['effectiveness_score']);
            $table->dropIndex(['last_reviewed_at']);
            
            // Drop foreign key
            $table->dropForeign(['reviewed_by']);
            
            // Drop columns
            $table->dropColumn([
                'auto_learned',
                'learning_source',
                'effectiveness_score',
                'false_positive_count',
                'true_positive_count',
                'last_reviewed_at',
                'reviewed_by',
                'min_amount',
                'max_amount',
                'valid_days',
                'valid_months'
            ]);
        });
    }
};
