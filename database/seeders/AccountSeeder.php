<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AccountSeeder extends Seeder
{
    private $now;

    /**
     * Run the database seeds.
     * 
     * CSV VERSION - Import from accounts.csv
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     * - File database/seeders/data/accounts.csv harus ada
     */
    public function run(): void
    {
        $this->command->info('🏪 Seeding Accounts from CSV...');
        $this->command->newLine();
        
        $this->now = Carbon::now();

        // Path ke file CSV
        $csvPath = database_path('seeders/data/accounts.csv');
        
        // Validasi file CSV exists
        if (!file_exists($csvPath)) {
            $this->command->error('❌ CSV file not found at: ' . $csvPath);
            $this->command->info('💡 Please create the file at: database/seeders/data/accounts.csv');
            return;
        }

        // Ambil companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->error('❌ No companies found! Please run CompanySeeder first.');
            return;
        }

        // Baca dan parse CSV
        $accounts = $this->readCsvFile($csvPath);
        
        if ($accounts->isEmpty()) {
            $this->command->error('❌ No data found in CSV file!');
            return;
        }

        $this->command->info("📄 Found {$accounts->count()} accounts in CSV");
        $this->command->newLine();

        $allAccounts = [];

        // Generate accounts untuk setiap company
        foreach ($companies as $company) {
            $this->command->info("   Processing company: {$company->name}");
            
            // Filter accounts untuk company ini dari CSV
            $companyAccounts = $accounts->where('company_id', $company->id);
            
            if ($companyAccounts->isEmpty()) {
                $this->command->warn("   ⚠️  No accounts found for company {$company->id}");
                continue;
            }

            foreach ($companyAccounts as $account) {
                $allAccounts[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company->id,
                    'name' => $account['name'],
                    'code' => $account['code'],
                    'description' => $account['description'],
                    'account_type' => $account['account_type'],
                    'color' => $this->getColorByType($account['account_type']),
                    'priority' => $this->getPriorityByType($account['account_type']),
                    'is_active' => true,
                    'created_at' => $this->now->copy()->subDays(rand(30, 180)),
                    'updated_at' => $this->now,
                    'deleted_at' => null,
                ];
            }
        }

        if (!empty($allAccounts)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($allAccounts, 50);
            foreach ($chunks as $chunk) {
                DB::table('accounts')->insert($chunk);
            }
            
            $this->command->newLine();
            $this->command->info('✅ Accounts seeded successfully!');
            $this->command->info("   Total accounts created: " . count($allAccounts));
            $this->command->info("   Companies: " . $companies->count());
            
            $this->displaySummary();
        } else {
            $this->command->warn('⚠️  No accounts created. Check if companies exist.');
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
        $accounts = collect();
        
        try {
            $file = fopen($csvPath, 'r');
            
            if ($file === false) {
                throw new \Exception('Failed to open CSV file');
            }

            // Skip header row
            $header = fgetcsv($file, 0, ';'); // Delimiter adalah semicolon
            
            // Validasi header
            $expectedHeaders = ['company_id', 'name', 'code', 'description', 'account_type'];
            if ($header !== $expectedHeaders) {
                $this->command->warn('⚠️  CSV header format: expected ' . implode(', ', $expectedHeaders));
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

                // Validasi data
                if (count($row) < 5) {
                    $this->command->warn("   ⚠️  Line {$lineNumber}: Incomplete data, skipping...");
                    continue;
                }

                // Validasi account_type
                $validTypes = ['asset', 'liability', 'equity', 'revenue', 'expense'];
                $accountType = trim($row[4]);
                
                if (!in_array($accountType, $validTypes)) {
                    $this->command->warn("   ⚠️  Line {$lineNumber}: Invalid account_type '{$accountType}', skipping...");
                    continue;
                }

                $accounts->push([
                    'company_id' => (int) trim($row[0]),
                    'name' => trim($row[1]),
                    'code' => trim($row[2]),
                    'description' => trim($row[3]),
                    'account_type' => $accountType,
                ]);
            }

            fclose($file);

            return $accounts;

        } catch (\Exception $e) {
            $this->command->error('❌ Error reading CSV: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get color based on account type
     * 
     * @param string $accountType
     * @return string
     */
    private function getColorByType(string $accountType): string
    {
        $colorMap = [
            'asset' => '#10B981',      // Green - Asset
            'liability' => '#EF4444',  // Red - Liability
            'equity' => '#8B5CF6',     // Purple - Equity
            'revenue' => '#3B82F6',    // Blue - Revenue
            'expense' => '#F59E0B',    // Amber - Expense
        ];

        return $colorMap[$accountType] ?? '#6B7280'; // Default Gray
    }

    /**
     * Get priority based on account type
     * 
     * @param string $accountType
     * @return int
     */
    private function getPriorityByType(string $accountType): int
    {
        $priorityMap = [
            'revenue' => 10,   // Highest priority
            'expense' => 9,
            'asset' => 8,
            'liability' => 7,
            'equity' => 6,
        ];

        return $priorityMap[$accountType] ?? 5; // Default priority
    }

    /**
     * Display summary
     */
    private function displaySummary(): void
    {
        $this->command->newLine();
        $this->command->info('📊 ACCOUNTS SUMMARY:');
        $this->command->info('='.str_repeat('=', 79));

        // Account type distribution
        $typeDistribution = DB::table('accounts')
            ->select('account_type', DB::raw('count(*) as count'))
            ->groupBy('account_type')
            ->orderBy('count', 'desc')
            ->get();

        $this->command->newLine();
        $this->command->info('💼 Account Type Distribution:');
        
        $typeIcons = [
            'asset' => '💰',
            'liability' => '📊',
            'equity' => '💎',
            'revenue' => '💵',
            'expense' => '💸',
        ];

        foreach ($typeDistribution as $type) {
            $icon = $typeIcons[$type->account_type] ?? '📋';
            $bar = str_repeat('█', min((int)($type->count / 2), 30));
            $this->command->info(sprintf("   %s %-12s %s (%d)", 
                $icon,
                ucfirst($type->account_type), 
                $bar, 
                $type->count
            ));
        }

        // Top 10 accounts
        $topAccounts = DB::table('accounts')
            ->select('name', 'code', 'account_type')
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->limit(10)
            ->get();

        $this->command->newLine();
        $this->command->info('🏆 Top 10 Accounts (by priority):');
        foreach ($topAccounts as $idx => $account) {
            $this->command->info(sprintf("   %2d. [%s] %-40s (%s)", 
                $idx + 1,
                $account->code,
                substr($account->name, 0, 40),
                strtoupper($account->account_type)
            ));
        }

        // Company distribution
        $companyDistribution = DB::table('accounts as a')
            ->join('companies as c', 'c.id', '=', 'a.company_id')
            ->select('c.name as company_name', DB::raw('count(a.id) as count'))
            ->groupBy('c.id', 'c.name')
            ->get();

        $this->command->newLine();
        $this->command->info('🏢 Accounts per Company:');
        foreach ($companyDistribution as $dist) {
            $this->command->info(sprintf("   %-30s %d accounts", 
                $dist->company_name, 
                $dist->count
            ));
        }

        $total = DB::table('accounts')->count();
        $activeCount = DB::table('accounts')->where('is_active', true)->count();
        
        $this->command->newLine();
        $this->command->info("Total Accounts: {$total}");
        $this->command->info("Active Accounts: {$activeCount}");
        $this->command->newLine();
        $this->command->info('💡 Accounts are ready for Chart of Accounts mapping!');
    }
}