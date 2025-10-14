<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class KeywordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Note: Seeder ini membutuhkan:
     * - SubCategories table sudah terisi (SubCategorySeeder)
     */
    public function run(): void
    {
        // Get all sub-categories with their names
        $subCategories = DB::table('sub_categories')
            ->select('id', 'company_id', 'name')
            ->orderBy('company_id')
            ->orderBy('priority', 'desc')
            ->get();
        
        if ($subCategories->isEmpty()) {
            $this->command->warn('⚠️  No sub-categories found! Please run SubCategorySeeder first.');
            return;
        }

        $now = Carbon::now();
        $allKeywords = [];
        
        // Keywords mapping per sub-category name (based on actual transaction patterns from CSV)
        $keywordMappings = [
            // SETORAN Keywords
            'Setoran LIPH' => [
                ['keyword' => 'LIPH', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'setor tunai.*LIPH', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
                ['keyword' => 'setoran tunai.*LIPH', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
            ],
            'Setoran Apotek' => [
                ['keyword' => 'setor tunai.*apotek', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
                ['keyword' => 'setoran.*apotek', 'match_type' => 'regex', 'priority' => 9, 'is_regex' => true],
                ['keyword' => 'PTUGBKS', 'match_type' => 'contains', 'priority' => 8, 'is_regex' => false],
            ],
            'Setoran Klinik' => [
                ['keyword' => 'setor tunai.*klinik', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
                ['keyword' => 'setoran.*klinik', 'match_type' => 'regex', 'priority' => 9, 'is_regex' => true],
                ['keyword' => 'klinik', 'match_type' => 'contains', 'priority' => 6, 'is_regex' => false],
            ],
            'Setoran Cabang Bekasi' => [
                ['keyword' => 'setor.*bekasi', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
                ['keyword' => 'bekasi.*setor', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
                ['keyword' => 'MG3 BEKASI', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
            ],
            'Setoran Wisma Asri' => [
                ['keyword' => 'setor.*wisma', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
                ['keyword' => 'wisma.*asri.*setor', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
                ['keyword' => 'wisma asri', 'match_type' => 'contains', 'priority' => 8, 'is_regex' => false],
            ],
            
            // TRANSFER BANK Keywords
            'Transfer BCA' => [
                ['keyword' => 'CENAIDJA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'BCANIDJA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
            ],
            'Transfer BNI' => [
                ['keyword' => 'BNINIDJA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
            ],
            'Transfer BSI' => [
                ['keyword' => 'BSI DR', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'BSIIIDJA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
            ],
            
            // PENERIMAAN OPERASIONAL Keywords
            'COD' => [
                ['keyword' => 'COD', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'cod ke', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
                ['keyword' => 'cash on delivery', 'match_type' => 'contains', 'priority' => 8, 'is_regex' => false],
            ],
            'Klaim Asuransi' => [
                ['keyword' => 'asuransi', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
                ['keyword' => 'klaim.*asuransi', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
            ],
            
            // PEMBAYARAN VENDOR Keywords
            'Kimia Farma Apotek' => [
                ['keyword' => 'KIMIA FARMA APOTEK', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'DARI KIMIA FARMA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'KE KIMIA FARMA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'kimia farma', 'match_type' => 'contains', 'priority' => 8, 'is_regex' => false],
            ],
            'PT Penta Valent' => [
                ['keyword' => 'PENTA VALENT TBK PT', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'PENTA VALENT', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
            ],
            
            // BIAYA ADMIN Keywords
            'Transfer Fee' => [
                ['keyword' => 'Transfer Fee', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'fee99102', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
            ],
            
            // PEMBELIAN Keywords
            'Pembelian Medicine' => [
                ['keyword' => 'pemb.*medicine', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
                ['keyword' => 'pemb.*box.*medicine', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
                ['keyword' => 'medicine', 'match_type' => 'contains', 'priority' => 7, 'is_regex' => false],
            ],
            'Pembelian Alkes' => [
                ['keyword' => 'pemb.*alkes', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
                ['keyword' => 'alat kesehatan', 'match_type' => 'contains', 'priority' => 8, 'is_regex' => false],
            ],
            
            // TRANSFER INTERNAL Keywords
            'MCM InhouseTrf DARI' => [
                ['keyword' => 'MCM InhouseTrf DARI', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'InhouseTrf DARI', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
            ],
            'MCM InhouseTrf KE' => [
                ['keyword' => 'MCM InhouseTrf KE', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'InhouseTrf KE', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
            ],
            
            // BOP Keywords
            'BOP Februari' => [
                ['keyword' => 'BOP FEBRUARI 2025', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'BOP FEBRUARI', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
            ],
            'BOP Januari' => [
                ['keyword' => 'BOP JANUARI', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
            ],
            'BOP Maret' => [
                ['keyword' => 'BOP MARET', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
            ],
            
            // PAYROLL Keywords - based on actual names in transactions
            'Gaji Staff' => [
                ['keyword' => 'LISA ANGGRAINI', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
                ['keyword' => 'RIRIS MEGASARI RAJAGUKGU', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
                ['keyword' => 'RINI RISNAWATI', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
                ['keyword' => 'SHERLY LENGGOGENY', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
                ['keyword' => 'KRISTINA UTAMI', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
                ['keyword' => 'ARINI RIANTI', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
                ['keyword' => 'gaji', 'match_type' => 'contains', 'priority' => 8, 'is_regex' => false],
                ['keyword' => 'payroll', 'match_type' => 'contains', 'priority' => 8, 'is_regex' => false],
            ],
            
            // POTONGAN Keywords
            'Potongan Koperasi' => [
                ['keyword' => 'POT KOP', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'potongan koperasi', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false],
            ],
            
            // BON KARYAWAN Keywords
            'Bon Sementara' => [
                ['keyword' => 'bon sementara', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'bon.*sementara', 'match_type' => 'regex', 'priority' => 9, 'is_regex' => true],
            ],
            
            // OUTLET Keywords
            'Apotek Bekasi' => [
                ['keyword' => 'apotek.*bekasi', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
                ['keyword' => 'bekasi.*apotek', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
            ],
            'Apotek Wisma Asri' => [
                ['keyword' => 'apotek.*wisma', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
                ['keyword' => 'wisma.*apotek', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
            ],
            'Apotek Kranggan' => [
                ['keyword' => 'kf kranggan', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false],
                ['keyword' => 'apotek.*kranggan', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true],
            ],
        ];
        
        $totalKeywords = 0;
        
        // Generate keywords for each sub-category
        foreach ($subCategories as $subCategory) {
            $subCategoryName = $subCategory->name;
            
            // Check if we have keywords for this sub-category
            if (isset($keywordMappings[$subCategoryName])) {
                foreach ($keywordMappings[$subCategoryName] as $keyword) {
                    $allKeywords[] = [
                        'uuid' => Str::uuid(),
                        'company_id' => $subCategory->company_id,
                        'sub_category_id' => $subCategory->id,
                        'keyword' => $keyword['keyword'],
                        'is_regex' => $keyword['is_regex'],
                        'case_sensitive' => false,
                        'match_type' => $keyword['match_type'],
                        'pattern_description' => "Pattern for matching {$subCategoryName} transactions",
                        'priority' => $keyword['priority'],
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now->copy()->subDays(rand(5, 120)),
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ];
                    $totalKeywords++;
                }
            } else {
                // Add a generic keyword for unmapped sub-categories
                $genericKeyword = strtolower(str_replace([' - ', ' '], ['_', '_'], $subCategoryName));
                $allKeywords[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $subCategory->company_id,
                    'sub_category_id' => $subCategory->id,
                    'keyword' => $genericKeyword,
                    'is_regex' => false,
                    'case_sensitive' => false,
                    'match_type' => 'contains',
                    'pattern_description' => "Generic pattern for {$subCategoryName}",
                    'priority' => 5,
                    'is_active' => true,
                    'match_count' => 0,
                    'last_matched_at' => null,
                    'created_at' => $now->copy()->subDays(rand(5, 120)),
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
                $totalKeywords++;
            }
        }

        if (!empty($allKeywords)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($allKeywords, 100);
            foreach ($chunks as $chunk) {
                DB::table('keywords')->insert($chunk);
            }
            
            $this->command->info('✅ Keywords seeded successfully!');
            $this->command->info("   Total keywords: " . count($allKeywords));
            
            // Display statistics
            $stats = DB::table('keywords')
                ->select('match_type', DB::raw('count(*) as count'))
                ->groupBy('match_type')
                ->get();
            
            $this->command->newLine();
            $this->command->info('📊 Match Type Distribution:');
            foreach ($stats as $stat) {
                $this->command->info("   {$stat->match_type}: {$stat->count} keywords");
            }
            
            // Show regex vs non-regex
            $regexStats = DB::table('keywords')
                ->select('is_regex', DB::raw('count(*) as count'))
                ->groupBy('is_regex')
                ->get();
            
            $this->command->newLine();
            $this->command->info('🔍 Pattern Types:');
            foreach ($regexStats as $stat) {
                $type = $stat->is_regex ? 'Regex patterns' : 'Simple patterns';
                $this->command->info("   {$type}: {$stat->count}");
            }
            
            // Show priority distribution
            $priorityStats = DB::table('keywords')
                ->select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->orderBy('priority', 'desc')
                ->get();
            
            $this->command->newLine();
            $this->command->info('🎯 Priority Distribution:');
            foreach ($priorityStats as $stat) {
                $bar = str_repeat('▓', min($stat->count / 5, 20));
                $this->command->info("   Priority {$stat->priority}: {$bar} ({$stat->count})");
            }
            
            // Top keywords by sub-category
            $topSubCategories = DB::table('sub_categories')
                ->join('keywords', 'sub_categories.id', '=', 'keywords.sub_category_id')
                ->select('sub_categories.name', DB::raw('count(keywords.id) as keyword_count'))
                ->groupBy('sub_categories.name')
                ->orderBy('keyword_count', 'desc')
                ->limit(5)
                ->get();
            
            $this->command->newLine();
            $this->command->info('🏆 Top 5 Sub-Categories by Keyword Count:');
            foreach ($topSubCategories as $idx => $subCat) {
                $this->command->info("   " . ($idx + 1) . ". {$subCat->name}: {$subCat->keyword_count} keywords");
            }
            
            $this->command->newLine();
            $this->command->info('💡 Tip: Keywords are ready for transaction matching!');
            $this->command->info('   - High priority (10) keywords are checked first');
            $this->command->info('   - Regex patterns allow flexible matching');
            $this->command->info('   - Match counts track keyword effectiveness');
        } else {
            $this->command->warn('⚠️  No keywords created. Check if sub-categories exist.');
        }
    }
}