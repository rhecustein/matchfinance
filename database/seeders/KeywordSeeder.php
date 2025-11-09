<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class KeywordSeeder extends Seeder
{
    private $now;

    /**
     * Run the database seeds.
     * 
     * CSV VERSION - Import from keywords.csv
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     * - Types table sudah terisi (TypeSeeder)
     * - Categories table sudah terisi (CategorySeeder)
     * - SubCategories table sudah terisi (SubCategorySeeder)
     * - File database/seeders/data/keywords.csv harus ada
     */
    public function run(): void
    {
        $this->command->info('🔑 Seeding Keywords from CSV...');
        $this->command->newLine();
        
        $this->now = Carbon::now();

        // Path ke file CSV
        $csvPath = database_path('seeders/data/keywords.csv');
        
        // Validasi file CSV exists
        if (!file_exists($csvPath)) {
            $this->command->error('❌ CSV file not found at: ' . $csvPath);
            $this->command->info('💡 Please create the file at: database/seeders/data/keywords.csv');
            return;
        }

        // Ambil companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->error('❌ No companies found! Please run CompanySeeder first.');
            return;
        }

        // Ambil sub-categories
        $subCategories = DB::table('sub_categories')->get();
        
        if ($subCategories->isEmpty()) {
            $this->command->error('❌ No sub-categories found! Please run SubCategorySeeder first.');
            return;
        }

        // Baca dan parse CSV
        $keywords = $this->readCsvFile($csvPath);
        
        if ($keywords->isEmpty()) {
            $this->command->error('❌ No data found in CSV file!');
            return;
        }

        $this->command->info("📄 Found {$keywords->count()} keywords in CSV");
        $this->command->newLine();

        $allKeywords = [];

        // Generate keywords untuk setiap company
        foreach ($companies as $company) {
            $this->command->info("   Processing company: {$company->name}");
            
            // Filter keywords untuk company ini dari CSV
            $companyKeywords = $keywords->where('company_id', $company->id);
            
            foreach ($companyKeywords as $keyword) {
                // Cari sub_category_id yang sesuai untuk company ini
                $subCategory = $subCategories->where('company_id', $company->id)
                                            ->where('id', $keyword['sub_category_id'])
                                            ->first();
                
                if (!$subCategory) {
                    $this->command->warn("   ⚠️  SubCategory ID {$keyword['sub_category_id']} not found for company {$company->id}, skipping...");
                    continue;
                }

                $allKeywords[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company->id,
                    'sub_category_id' => $subCategory->id,
                    'keyword' => $keyword['keyword'],
                    'is_regex' => $keyword['is_regex'],
                    'case_sensitive' => $keyword['case_sensitive'],
                    'match_type' => $keyword['match_type'],
                    'pattern_description' => $keyword['pattern_description'],
                    'min_amount' => $keyword['min_amount'],
                    'max_amount' => $keyword['max_amount'],
                    'valid_days' => $keyword['valid_days'],
                    'valid_months' => $keyword['valid_months'],
                    'priority' => $keyword['priority'],
                    'is_active' => $keyword['is_active'],
                    'match_count' => $keyword['match_count'],
                    'last_matched_at' => $keyword['last_matched_at'],
                    'auto_learned' => $keyword['auto_learned'],
                    'learning_source' => $keyword['learning_source'],
                    'effectiveness_score' => $keyword['effectiveness_score'],
                    'false_positive_count' => $keyword['false_positive_count'],
                    'true_positive_count' => $keyword['true_positive_count'],
                    'last_reviewed_at' => $keyword['last_reviewed_at'],
                    'reviewed_by' => $keyword['reviewed_by'],
                    'created_at' => $this->now->copy()->subDays(rand(5, 120)),
                    'updated_at' => $this->now,
                    'deleted_at' => null,
                ];
            }
        }

        if (!empty($allKeywords)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($allKeywords, 200);
            foreach ($chunks as $chunk) {
                DB::table('keywords')->insert($chunk);
            }
            
            $this->command->newLine();
            $this->command->info('✅ Keywords seeded successfully!');
            $this->command->info("   Total keywords created: " . count($allKeywords));
            $this->command->info("   Companies: " . $companies->count());
            
            $this->displayStatistics();
        } else {
            $this->command->warn('⚠️  No keywords created. Check if sub-categories and companies exist.');
        }
    }

    /**
     * Read and parse CSV file
     * 
     * @param string $csvPath
     * @return \Illuminate\Support\Collection
     */
    private function readCsvFile(string $csvPath): \Illuminate\Support\Collection
    {
        $keywords = collect();
        
        try {
            $file = fopen($csvPath, 'r');
            
            if ($file === false) {
                throw new \Exception('Failed to open CSV file');
            }

            // Skip header row
            $header = fgetcsv($file, 0, ';'); // Delimiter adalah semicolon
            
            // Validasi header
            $expectedHeaders = [
                'company_id', 'sub_category_id', 'keyword', 'is_regex', 
                'case_sensitive', 'match_type', 'pattern_description', 
                'min_amount', 'max_amount', 'valid_days', 'valid_months',
                'priority', 'is_active', 'match_count', 'last_matched_at',
                'auto_learned', 'learning_source', 'effectiveness_score',
                'false_positive_count', 'true_positive_count', 
                'last_reviewed_at', 'reviewed_by'
            ];
            
            if ($header !== $expectedHeaders) {
                $this->command->warn('⚠️  CSV header format mismatch');
                $this->command->warn('    Expected: ' . implode(', ', $expectedHeaders));
                $this->command->warn('    Got: ' . implode(', ', $header));
            }

            // Baca setiap baris
            $lineNumber = 1;
            while (($row = fgetcsv($file, 0, ';')) !== false) {
                $lineNumber++;
                
                // Skip empty rows
                if (empty($row[0]) && empty($row[1]) && empty($row[2])) {
                    continue;
                }

                // Validasi data minimal
                if (count($row) < 22) {
                    $this->command->warn("   ⚠️  Line {$lineNumber}: Incomplete data (expected 22 columns, got " . count($row) . "), skipping...");
                    continue;
                }

                // Parse dan konversi data
                $keywords->push([
                    'company_id' => (int) trim($row[0]),
                    'sub_category_id' => (int) trim($row[1]),
                    'keyword' => trim($row[2]),
                    'is_regex' => (bool) (int) trim($row[3]),
                    'case_sensitive' => (bool) (int) trim($row[4]),
                    'match_type' => trim($row[5]),
                    'pattern_description' => trim($row[6]),
                    'min_amount' => $this->parseNullableDecimal($row[7]),
                    'max_amount' => $this->parseNullableDecimal($row[8]),
                    'valid_days' => $this->parseNullableJson($row[9]),
                    'valid_months' => $this->parseNullableJson($row[10]),
                    'priority' => (int) trim($row[11]),
                    'is_active' => (bool) (int) trim($row[12]),
                    'match_count' => (int) trim($row[13]),
                    'last_matched_at' => $this->parseNullableDateTime($row[14]),
                    'auto_learned' => (bool) (int) trim($row[15]),
                    'learning_source' => $this->parseNullableString($row[16]),
                    'effectiveness_score' => $this->parseNullableDecimal($row[17]),
                    'false_positive_count' => (int) trim($row[18]),
                    'true_positive_count' => (int) trim($row[19]),
                    'last_reviewed_at' => $this->parseNullableDateTime($row[20]),
                    'reviewed_by' => $this->parseNullableInt($row[21]),
                ]);
            }

            fclose($file);

            return $keywords;

        } catch (\Exception $e) {
            $this->command->error('❌ Error reading CSV: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Parse nullable string
     */
    private function parseNullableString($value): ?string
    {
        $trimmed = trim($value);
        return ($trimmed === '' || strtoupper($trimmed) === 'NULL') ? null : $trimmed;
    }

    /**
     * Parse nullable integer
     */
    private function parseNullableInt($value): ?int
    {
        $trimmed = trim($value);
        return ($trimmed === '' || strtoupper($trimmed) === 'NULL') ? null : (int) $trimmed;
    }

    /**
     * Parse nullable decimal
     */
    private function parseNullableDecimal($value): ?float
    {
        $trimmed = trim($value);
        return ($trimmed === '' || strtoupper($trimmed) === 'NULL') ? null : (float) $trimmed;
    }

    /**
     * Parse nullable datetime
     */
    private function parseNullableDateTime($value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || strtoupper($trimmed) === 'NULL') {
            return null;
        }
        
        try {
            return Carbon::parse($trimmed)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse nullable JSON
     */
    private function parseNullableJson($value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || strtoupper($trimmed) === 'NULL') {
            return null;
        }
        
        // Jika sudah JSON format, return as is
        if (json_decode($trimmed) !== null) {
            return $trimmed;
        }
        
        return null;
    }

    /**
     * Display statistics
     */
    private function displayStatistics(): void
    {
        $this->command->newLine();
        $this->command->info('📊 KEYWORD STATISTICS:');
        $this->command->info('='.str_repeat('=', 79));

        // Match type distribution
        $matchTypes = DB::table('keywords')
            ->select('match_type', DB::raw('count(*) as count'))
            ->groupBy('match_type')
            ->get();

        $this->command->newLine();
        $this->command->info('📋 Match Type Distribution:');
        foreach ($matchTypes as $type) {
            $bar = str_repeat('█', min((int)($type->count / 10), 30));
            $this->command->info(sprintf("   %-15s %s (%d)", $type->match_type, $bar, $type->count));
        }

        // Regex vs non-regex
        $regexStats = DB::table('keywords')
            ->select('is_regex', DB::raw('count(*) as count'))
            ->groupBy('is_regex')
            ->get();

        $this->command->newLine();
        $this->command->info('🔍 Pattern Complexity:');
        foreach ($regexStats as $stat) {
            $type = $stat->is_regex ? 'Regex patterns' : 'Simple patterns';
            $total = array_sum(array_column($regexStats->toArray(), 'count'));
            $percent = $total > 0 ? round(($stat->count / $total) * 100, 1) : 0;
            $this->command->info(sprintf("   %-20s %d (%s%%)", $type, $stat->count, $percent));
        }

        // Priority distribution
        $priorities = DB::table('keywords')
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->orderBy('priority', 'desc')
            ->get();

        $this->command->newLine();
        $this->command->info('🎯 Priority Distribution:');
        foreach ($priorities as $prio) {
            $bar = str_repeat('█', min((int)($prio->count / 10), 30));
            $this->command->info(sprintf("   Priority %d: %s (%d)", $prio->priority, $bar, $prio->count));
        }

        // Learning source distribution
        $learningSources = DB::table('keywords')
            ->select('learning_source', DB::raw('count(*) as count'))
            ->groupBy('learning_source')
            ->get();

        $this->command->newLine();
        $this->command->info('📚 Learning Source Distribution:');
        foreach ($learningSources as $source) {
            $sourceName = $source->learning_source ?? 'manual';
            $this->command->info(sprintf("   %-20s %d keywords", $sourceName, $source->count));
        }

        // Auto-learned vs manual
        $autoLearnedStats = DB::table('keywords')
            ->select('auto_learned', DB::raw('count(*) as count'))
            ->groupBy('auto_learned')
            ->get();

        $this->command->newLine();
        $this->command->info('🤖 Auto-Learning Statistics:');
        foreach ($autoLearnedStats as $stat) {
            $type = $stat->auto_learned ? 'Auto-learned' : 'Manual';
            $this->command->info(sprintf("   %-20s %d keywords", $type, $stat->count));
        }

        // Top sub-categories
        $topSubs = DB::table('sub_categories as sc')
            ->join('keywords as k', 'k.sub_category_id', '=', 'sc.id')
            ->select('sc.name', DB::raw('count(k.id) as keyword_count'))
            ->groupBy('sc.id', 'sc.name')
            ->orderBy('keyword_count', 'desc')
            ->limit(10)
            ->get();

        $this->command->newLine();
        $this->command->info('🏆 Top 10 Sub-Categories by Keywords:');
        foreach ($topSubs as $idx => $sub) {
            $this->command->info(sprintf("   %2d. %-50s %d keywords", 
                $idx + 1, 
                substr($sub->name, 0, 50), 
                $sub->keyword_count
            ));
        }

        // Active vs Inactive
        $activeStats = DB::table('keywords')
            ->select('is_active', DB::raw('count(*) as count'))
            ->groupBy('is_active')
            ->get();

        $this->command->newLine();
        $this->command->info('✅ Active Status:');
        foreach ($activeStats as $stat) {
            $status = $stat->is_active ? 'Active' : 'Inactive';
            $this->command->info(sprintf("   %-20s %d keywords", $status, $stat->count));
        }

        $total = DB::table('keywords')->count();
        $this->command->newLine();
        $this->command->info("Total Keywords: {$total}");
        $this->command->newLine();
        $this->command->info('💡 Keywords are ready for high-accuracy transaction matching!');
    }
}