<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AccountKeywordSeeder extends Seeder
{
    private $now;
    private $companyId = 1;

    /**
     * Run the database seeds.
     * 
     * FULL VERSION dengan Multi-Tier Priority & Negative Keywords
     * Berdasarkan analisis 26,357 transaksi bank real
     */
    public function run(): void
    {
        $this->command->info('🔑 Seeding Account Keywords (FULL VERSION)...');
        
        $this->now = Carbon::now();

        // Validasi company
        $company = DB::table('companies')->where('id', $this->companyId)->first();
        if (!$company) {
            $this->command->error('❌ Company with ID = 1 not found!');
            return;
        }

        // Ambil semua accounts
        $accounts = DB::table('accounts')
            ->where('company_id', $this->companyId)
            ->get();

        if ($accounts->isEmpty()) {
            $this->command->error('❌ No accounts found! Please run AccountSeeder first.');
            return;
        }

        $this->command->info("   Generating keywords for {$accounts->count()} outlets...");
        $this->command->newLine();

        $keywords = [];
        $totalKeywords = 0;

        foreach ($accounts as $account) {
            $accountKeywords = $this->generateKeywordsForAccount($account);
            $keywords = array_merge($keywords, $accountKeywords);
            $totalKeywords += count($accountKeywords);
            
            $this->command->info("   ✓ {$account->code} - {$account->name}: " . count($accountKeywords) . " keywords");
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
     * Generate comprehensive keywords untuk satu account
     * Menggunakan 4-Tier Priority System
     */
    private function generateKeywordsForAccount($account): array
    {
        $keywords = [];
        $name = $account->name;
        $code = $account->code;

        // Parse informasi outlet
        $kfNumber = $this->extractKFNumber($name);
        $location = $this->extractLocation($name);
        $isResep = stripos($name, 'RESEP') !== false;
        $isKlinik = stripos($name, 'KLINIK') !== false;
        $isApotek = stripos($name, 'APOTEK') !== false;
        $isPPO = stripos($name, 'PPO') !== false;
        $isKDI = stripos($name, 'KDI') !== false;
        $isKTIM = stripos($name, 'KTIM') !== false;

        // ========================================
        // TIER 1: EXACT MATCH (Priority 10)
        // ========================================
        
        // 1.1 Kode outlet exact
        $keywords[] = $this->makeKeyword($account->id, [
            'keyword' => $code,
            'match_type' => 'contains',
            'priority' => 10,
            'pattern_description' => "Exact code: {$code}",
        ]);

        // 1.2 Kode tanpa underscore
        $codeNoUnderscore = str_replace('_', '', $code);
        if ($codeNoUnderscore !== $code) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => $codeNoUnderscore,
                'match_type' => 'contains',
                'priority' => 10,
                'pattern_description' => "Code without underscore: {$codeNoUnderscore}",
            ]);
        }

        // 1.3 KF + Nomor lengkap (jika ada)
        if ($kfNumber) {
            // KF 0264 (dengan spasi)
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => "KF {$kfNumber}",
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 10,
                'pattern_description' => "KF + number with space: KF {$kfNumber}",
            ]);

            // KF0264 (tanpa spasi)
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => "KF{$kfNumber}",
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 10,
                'pattern_description' => "KF + number without space: KF{$kfNumber}",
            ]);
        }

        // 1.4 Nama lokasi lengkap (case-insensitive)
        if ($location && strlen($location) >= 4) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => $location,
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 10,
                'pattern_description' => "Full location name: {$location}",
            ]);
        }

        // ========================================
        // TIER 2: STRONG MATCH (Priority 8-9)
        // ========================================
        
        // 2.1 KF + Nomor tanpa leading zero
        if ($kfNumber) {
            $kfNumberNoZero = ltrim($kfNumber, '0');
            if ($kfNumberNoZero !== $kfNumber && !empty($kfNumberNoZero)) {
                $keywords[] = $this->makeKeyword($account->id, [
                    'keyword' => "KF {$kfNumberNoZero}",
                    'match_type' => 'contains',
                    'case_sensitive' => false,
                    'priority' => 9,
                    'pattern_description' => "KF + number no zero: KF {$kfNumberNoZero}",
                ]);

                $keywords[] = $this->makeKeyword($account->id, [
                    'keyword' => "KF{$kfNumberNoZero}",
                    'match_type' => 'contains',
                    'case_sensitive' => false,
                    'priority' => 9,
                    'pattern_description' => "KF + number no zero (no space): KF{$kfNumberNoZero}",
                ]);
            }
        }

        // 2.2 Nomor saja (4 digit) - dengan negative keywords
        if ($kfNumber && strlen($kfNumber) === 4) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => $kfNumber,
                'match_type' => 'contains',
                'priority' => 8,
                'pattern_description' => "Number only: {$kfNumber} (Context: after KF or in transaction code)",
            ]);
        }

        // 2.3 Prefix spesifik + lokasi
        if ($location) {
            // LIPH KF + lokasi (untuk setoran)
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => "LIPH KF {$location}",
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 9,
                'pattern_description' => "LIPH prefix: LIPH KF {$location}",
            ]);

            // Variasi tanpa spasi
            $locationNoSpace = str_replace(' ', '', $location);
            if ($locationNoSpace !== $location) {
                $keywords[] = $this->makeKeyword($account->id, [
                    'keyword' => "LIPH KF{$locationNoSpace}",
                    'match_type' => 'contains',
                    'case_sensitive' => false,
                    'priority' => 8,
                    'pattern_description' => "LIPH no space: LIPH KF{$locationNoSpace}",
                ]);
            }

            // KLINIK KF + lokasi (khusus untuk klinik)
            if ($isKlinik) {
                $keywords[] = $this->makeKeyword($account->id, [
                    'keyword' => "KLINIK KF {$location}",
                    'match_type' => 'contains',
                    'case_sensitive' => false,
                    'priority' => 9,
                    'pattern_description' => "Klinik prefix: KLINIK KF {$location}",
                ]);

                $keywords[] = $this->makeKeyword($account->id, [
                    'keyword' => "KLINIK {$location}",
                    'match_type' => 'contains',
                    'case_sensitive' => false,
                    'priority' => 9,
                    'pattern_description' => "Klinik location: KLINIK {$location}",
                ]);
            }

            // QR + KF pattern
            if ($kfNumber) {
                $keywords[] = $this->makeKeyword($account->id, [
                    'keyword' => "QR.*KF.*{$kfNumber}",
                    'match_type' => 'regex',
                    'is_regex' => true,
                    'case_sensitive' => false,
                    'priority' => 8,
                    'pattern_description' => "QR pattern: QR...KF...{$kfNumber}",
                ]);
            }
        }

        // ========================================
        // TIER 3: PARTIAL MATCH (Priority 6-7)
        // ========================================
        
        // 3.1 Nama lokasi pendek (single word)
        if ($location) {
            $locationWords = preg_split('/[\s_]+/', $location);
            foreach ($locationWords as $word) {
                if (strlen($word) >= 4 && !in_array(strtoupper($word), ['RESEP', 'APOTEK', 'KLINIK'])) {
                    $keywords[] = $this->makeKeyword($account->id, [
                        'keyword' => $word,
                        'match_type' => 'contains',
                        'case_sensitive' => false,
                        'priority' => 7,
                        'pattern_description' => "Location word: {$word} (from {$location})",
                    ]);
                }
            }
        }

        // 3.2 Typo umum & variasi
        $typos = $this->getTypoVariations($location, $name);
        foreach ($typos as $typo) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => $typo['keyword'],
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 7,
                'pattern_description' => "Typo/variation: {$typo['description']}",
            ]);
        }

        // 3.3 Kode internal (pc, bs, bo, dll)
        $internalCodes = $this->getInternalCodes($location, $code);
        foreach ($internalCodes as $ic) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => $ic,
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 6,
                'pattern_description' => "Internal code: {$ic}",
            ]);
        }

        // ========================================
        // TIER 4: FALLBACK (Priority 5)
        // ========================================
        
        // 4.1 Singkatan lokasi
        $abbreviations = $this->getLocationAbbreviations($location);
        foreach ($abbreviations as $abbr) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => $abbr,
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 5,
                'pattern_description' => "Abbreviation: {$abbr} for {$location}",
            ]);
        }

        // ========================================
        // SPECIAL CASES: RESEP, PPO, KDI, KTIM
        // ========================================
        
        if ($isResep) {
            $keywords = array_merge($keywords, $this->getResepKeywords($account->id, $kfNumber, $location));
        }

        if ($isPPO) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => 'PPO',
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 9,
                'pattern_description' => 'PPO outlet identifier',
            ]);
        }

        if ($isKDI) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => 'KDI',
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 10,
                'pattern_description' => 'KDI (Kimia Diagnostika) identifier',
            ]);
        }

        if ($isKTIM) {
            $keywords[] = $this->makeKeyword($account->id, [
                'keyword' => 'KTIM',
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 10,
                'pattern_description' => 'KTIM (Kimia Farma Trading) identifier',
            ]);
        }

        return $keywords;
    }

    /**
     * Extract nomor KF dari nama outlet
     */
    private function extractKFNumber($name): ?string
    {
        if (preg_match('/KF[\s_]?(\d{4})/i', $name, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extract nama lokasi dari nama outlet
     */
    private function extractLocation($name): ?string
    {
        // Remove KF number pattern
        $cleaned = preg_replace('/KF[\s_]?\d{4}[\s_]?/i', '', $name);
        
        // Remove RESEP, APOTEK, PPO, KLINIK
        $cleaned = preg_replace('/(RESEP|APOTEK|PPO|KLINIK)[\s_]?/i', '', $cleaned);
        
        // Trim
        $cleaned = trim($cleaned);
        
        return !empty($cleaned) ? $cleaned : null;
    }

    /**
     * Generate typo variations berdasarkan pola real dari database
     */
    private function getTypoVariations($location, $fullName): array
    {
        $variations = [];

        if (!$location) return $variations;

        $locationUpper = strtoupper($location);

        // Specific typo patterns dari analisis data
        $typoMap = [
            'HARAPAN INDAH' => [
                ['keyword' => 'HARAPAN INDA', 'description' => 'Common typo: INDA instead of INDAH']
            ],
            'WISMA ASRI' => [
                ['keyword' => 'KFWISMA', 'description' => 'Concatenated: KF+WISMA'],
                ['keyword' => 'KF WISMA', 'description' => 'Shortened: KF WISMA (without ASRI)'],
                ['keyword' => 'WISMA', 'description' => 'Short form: WISMA only']
            ],
            'CIKARANG' => [
                ['keyword' => 'CKRG', 'description' => 'Abbreviation: CKRG'],
                ['keyword' => 'KF CKRG', 'description' => 'KF + abbreviation']
            ],
            'KALI ABANG' => [
                ['keyword' => 'KALIABANG', 'description' => 'No space: KALIABANG']
            ],
            'CAKRA RAYA' => [
                ['keyword' => 'CAKRARAYA', 'description' => 'No space: CAKRARAYA']
            ],
            'JATI ASIH' => [
                ['keyword' => 'JATIASIH', 'description' => 'No space: JATIASIH']
            ],
        ];

        if (isset($typoMap[$locationUpper])) {
            $variations = $typoMap[$locationUpper];
        }

        return $variations;
    }

    /**
     * Generate internal codes (pc, bs, bo, dll)
     */
    private function getInternalCodes($location, $code): array
    {
        $codes = [];

        if (!$location) return $codes;

        $locationLower = strtolower(str_replace(' ', '', $location));

        // Pattern: "pc wisma", "bs kf granwis", "BO BEKASI"
        $codes[] = "pc {$locationLower}";
        $codes[] = "bs {$locationLower}";
        $codes[] = "bo {$locationLower}";

        return $codes;
    }

    /**
     * Generate abbreviations
     */
    private function getLocationAbbreviations($location): array
    {
        $abbr = [];

        if (!$location) return $abbr;

        // Common abbreviations dari data
        $abbrMap = [
            'CIKARANG' => ['ckrg'],
            'KALIMALANG' => ['krg'],
            'GRANWIS' => ['grwis'],
            'SUMMARECON' => ['summa'],
        ];

        $locationUpper = strtoupper($location);
        if (isset($abbrMap[$locationUpper])) {
            $abbr = $abbrMap[$locationUpper];
        }

        return $abbr;
    }

    /**
     * Generate keywords khusus untuk outlet RESEP
     */
    private function getResepKeywords($accountId, $kfNumber, $location): array
    {
        $keywords = [];

        // RESEP + nomor KF
        if ($kfNumber) {
            $keywords[] = $this->makeKeyword($accountId, [
                'keyword' => "RESEP {$kfNumber}",
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 9,
                'pattern_description' => "Resep pattern: RESEP {$kfNumber}",
            ]);

            $kfNumberNoZero = ltrim($kfNumber, '0');
            if ($kfNumberNoZero !== $kfNumber) {
                $keywords[] = $this->makeKeyword($accountId, [
                    'keyword' => "RESEP {$kfNumberNoZero}",
                    'match_type' => 'contains',
                    'case_sensitive' => false,
                    'priority' => 9,
                    'pattern_description' => "Resep no zero: RESEP {$kfNumberNoZero}",
                ]);
            }
        }

        // RESEP + lokasi
        if ($location) {
            $keywords[] = $this->makeKeyword($accountId, [
                'keyword' => "RESEP {$location}",
                'match_type' => 'contains',
                'case_sensitive' => false,
                'priority' => 8,
                'pattern_description' => "Resep location: RESEP {$location}",
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