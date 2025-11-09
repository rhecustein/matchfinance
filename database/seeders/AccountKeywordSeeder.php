<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AccountKeywordSeeder extends Seeder
{
    private $now;

    /**
     * Run the database seeds.
     * 
     * CSV VERSION - Import from account_keywords.csv
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     * - Accounts table sudah terisi (AccountSeeder)
     * - File database/seeders/data/account_keywords.csv harus ada
     */
    public function run(): void
    {
        $this->command->info('🔑 Seeding Account Keywords from CSV...');
        $this->command->newLine();
        
        $this->now = Carbon::now();

        // Path ke file CSV
        $csvPath = database_path('seeders/data/account_keywords.csv');
        
        // Validasi file CSV exists
        if (!file_exists($csvPath)) {
            $this->command->error('❌ CSV file not found at: ' . $csvPath);
            $this->command->info('💡 Please create the file at: database/seeders/data/account_keywords.csv');
            return;
        }

        // Ambil companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->error('❌ No companies found! Please run CompanySeeder first.');
            return;
        }

        // Ambil accounts
        $accounts = DB::table('accounts')->get();
        
        if ($accounts->isEmpty()) {
            $this->command->error('❌ No accounts found! Please run AccountSeeder first.');
            return;
        }

        // Baca dan parse CSV
        $accountKeywords = $this->readCsvFile($csvPath);
        
        if ($accountKeywords->isEmpty()) {
            $this->command->error('❌ No data found in CSV file!');
            return;
        }

        $this->command->info("📄 Found {$accountKeywords->count()} account keywords in CSV");
        $this->command->newLine();

        $allAccountKeywords = [];
        $keywordsByAccount = [];

        // Generate account keywords untuk setiap company
        foreach ($companies as $company) {
            $this->command->info("   Processing company: {$company->name}");
            
            // Filter account keywords untuk company ini dari CSV
            $companyAccountKeywords = $accountKeywords->where('company_id', $company->id);
            
            foreach ($companyAccountKeywords as $accountKeyword) {
                // Cari account_id yang sesuai untuk company ini
                $account = $accounts->where('company_id', $company->id)
                                   ->where('id', $accountKeyword['account_id'])
                                   ->first();
                
                if (!$account) {
                    $this->command->warn("   ⚠️  Account ID {$accountKeyword['account_id']} not found for company {$company->id}, skipping...");
                    continue;
                }

                $allAccountKeywords[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company->id,
                    'account_id' => $account->id,
                    'keyword' => $accountKeyword['keyword'],
                    'is_regex' => $accountKeyword['is_regex'],
                    'case_sensitive' => $accountKeyword['case_sensitive'],
                    'match_type' => $accountKeyword['match_type'],
                    'pattern_description' => $accountKeyword['pattern_description'],
                    'priority' => $accountKeyword['priority'],
                    'is_active' => true,
                    'match_count' => 0,
                    'last_matched_at' => null,
                    'created_at' => $this->now->copy()->subDays(rand(5, 120)),
                    'updated_at' => $this->now,
                    'deleted_at' => null,
                ];

                // Track keywords per account untuk summary
                $accountKey = "{$company->id}_{$account->id}";
                if (!isset($keywordsByAccount[$accountKey])) {
                    $keywordsByAccount[$accountKey] = [
                        'account' => $account,
                        'count' => 0
                    ];
                }
                $keywordsByAccount[$accountKey]['count']++;
            }
        }

        if (!empty($allAccountKeywords)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($allAccountKeywords, 200);
            foreach ($chunks as $chunk) {
                DB::table('account_keywords')->insert($chunk);
            }
            
            $this->command->newLine();
            $this->command->info('✅ Account Keywords seeded successfully!');
            $this->command->info("   Total account keywords created: " . count($allAccountKeywords));
            $this->command->info("   Companies: " . $companies->count());
            
            $this->displaySummary($keywordsByAccount);
        } else {
            $this->command->warn('⚠️  No account keywords created. Check if accounts and companies exist.');
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
        $accountKeywords = collect();
        
        try {
            $file = fopen($csvPath, 'r');
            
            if ($file === false) {
                throw new \Exception('Failed to open CSV file');
            }

            // Skip header row
            $header = fgetcsv($file, 0, ';'); // Delimiter adalah semicolon
            
            // Validasi header
            $expectedHeaders = [
                'company_id', 'account_id', 'keyword', 'is_regex',
                'case_sensitive', 'match_type', 'pattern_description', 'priority'
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
                if (count($row) < 8) {
                    $this->command->warn("   ⚠️  Line {$lineNumber}: Incomplete data (expected 8 columns, got " . count($row) . "), skipping...");
                    continue;
                }

                // Validasi match_type
                $validMatchTypes = ['exact', 'contains', 'starts_with', 'ends_with', 'regex'];
                $matchType = trim($row[5]);
                
                if (!in_array($matchType, $validMatchTypes)) {
                    $this->command->warn("   ⚠️  Line {$lineNumber}: Invalid match_type '{$matchType}', skipping...");
                    continue;
                }

                // Parse dan konversi data
                $accountKeywords->push([
                    'company_id' => (int) trim($row[0]),
                    'account_id' => (int) trim($row[1]),
                    'keyword' => trim($row[2]),
                    'is_regex' => (bool) (int) trim($row[3]),
                    'case_sensitive' => (bool) (int) trim($row[4]),
                    'match_type' => $matchType,
                    'pattern_description' => $this->parseNullableString($row[6]),
                    'priority' => (int) trim($row[7]),
                ]);
            }

            fclose($file);

            return $accountKeywords;

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
     * Display summary
     */
    private function displaySummary(array $keywordsByAccount): void
    {
        $this->command->newLine();
        $this->command->info('📊 ACCOUNT KEYWORDS SUMMARY:');
        $this->command->info('='.str_repeat('=', 79));

        // Match type distribution
        $matchTypes = DB::table('account_keywords')
            ->select('match_type', DB::raw('count(*) as count'))
            ->groupBy('match_type')
            ->orderBy('count', 'desc')
            ->get();

        $this->command->newLine();
        $this->command->info('📋 Match Type Distribution:');
        foreach ($matchTypes as $type) {
            $bar = str_repeat('█', min((int)($type->count / 10), 30));
            $this->command->info(sprintf("   %-15s %s (%d)", $type->match_type, $bar, $type->count));
        }

        // Regex vs non-regex
        $regexStats = DB::table('account_keywords')
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
        $priorities = DB::table('account_keywords')
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

        // Top accounts by keyword count
        $topAccounts = DB::table('accounts as a')
            ->join('account_keywords as ak', 'ak.account_id', '=', 'a.id')
            ->select('a.code', 'a.name', DB::raw('count(ak.id) as keyword_count'))
            ->groupBy('a.id', 'a.code', 'a.name')
            ->orderBy('keyword_count', 'desc')
            ->limit(10)
            ->get();

        $this->command->newLine();
        $this->command->info('🏆 Top 10 Accounts by Keywords:');
        foreach ($topAccounts as $idx => $account) {
            $this->command->info(sprintf("   %2d. [%s] %-40s %d keywords", 
                $idx + 1,
                $account->code,
                substr($account->name, 0, 40),
                $account->keyword_count
            ));
        }

        // Case sensitive statistics
        $caseStats = DB::table('account_keywords')
            ->select('case_sensitive', DB::raw('count(*) as count'))
            ->groupBy('case_sensitive')
            ->get();

        $this->command->newLine();
        $this->command->info('🔤 Case Sensitivity:');
        foreach ($caseStats as $stat) {
            $type = $stat->case_sensitive ? 'Case sensitive' : 'Case insensitive';
            $this->command->info(sprintf("   %-20s %d keywords", $type, $stat->count));
        }

        // Average keywords per account
        $totalKeywords = DB::table('account_keywords')->count();
        $totalAccounts = DB::table('accounts')
            ->whereIn('id', function($query) {
                $query->select('account_id')
                      ->from('account_keywords')
                      ->distinct();
            })
            ->count();

        $avgKeywords = $totalAccounts > 0 ? round($totalKeywords / $totalAccounts, 1) : 0;

        $this->command->newLine();
        $this->command->info("Total Account Keywords: {$totalKeywords}");
        $this->command->info("Accounts with Keywords: {$totalAccounts}");
        $this->command->info("Average Keywords per Account: {$avgKeywords}");
        $this->command->newLine();
        $this->command->info('💡 Account keywords are ready for Chart of Accounts matching!');
    }
}