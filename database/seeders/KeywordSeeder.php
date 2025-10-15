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
     * ULTRA FULL VERSION - Comprehensive Keywords
     * Berdasarkan analisis 26,357 transaksi bank real
     * 
     * Note: Seeder ini membutuhkan:
     * - SubCategories table sudah terisi (SubCategorySeeder)
     */
    public function run(): void
    {
        $this->command->info('🔑 Seeding Keywords (ULTRA FULL VERSION)...');
        $this->command->newLine();
        
        $this->now = Carbon::now();

        // Get all sub-categories
        $subCategories = DB::table('sub_categories')
            ->select('id', 'company_id', 'name')
            ->orderBy('company_id')
            ->orderBy('priority', 'desc')
            ->get();
        
        if ($subCategories->isEmpty()) {
            $this->command->error('❌ No sub-categories found! Please run SubCategorySeeder first.');
            return;
        }

        $this->command->info("   Processing {$subCategories->count()} sub-categories...");

        $allKeywords = [];
        $keywordsCreated = 0;
        
        // Get comprehensive keyword mappings
        $keywordMappings = $this->getKeywordMappings();

        // Generate keywords for each sub-category
        foreach ($subCategories as $subCategory) {
            $subCategoryName = $subCategory->name;
            
            // Check if we have specific keywords for this sub-category
            if (isset($keywordMappings[$subCategoryName])) {
                foreach ($keywordMappings[$subCategoryName] as $keyword) {
                    $allKeywords[] = $this->makeKeyword(
                        $subCategory->company_id,
                        $subCategory->id,
                        $keyword
                    );
                    $keywordsCreated++;
                }
            } else {
                // Add generic fallback keyword
                $genericKeyword = strtolower(str_replace([' - ', ' ', '/'], ['_', '_', '_'], $subCategoryName));
                $allKeywords[] = $this->makeKeyword(
                    $subCategory->company_id,
                    $subCategory->id,
                    [
                        'keyword' => $genericKeyword,
                        'match_type' => 'contains',
                        'priority' => 5,
                        'is_regex' => false,
                        'pattern_description' => "Generic fallback for {$subCategoryName}",
                    ]
                );
                $keywordsCreated++;
            }
        }

        if (!empty($allKeywords)) {
            // Insert in chunks
            $chunks = array_chunk($allKeywords, 200);
            foreach ($chunks as $chunk) {
                DB::table('keywords')->insert($chunk);
            }
            
            $this->command->newLine();
            $this->command->info('✅ Keywords seeded successfully!');
            $this->command->info("   Total keywords: {$keywordsCreated}");
            
            $this->displayStatistics();
        } else {
            $this->command->warn('⚠️  No keywords created. Check if sub-categories exist.');
        }
    }

    /**
     * Get comprehensive keyword mappings
     * Berdasarkan pola transaksi real dari 26,357 data
     */
    private function getKeywordMappings(): array
    {
        return [
            // ========================================
            // KEYWORDS: PENJUALAN OUTLET (Priority 10)
            // ========================================
            'KF 0264 Narogong' => [
                ['keyword' => 'KF 0264', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number match'],
                ['keyword' => 'KF0264', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number no space'],
                ['keyword' => 'KF 264', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'KF number no zero'],
                ['keyword' => 'NAROGONG', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Location name'],
                ['keyword' => '0264', 'match_type' => 'contains', 'priority' => 8, 'is_regex' => false, 'pattern_description' => 'Number only'],
            ],

            'KF 0330 Harapan Indah' => [
                ['keyword' => 'KF 0330', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number match'],
                ['keyword' => 'KF0330', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number no space'],
                ['keyword' => 'HARAPAN INDAH', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Location name'],
                ['keyword' => 'HARAPAN INDA', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'Common typo'],
                ['keyword' => '0330', 'match_type' => 'contains', 'priority' => 8, 'is_regex' => false, 'pattern_description' => 'Number only'],
            ],

            'KF 0340 Cikarang' => [
                ['keyword' => 'KF 0340', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number match'],
                ['keyword' => 'KF0340', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number no space'],
                ['keyword' => 'KF CIKARANG', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF + location'],
                ['keyword' => 'CIKARANG', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'Location name'],
                ['keyword' => 'CKRG', 'match_type' => 'contains', 'priority' => 7, 'is_regex' => false, 'pattern_description' => 'Abbreviation'],
            ],

            'KF 0347 Pekayon' => [
                ['keyword' => 'KF 0347', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number match'],
                ['keyword' => 'PEKAYON', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Location name'],
                ['keyword' => 'KF PEKAYON', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF + location'],
            ],

            'KF 0367 Jati Asih' => [
                ['keyword' => 'KF 0367', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number match'],
                ['keyword' => 'JATI ASIH', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Location name'],
                ['keyword' => 'JATIASIH', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'No space variant'],
                ['keyword' => 'KF JATIASIH', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF + location'],
            ],

            'KF 0406 Kalimalang' => [
                ['keyword' => 'KF 0406', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number match'],
                ['keyword' => 'KALIMALANG', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Location name'],
                ['keyword' => 'KF KALIMALANG', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF + location'],
                ['keyword' => 'KRG', 'match_type' => 'contains', 'priority' => 6, 'is_regex' => false, 'pattern_description' => 'Abbreviation'],
            ],

            'KF 0007 Cibitung' => [
                ['keyword' => 'KF 0007', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number match'],
                ['keyword' => 'KF 007', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number no leading zero'],
                ['keyword' => 'CIBITUNG', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Location name'],
                ['keyword' => 'KF CIBITUNG', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF + location'],
            ],

            'KF 0456 Granwis' => [
                ['keyword' => 'KF 0456', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number match'],
                ['keyword' => 'GRANWIS', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Location name'],
                ['keyword' => 'KF GRANWIS', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF + location'],
                ['keyword' => 'GRANWISATA', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'Full location name'],
            ],

            'KF 0591 Zamrud' => [
                ['keyword' => 'KF 0591', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number match'],
                ['keyword' => 'ZAMRUD', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Location name'],
                ['keyword' => 'KF ZAMRUD', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF + location'],
            ],

            'KF 0624 CISC' => [
                ['keyword' => 'KF 0624', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number match'],
                ['keyword' => 'KF0624', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF number no space'],
                ['keyword' => 'CISC', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Location code'],
                ['keyword' => 'GGC', 'match_type' => 'contains', 'priority' => 8, 'is_regex' => false, 'pattern_description' => 'Alternative code'],
            ],

            'KF Wisma Asri' => [
                ['keyword' => 'WISMA ASRI', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Full location name'],
                ['keyword' => 'KF WISMA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF + location'],
                ['keyword' => 'KFWISMA', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'No space variant'],
                ['keyword' => 'WISMA', 'match_type' => 'contains', 'priority' => 7, 'is_regex' => false, 'pattern_description' => 'Short form'],
            ],

            'KF Kali Abang Bekasi' => [
                ['keyword' => 'KALI ABANG', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Location name'],
                ['keyword' => 'KALIABANG', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'No space variant'],
                ['keyword' => 'KF KALIABANG', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF + location'],
            ],

            'KF Summarecon' => [
                ['keyword' => 'SUMMARECON', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Location name'],
                ['keyword' => 'KF SUMMARECON', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF + location'],
                ['keyword' => 'SUMMA', 'match_type' => 'contains', 'priority' => 7, 'is_regex' => false, 'pattern_description' => 'Abbreviation'],
            ],

            // ========================================
            // KEYWORDS: OUTLET RESEP
            // ========================================
            'KF 264 Resep Suzi' => [
                ['keyword' => 'RESEP SUZI', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Resep identifier'],
                ['keyword' => 'KF.*264.*RESEP', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'KF + number + resep'],
                ['keyword' => 'RESEP.*264', 'match_type' => 'regex', 'priority' => 9, 'is_regex' => true, 'pattern_description' => 'Resep + number'],
            ],

            'KF 330 Resep HI' => [
                ['keyword' => 'RESEP HI', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Resep identifier'],
                ['keyword' => 'KF.*330.*RESEP', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'KF + number + resep'],
            ],

            'KF 340 Resep KC' => [
                ['keyword' => 'RESEP KC', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Resep identifier'],
                ['keyword' => 'KF.*340.*RESEP', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'KF + number + resep'],
            ],

            'KF 007 Resep CB' => [
                ['keyword' => 'RESEP CB', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Resep identifier'],
                ['keyword' => 'KF.*007.*RESEP', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'KF + number + resep'],
            ],

            // ========================================
            // KEYWORDS: KLINIK
            // ========================================
            'Klinik KF Wisma Asri' => [
                ['keyword' => 'KLINIK.*WISMA', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'Klinik + Wisma'],
                ['keyword' => 'KLINIK KF WISMA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Full klinik name'],
                ['keyword' => 'KLINIK WISMA ASRI', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Klinik location'],
            ],

            // ========================================
            // KEYWORDS: SETOR TUNAI / LIPH
            // ========================================
            'LIPH KF Narogong' => [
                ['keyword' => 'LIPH.*0264', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'LIPH + KF number'],
                ['keyword' => 'LIPH.*NAROGONG', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'LIPH + location'],
                ['keyword' => 'LIPH KF.*0264', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'LIPH KF + number'],
            ],

            'LIPH KF Harapan Indah' => [
                ['keyword' => 'LIPH.*0330', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'LIPH + KF number'],
                ['keyword' => 'LIPH.*HARAPAN', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'LIPH + location'],
            ],

            'LIPH KF Cikarang' => [
                ['keyword' => 'LIPH.*0340', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'LIPH + KF number'],
                ['keyword' => 'LIPH.*CIKARANG', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'LIPH + location'],
                ['keyword' => 'LIPH KF CIKARANG', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Full LIPH name'],
            ],

            'LIPH KF Kalimalang' => [
                ['keyword' => 'LIPH.*0406', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'LIPH + KF number'],
                ['keyword' => 'LIPH.*KALIMALANG', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'LIPH + location'],
            ],

            'LIPH KF Kali Abang' => [
                ['keyword' => 'LIPH.*KALIABANG', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'LIPH + location'],
                ['keyword' => 'LIPH KF KALIABANG', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Full LIPH name'],
            ],

            'LIPH KF Summarecon' => [
                ['keyword' => 'LIPH.*SUMMARECON', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'LIPH + location'],
                ['keyword' => 'LIPH KF SUMMARECON', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Full LIPH name'],
            ],

            'Setoran Klinik Wisma Asri' => [
                ['keyword' => 'KLINIK.*WISMA.*SETOR', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'Klinik setoran'],
                ['keyword' => 'SETOR.*KLINIK.*WISMA', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'Setoran klinik'],
            ],

            // ========================================
            // KEYWORDS: QR CODE PAYMENT
            // ========================================
            'QRIS Static Outlet' => [
                ['keyword' => 'QR.*STATIC', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'QR static pattern'],
                ['keyword' => 'QRIS.*STATIC', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'QRIS static'],
            ],

            'OTOPAY QR KF Summarecon' => [
                ['keyword' => 'OTOPAY.*QR.*SUMMARECON', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'OTOPAY Summarecon'],
                ['keyword' => 'OTOPAY QR', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'OTOPAY general'],
            ],

            // ========================================
            // KEYWORDS: KLAIM ASURANSI
            // ========================================
            'Klaim Asuransi Sinar Mas' => [
                ['keyword' => 'ASURANSI SINAR MAS', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Sinar Mas insurance'],
                ['keyword' => 'SINAR MAS', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'Company name'],
            ],

            'Klaim Hanwha Life Insurance' => [
                ['keyword' => 'HANWHA LIFE', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Hanwha insurance'],
                ['keyword' => 'HANWHA', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'Company name'],
            ],

            'Klaim Perta Life Insurance' => [
                ['keyword' => 'PERTA LIFE', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Perta insurance'],
                ['keyword' => 'PERTA', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'Company name'],
            ],

            // ========================================
            // KEYWORDS: BOP (Biaya Operasional)
            // ========================================
            'BOP MG3 Bekasi' => [
                ['keyword' => 'BOP.*MG3.*BEKASI', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'BOP MG3'],
                ['keyword' => 'MG3 BEKASI', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'MG3 location'],
            ],

            'BOP BO Bekasi' => [
                ['keyword' => 'BO.*BEKASI', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'BO Bekasi'],
                ['keyword' => 'TBHBO BEKASI', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'TBH BO'],
            ],

            'BOP Outlet Januari' => [
                ['keyword' => 'BOP.*JANUARI', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'BOP month January'],
            ],

            'BOP Outlet Februari' => [
                ['keyword' => 'BOP.*FEBRUARI', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'BOP month February'],
                ['keyword' => 'BOP FEBRUARI 2025', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'BOP Feb 2025'],
            ],

            'Rumat KF Wisma Asri' => [
                ['keyword' => 'RUMAT.*WISMA', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'Rumat Wisma'],
                ['keyword' => 'RUMATKFWISMA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Rumat no space'],
                ['keyword' => 'RUMAT KF WISMA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Full rumat name'],
            ],

            // ========================================
            // KEYWORDS: UTILITAS
            // ========================================
            'Listrik PLN Kantor Pusat' => [
                ['keyword' => 'PERUSAHAAN LISTRIK NEGARA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'PLN full name'],
                ['keyword' => 'LISTRIK NEGARA', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'PLN short'],
            ],

            'IPL KF Outlet' => [
                ['keyword' => 'IPL.*KF', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'IPL + KF'],
                ['keyword' => 'IPL', 'match_type' => 'contains', 'priority' => 7, 'is_regex' => false, 'pattern_description' => 'IPL general'],
            ],

            // ========================================
            // KEYWORDS: MAINTENANCE
            // ========================================
            'Service AC Outlet Cikarang' => [
                ['keyword' => 'SERVICE.*AC.*CIKARANG', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'AC service Cikarang'],
                ['keyword' => 'SERVICE AC2 KF CKRG', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'AC service pattern'],
            ],

            'CCTV Outlet Cibitung' => [
                ['keyword' => 'CCTV.*CIBITUNG', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'CCTV Cibitung'],
            ],

            'CCTV Outlet Daan' => [
                ['keyword' => 'CCTV.*POGE', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'CCTV Daan (POGE)'],
                ['keyword' => 'PENGADAAN CCTV', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'CCTV procurement'],
            ],

            'Maintenance PC/Komputer' => [
                ['keyword' => 'PC.*OUTLET', 'match_type' => 'regex', 'priority' => 9, 'is_regex' => true, 'pattern_description' => 'PC outlet'],
                ['keyword' => 'TBH.*PC', 'match_type' => 'regex', 'priority' => 9, 'is_regex' => true, 'pattern_description' => 'Additional PC'],
                ['keyword' => 'BS.*PC.*KF', 'match_type' => 'regex', 'priority' => 9, 'is_regex' => true, 'pattern_description' => 'PC maintenance'],
            ],

            // ========================================
            // KEYWORDS: PEMBELIAN MARKETING
            // ========================================
            'Sticker KF Summarecon' => [
                ['keyword' => 'STICKER.*KF.*SMC', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'Sticker SMC'],
                ['keyword' => 'STICKER KF', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'Sticker general'],
            ],

            'Percetakan Primagraphia' => [
                ['keyword' => 'PRIMAGRAPHIA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Primagraphia company'],
                ['keyword' => 'PRIMAGRAPHIA DIGITAL', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Full company name'],
            ],

            // ========================================
            // KEYWORDS: PAYROLL - THR & DINAS
            // ========================================
            'Dinas Lebaran Zamrud' => [
                ['keyword' => 'DINAS.*LEBRAN.*ZAMRUD', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'Dinas Zamrud'],
                ['keyword' => 'DINAS LEBARAN ZAMRUD', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Full dinas name'],
            ],

            'Dinas Lebaran Pekayon' => [
                ['keyword' => 'DINAS.*LEBRAN.*PEKAYON', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'Dinas Pekayon'],
                ['keyword' => 'DINAS LBRAN PEKAYON', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Dinas abbr'],
            ],

            'Jasa Praktik Spesialis Kalimalang' => [
                ['keyword' => 'JASA PRAKTEK SPESIALIS.*KALIMALANG', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'Doctor fee'],
                ['keyword' => 'JASA PRAKTEK SPESIALIS KF KALIMALANG', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Full doctor fee'],
            ],

            // ========================================
            // KEYWORDS: BON KARYAWAN
            // ========================================
            'Bon Sementara Kranggan' => [
                ['keyword' => 'BON.*SEMENTARA.*KRANGGAN', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'Bon Kranggan'],
                ['keyword' => 'KF KRANGGAN BON', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Kranggan bon'],
            ],

            'Bon Perbaikan Kali Abang' => [
                ['keyword' => 'BON.*PERBAIKAN.*KALIABANG', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'Bon repair'],
                ['keyword' => 'KALIABANG BON PERBAIKAN', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'Full bon name'],
            ],

            // ========================================
            // KEYWORDS: VENDOR
            // ========================================
            'Kimia Farma Apotek Pusat' => [
                ['keyword' => 'KIMIA FARMA APOTEK', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'KF Apotek'],
                ['keyword' => 'DARI KIMIA FARMA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'From KF'],
                ['keyword' => 'KE KIMIA FARMA', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'To KF'],
            ],

            // ========================================
            // KEYWORDS: TRANSFER & BANKING
            // ========================================
            'Transfer Fee BCA' => [
                ['keyword' => 'TRANSFER FEE.*BCA', 'match_type' => 'regex', 'priority' => 10, 'is_regex' => true, 'pattern_description' => 'BCA fee'],
                ['keyword' => 'TRANSFER FEE', 'match_type' => 'contains', 'priority' => 8, 'is_regex' => false, 'pattern_description' => 'General transfer fee'],
            ],

            'MCM InhouseTrf KE Karyawan' => [
                ['keyword' => 'MCM INHOUSETRF KE', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'MCM to employee'],
                ['keyword' => 'INHOUSETRF KE', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'Inhouse to'],
            ],

            'MCM InhouseTrf DARI Vendor' => [
                ['keyword' => 'MCM INHOUSETRF DARI', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'MCM from vendor'],
                ['keyword' => 'INHOUSETRF DARI', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'Inhouse from'],
            ],

            'ATMB Transfer Credit' => [
                ['keyword' => 'ATMB TRF', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'ATM Banking transfer'],
                ['keyword' => 'ATMB TRF CREDT', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'ATM transfer credit'],
            ],

            // ========================================
            // KEYWORDS: KODE BANK
            // ========================================
            'Transfer dari Kimia Farma Apotek' => [
                ['keyword' => 'KIMIA FARMA APOTEK', 'match_type' => 'contains', 'priority' => 10, 'is_regex' => false, 'pattern_description' => 'From KF Apotek'],
                ['keyword' => 'IRKF BEKASI', 'match_type' => 'contains', 'priority' => 9, 'is_regex' => false, 'pattern_description' => 'IR KF Bekasi'],
            ],
        ];
    }

    /**
     * Make keyword array
     */
    private function makeKeyword(int $companyId, int $subCategoryId, array $data): array
    {
        return [
            'uuid' => Str::uuid(),
            'company_id' => $companyId,
            'sub_category_id' => $subCategoryId,
            'keyword' => $data['keyword'],
            'is_regex' => $data['is_regex'],
            'case_sensitive' => false,
            'match_type' => $data['match_type'],
            'pattern_description' => $data['pattern_description'],
            'priority' => $data['priority'],
            'is_active' => true,
            'match_count' => 0,
            'last_matched_at' => null,
            'created_at' => $this->now->copy()->subDays(rand(5, 120)),
            'updated_at' => $this->now,
            'deleted_at' => null,
        ];
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
            $bar = str_repeat('█', min($type->count / 10, 30));
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
            $percent = round(($stat->count / array_sum(array_column($regexStats->toArray(), 'count'))) * 100, 1);
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
            $bar = str_repeat('█', min($prio->count / 10, 30));
            $this->command->info(sprintf("   Priority %d: %s (%d)", $prio->priority, $bar, $prio->count));
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
            $this->command->info(sprintf("   %2d. %-50s %d keywords", $idx + 1, $sub->name, $sub->keyword_count));
        }

        $total = DB::table('keywords')->count();
        $this->command->newLine();
        $this->command->info("Total Keywords: {$total}");
        $this->command->newLine();
        $this->command->info('💡 Keywords are optimized for high-accuracy transaction matching!');
    }
}