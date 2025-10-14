<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AccountKeywordSeeder extends Seeder
{
    private $now;
    private $companyId = 1; // Static company_id

    /**
     * Run the database seeds.
     * 
     * Note: Seeder ini untuk Account Keywords berdasarkan outlet
     * Membuat keyword untuk matching otomatis berdasarkan:
     * - Kode outlet (A_01, A_02, dst)
     * - Nama outlet (NAROGONG, HARAPAN INDAH, dst)
     * - Variasi nama (KF 0264, KF 264, 0264, dst)
     */
    public function run(): void
    {
        $this->command->info('🔑 Seeding Account Keywords...');
        
        $this->now = Carbon::now();

        // Cek apakah company_id = 1 ada
        $company = DB::table('companies')->where('id', $this->companyId)->first();
        
        if (!$company) {
            $this->command->error('❌ Company with ID = 1 not found!');
            return;
        }

        // Ambil semua accounts untuk company ini
        $accounts = DB::table('accounts')
            ->where('company_id', $this->companyId)
            ->get();

        if ($accounts->isEmpty()) {
            $this->command->error('❌ No accounts found! Please run AccountSeeder first.');
            return;
        }

        $this->command->info("   Generating keywords for {$accounts->count()} outlets...");

        $keywords = [];
        $totalKeywords = 0;

        foreach ($accounts as $account) {
            $accountKeywords = $this->generateKeywordsForAccount($account);
            $keywords = array_merge($keywords, $accountKeywords);
            $totalKeywords += count($accountKeywords);
        }

        // Insert ke database
        DB::table('account_keywords')->insert($keywords);

        $this->command->newLine();
        $this->command->info('✅ Account Keywords seeded successfully!');
        $this->command->info("   Total accounts: {$accounts->count()}");
        $this->command->info("   Total keywords: {$totalKeywords}");
        $this->command->info("   Avg keywords per account: " . round($totalKeywords / $accounts->count(), 1));
    }

    /**
     * Generate keywords untuk satu account
     */
    private function generateKeywordsForAccount($account): array
    {
        $keywords = [];

        // Parse nama outlet untuk extract informasi
        $name = $account->name;
        $code = $account->code;

        // ========================================
        // 1. KEYWORD BERDASARKAN KODE OUTLET
        // ========================================
        
        // Exact match kode (A_01, A_02, dst)
        $keywords[] = $this->makeKeyword($account->id, [
            'keyword' => $code,
            'match_type' => 'contains',
            'priority' => 10,
            'pattern_description' => "Exact code match: {$code}",
        ]);

        // Variasi kode tanpa underscore (A01, A02)
        $codeNoUnderscore = str_replace('_', '', $code);
        if ($codeNoUnderscore !== $code) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => $codeNoUnderscore,
                'match_type' => 'contains',
                'priority' => 9,
                'pattern_description' => "Code without underscore: {$codeNoUnderscore}",
            ]);
        }

        // ========================================
        // 2. KEYWORD BERDASARKAN NOMOR KF
        // ========================================
        
        // Extract nomor KF (contoh: "KF 0264" dari "KF 0264 NAROGONG")
        if (preg_match('/KF[\s_]?(\d+)/i', $name, $matches)) {
            $kfNumber = $matches[1];
            
            // KF dengan nomor (KF 0264, KF 264, KF0264)
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => "KF {$kfNumber}",
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 9,
                'pattern_description' => "KF number with space: KF {$kfNumber}",
            ]);

            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => "KF{$kfNumber}",
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 9,
                'pattern_description' => "KF number without space: KF{$kfNumber}",
            ]);

            // Nomor tanpa leading zero jika ada (0264 -> 264)
            $kfNumberNoZero = ltrim($kfNumber, '0');
            if ($kfNumberNoZero !== $kfNumber && !empty($kfNumberNoZero)) {
                $keywords[] = $this->makeKeyword($account->id, [
                    'keyword' => "KF {$kfNumberNoZero}",
                    'match_type' => 'contains',
                    'case_sensitive' => false,
                    'priority' => 8,
                    'pattern_description' => "KF number no leading zero: KF {$kfNumberNoZero}",
                ]);
            }
        }

        // ========================================
        // 3. KEYWORD BERDASARKAN NAMA LOKASI
        // ========================================
        
        // Extract nama lokasi (kata terakhir biasanya lokasi)
        // Contoh: "KF 0264 NAROGONG" -> "NAROGONG"
        $nameParts = preg_split('/[\s_]+/', $name);
        $locationName = end($nameParts);

        // Skip jika nama lokasi terlalu pendek atau generic
        if (strlen($locationName) >= 4 && !in_array(strtoupper($locationName), ['RESEP', 'APOTEK', 'PPO'])) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => $locationName,
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 7,
                'pattern_description' => "Location name: {$locationName}",
            ]);

            // Variasi dengan "KF" + lokasi
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => "KF {$locationName}",
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 8,
                'pattern_description' => "KF + location: KF {$locationName}",
            ]);
        }

        // ========================================
        // 4. KEYWORD KHUSUS UNTUK OUTLET RESEP
        // ========================================
        
        if (stripos($name, 'RESEP') !== false) {
            // Extract kode/nama sebelum kata RESEP
            if (preg_match('/KF[\s_]?(\d+)[\s_]+RESEP/i', $name, $matches)) {
                $baseNumber = $matches[1];
                
                $keywords[] = $this->makeKeyword($account->id, [
                    'keyword' => "RESEP {$baseNumber}",
                    'match_type' => 'contains',
                    'case_sensitive' => false,
                    'priority' => 9,
                    'pattern_description' => "Resep keyword: RESEP {$baseNumber}",
                ]);
            }
        }

        // ========================================
        // 5. KEYWORD KHUSUS UNTUK OUTLET PPO
        // ========================================
        
        if (stripos($name, 'PPO') !== false) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => 'PPO',
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 8,
                'pattern_description' => "PPO outlet keyword",
            ]);
        }

        // ========================================
        // 6. KEYWORD UNTUK OUTLET KHUSUS
        // ========================================
        
        // KDI (Kimia Diagnostika)
        if (stripos($name, 'KDI') !== false) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => 'KDI',
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 10,
                'pattern_description' => "KDI outlet keyword",
            ]);
        }

        // KTIM (Kimia Farma Trading)
        if (stripos($name, 'KTIM') !== false) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => 'KTIM',
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 10,
                'pattern_description' => "KTIM outlet keyword",
            ]);
        }

        return $keywords;
    }

    /**
     * Helper untuk membuat keyword array
     */
    private function makeKeyword(int $accountId, array $data): array
    {
        $defaults = [
            'uuid' => Str::uuid(),
            'company_id' => $this->companyId,
            'account_id' => $accountId,
            'keyword' => '',
            'is_regex' => false,
            'case_sensitive' => false,
            'match_type' => 'contains',
            'pattern_description' => null,
            'priority' => 5,
            'is_active' => true,
            'match_count' => 0,
            'last_matched_at' => null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ];

        return array_merge($defaults, $data);
    }
}