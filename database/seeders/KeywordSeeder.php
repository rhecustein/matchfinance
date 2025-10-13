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
     * - Companies table sudah terisi (CompanySeeder)
     * - Types table sudah terisi (TypeSeeder)
     * - Categories table sudah terisi (CategorySeeder)
     * - SubCategories table sudah terisi (SubCategorySeeder)
     */
    public function run(): void
    {
        $this->command->info('🔑 Seeding Keywords...');
        
        $now = Carbon::now();
        
        // Ambil semua companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('⚠️  No companies found! Please run CompanySeeder first.');
            return;
        }

        $allKeywords = [];
        $stats = [];

        // ============================================
        // GENERATE KEYWORDS PER COMPANY
        // ============================================
        foreach ($companies as $company) {
            $this->command->info("   Processing: {$company->name}");
            
            // Ambil categories untuk company ini
            $categories = DB::table('categories')
                ->where('company_id', $company->id)
                ->get();
            
            if ($categories->isEmpty()) {
                $this->command->warn("   ⚠️  No categories found for {$company->name}");
                continue;
            }
            
            $categoryIds = $categories->pluck('id')->toArray();
            
            // Ambil sub_categories dari categories company ini
            $subCategories = DB::table('sub_categories')
                ->whereIn('category_id', $categoryIds)
                ->get()
                ->keyBy('name');
            
            if ($subCategories->isEmpty()) {
                $this->command->warn("   ⚠️  No sub categories found for {$company->name}");
                continue;
            }

            $companyKeywords = $this->generateKeywordsForCompany($company, $subCategories, $now);
            $allKeywords = array_merge($allKeywords, $companyKeywords);
            
            $stats[$company->name] = count($companyKeywords);
        }

        // ============================================
        // INSERT KEYWORDS
        // ============================================
        if (!empty($allKeywords)) {
            // Insert in chunks untuk menghindari query terlalu besar
            $chunks = array_chunk($allKeywords, 100);
            foreach ($chunks as $chunk) {
                DB::table('keywords')->insert($chunk);
            }
            
            $totalKeywords = count($allKeywords);
            $this->command->newLine();
            $this->command->info("✅ {$totalKeywords} Keywords seeded successfully!");
            $this->command->newLine();
            
            // Display stats per company
            $this->command->info('📊 Keywords per Company:');
            foreach ($stats as $companyName => $count) {
                $this->command->info("   • {$companyName}: {$count} keywords");
            }
        } else {
            $this->command->warn('⚠️  No keywords created. Check if sub_categories exist.');
        }
    }

    /**
     * Generate keywords untuk satu company
     */
    private function generateKeywordsForCompany($company, $subCategories, $now): array
    {
        $keywords = [];
        $companySlug = $company->slug;

        // ============================================
        // KEYWORDS BERDASARKAN COMPANY
        // ============================================
        
        switch ($companySlug) {
            case 'kimia-farma-apotek':
                $keywords = $this->getKimiaFarmaApotekKeywords($company->id, $subCategories, $now);
                break;
                
            case 'kimia-farma-diagnostika':
                $keywords = $this->getKimiaFarmaDiagnostikaKeywords($company->id, $subCategories, $now);
                break;
                
            case 'albahjah':
                $keywords = $this->getAlbahjahKeywords($company->id, $subCategories, $now);
                break;
                
            default:
                // Default keywords untuk company lain
                $keywords = $this->getDefaultKeywords($company->id, $subCategories, $now);
                break;
        }

        return $keywords;
    }

    /**
     * Keywords untuk PT Kimia Farma Apotek
     */
    private function getKimiaFarmaApotekKeywords($companyId, $subCategories, $now): array
    {
        $keywords = [];

        // APOTEK - Kimia Farma
        if (isset($subCategories['Kimia Farma'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Kimia Farma']->id, 'APOTEK KIMIA FARMA', 10, 'contains', false, false, 'Transaksi di Apotek Kimia Farma', 234, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Kimia Farma']->id, 'KIMIA FARMA QR', 9, 'contains', false, false, 'Pembayaran QR Kimia Farma', 145, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Kimia Farma']->id, 'KF-', 8, 'starts_with', false, false, 'Kode outlet Kimia Farma', 178, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Kimia Farma']->id, '/KF\d{4}/', 9, 'regex', true, false, 'Pattern KF + 4 digit angka', 89, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Kimia Farma']->id, 'KLINIK KIMIA FARMA', 8, 'contains', false, false, 'Klinik Kimia Farma', 67, $now);
        }

        // APOTEK - Guardian
        if (isset($subCategories['Guardian'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Guardian']->id, 'GUARDIAN', 10, 'contains', false, false, 'Pembelian di Guardian', 167, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Guardian']->id, 'GUARDIAN HEALTH', 9, 'contains', false, false, 'Guardian Health & Beauty', 89, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Guardian']->id, 'GRD', 7, 'starts_with', false, false, 'Kode Guardian', 45, $now);
        }

        // APOTEK - Century
        if (isset($subCategories['Century Healthcare'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Century Healthcare']->id, 'CENTURY HEALTHCARE', 10, 'contains', false, false, 'Belanja di Century', 134, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Century Healthcare']->id, 'CENTURY', 9, 'contains', false, false, 'Century outlet', 198, $now);
        }

        // MINIMARKET - Indomaret
        if (isset($subCategories['Indomaret'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Indomaret']->id, 'INDOMARET', 10, 'contains', false, false, 'Belanja di Indomaret', 456, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Indomaret']->id, 'INDO MARET', 9, 'contains', false, false, 'Indomaret dengan spasi', 78, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Indomaret']->id, 'IDM', 8, 'starts_with', false, false, 'Kode Indomaret', 123, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Indomaret']->id, '/INDOMARET\s+\d+/', 8, 'regex', true, false, 'Indomaret + nomor cabang', 167, $now);
        }

        // MINIMARKET - Alfamart
        if (isset($subCategories['Alfamart'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Alfamart']->id, 'ALFAMART', 10, 'contains', false, false, 'Belanja di Alfamart', 389, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Alfamart']->id, 'ALFA MART', 9, 'contains', false, false, 'Alfamart dengan spasi', 134, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Alfamart']->id, 'ALF', 7, 'starts_with', false, false, 'Kode Alfamart', 89, $now);
        }

        // MINIMARKET - Alfamidi
        if (isset($subCategories['Alfamidi'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Alfamidi']->id, 'ALFAMIDI', 10, 'contains', false, false, 'Belanja di Alfamidi', 145, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Alfamidi']->id, 'ALFA MIDI', 8, 'contains', false, false, 'Alfamidi dengan spasi', 56, $now);
        }

        // E-WALLET - QRIS
        if (isset($subCategories['QRIS'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['QRIS']->id, 'QRIS', 10, 'contains', false, false, 'Pembayaran QRIS', 678, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['QRIS']->id, 'QR CODE', 8, 'contains', false, false, 'Pembayaran QR', 345, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['QRIS']->id, '/QR\s*PAYMENT/', 7, 'regex', true, false, 'QR Payment pattern', 123, $now);
        }

        // E-WALLET - GoPay
        if (isset($subCategories['GoPay'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['GoPay']->id, 'GOPAY', 10, 'contains', false, false, 'Transfer GoPay', 556, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['GoPay']->id, 'GO-PAY', 9, 'contains', false, false, 'GoPay dengan strip', 234, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['GoPay']->id, 'GOJEK', 8, 'contains', false, false, 'Transaksi Gojek', 189, $now);
        }

        // E-WALLET - OVO
        if (isset($subCategories['OVO'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['OVO']->id, 'OVO', 10, 'contains', false, false, 'Pembayaran OVO', 489, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['OVO']->id, '/^OVO\s/', 9, 'regex', true, false, 'OVO di awal kalimat', 167, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['OVO']->id, '/OVO\s*-/', 8, 'regex', true, false, 'OVO dengan strip', 123, $now);
        }

        // E-WALLET - DANA
        if (isset($subCategories['Dana'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Dana']->id, 'DANA', 10, 'contains', false, false, 'Transfer DANA', 523, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Dana']->id, '/DANA\s*-\s*\d+/', 8, 'regex', true, false, 'DANA dengan referensi', 156, $now);
        }

        // E-WALLET - ShopeePay
        if (isset($subCategories['ShopeePay'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['ShopeePay']->id, 'SHOPEEPAY', 10, 'contains', false, false, 'Pembayaran ShopeePay', 378, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['ShopeePay']->id, 'SHOPEE PAY', 9, 'contains', false, false, 'ShopeePay dengan spasi', 145, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['ShopeePay']->id, 'SPay', 7, 'contains', false, false, 'Singkatan ShopeePay', 67, $now);
        }

        // E-COMMERCE - Tokopedia
        if (isset($subCategories['Tokopedia'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Tokopedia']->id, 'TOKOPEDIA', 10, 'contains', false, false, 'Belanja di Tokopedia', 267, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Tokopedia']->id, 'TOKPED', 8, 'contains', false, false, 'Singkatan Tokopedia', 89, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Tokopedia']->id, '/TOPED/', 7, 'regex', true, false, 'Pattern Tokped', 45, $now);
        }

        // E-COMMERCE - Shopee
        if (isset($subCategories['Shopee'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Shopee']->id, 'SHOPEE', 10, 'contains', false, false, 'Belanja di Shopee', 345, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Shopee']->id, '/SHOPEE\s*ID/', 8, 'regex', true, false, 'Shopee Indonesia', 123, $now);
        }

        // TRANSPORTASI - Pertamina
        if (isset($subCategories['Pertamina'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Pertamina']->id, 'PERTAMINA', 10, 'contains', false, false, 'Isi BBM Pertamina', 456, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Pertamina']->id, 'SPBU PERTAMINA', 9, 'contains', false, false, 'SPBU Pertamina', 234, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Pertamina']->id, '/SPBU\s*\d+/', 8, 'regex', true, false, 'SPBU dengan nomor', 167, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Pertamina']->id, 'PERTALITE', 7, 'contains', false, false, 'Pembelian Pertalite', 89, $now);
        }

        // PEMBAYARAN - PLN Pascabayar
        if (isset($subCategories['PLN Pascabayar'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['PLN Pascabayar']->id, 'PLN PASCABAYAR', 10, 'contains', false, false, 'Bayar listrik pascabayar', 67, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['PLN Pascabayar']->id, 'TAGIHAN PLN', 9, 'contains', false, false, 'Tagihan listrik', 45, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['PLN Pascabayar']->id, '/PLN\s*POSTPAID/', 8, 'regex', true, false, 'PLN postpaid', 23, $now);
        }

        // PEMBAYARAN - Token PLN
        if (isset($subCategories['Token PLN'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Token PLN']->id, 'TOKEN PLN', 10, 'contains', false, false, 'Beli token listrik', 123, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Token PLN']->id, 'TOKEN LISTRIK', 9, 'contains', false, false, 'Token listrik prabayar', 89, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Token PLN']->id, '/TOKEN\s*\d{20}/', 8, 'regex', true, false, 'Token dengan 20 digit', 56, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Token PLN']->id, 'PLN PREPAID', 7, 'contains', false, false, 'PLN prabayar', 34, $now);
        }

        // PEMBAYARAN - Pulsa
        if (isset($subCategories['Pulsa'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Pulsa']->id, 'PULSA', 10, 'contains', false, false, 'Pembelian pulsa', 178, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Pulsa']->id, '/PULSA\s*(TELKOMSEL|XL|INDOSAT|TRI)/', 9, 'regex', true, false, 'Pulsa provider', 123, $now);
        }

        // RESTORAN - Fast Food
        if (isset($subCategories['Fast Food'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Fast Food']->id, 'MCDONALD', 9, 'contains', false, false, 'McDonald\'s', 89, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Fast Food']->id, 'KFC', 9, 'contains', false, false, 'Kentucky Fried Chicken', 134, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Fast Food']->id, 'BURGER KING', 8, 'contains', false, false, 'Burger King', 45, $now);
        }

        // RESTORAN - Cafe
        if (isset($subCategories['Cafe & Coffee Shop'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Cafe & Coffee Shop']->id, 'STARBUCKS', 9, 'contains', false, false, 'Starbucks Coffee', 156, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Cafe & Coffee Shop']->id, 'KOPI KENANGAN', 9, 'contains', false, false, 'Kopi Kenangan', 123, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Cafe & Coffee Shop']->id, 'JANJI JIWA', 8, 'contains', false, false, 'Janji Jiwa', 89, $now);
        }

        return $keywords;
    }

    /**
     * Keywords untuk PT Kimia Farma Diagnostika
     */
    private function getKimiaFarmaDiagnostikaKeywords($companyId, $subCategories, $now): array
    {
        $keywords = [];

        // Focus pada operasional lab
        if (isset($subCategories['Kimia Farma'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Kimia Farma']->id, 'KIMIA FARMA DIAGNOSTIK', 10, 'contains', false, false, 'Lab KF Diagnostik', 89, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Kimia Farma']->id, 'KLINIK KIMIA FARMA', 9, 'contains', false, false, 'Klinik Kimia Farma', 67, $now);
        }

        if (isset($subCategories['Indomaret'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Indomaret']->id, 'INDOMARET', 9, 'contains', false, false, 'Belanja operasional', 167, $now);
        }

        if (isset($subCategories['QRIS'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['QRIS']->id, 'QRIS', 10, 'contains', false, false, 'Pembayaran QRIS', 289, $now);
        }

        if (isset($subCategories['Pertamina'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Pertamina']->id, 'PERTAMINA', 9, 'contains', false, false, 'BBM kendaraan operasional', 198, $now);
        }

        if (isset($subCategories['PLN Pascabayar'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['PLN Pascabayar']->id, 'PLN', 9, 'contains', false, false, 'Tagihan listrik lab', 34, $now);
        }

        return $keywords;
    }

    /**
     * Keywords untuk ALBAHJAH (Trial User)
     */
    private function getAlbahjahKeywords($companyId, $subCategories, $now): array
    {
        $keywords = [];

        // Basic keywords untuk trial user
        if (isset($subCategories['Indomaret'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Indomaret']->id, 'INDOMARET', 10, 'contains', false, false, 'Belanja bulanan', 56, $now);
        }

        if (isset($subCategories['Alfamart'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Alfamart']->id, 'ALFAMART', 10, 'contains', false, false, 'Belanja harian', 78, $now);
        }

        if (isset($subCategories['QRIS'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['QRIS']->id, 'QRIS', 9, 'contains', false, false, 'Pembayaran digital', 123, $now);
        }

        if (isset($subCategories['GoPay'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['GoPay']->id, 'GOPAY', 9, 'contains', false, false, 'Transfer GoPay', 45, $now);
        }

        if (isset($subCategories['Dana'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Dana']->id, 'DANA', 9, 'contains', false, false, 'Transfer DANA', 67, $now);
        }

        if (isset($subCategories['Tokopedia'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Tokopedia']->id, 'TOKOPEDIA', 9, 'contains', false, false, 'Belanja online', 34, $now);
        }

        if (isset($subCategories['Shopee'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Shopee']->id, 'SHOPEE', 9, 'contains', false, false, 'Belanja marketplace', 45, $now);
        }

        if (isset($subCategories['Pertamina'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Pertamina']->id, 'PERTAMINA', 9, 'contains', false, false, 'BBM kendaraan', 56, $now);
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Pertamina']->id, 'SPBU', 7, 'contains', false, false, 'Isi bensin', 34, $now);
        }

        if (isset($subCategories['Token PLN'])) {
            $keywords[] = $this->makeKeyword($companyId, $subCategories['Token PLN']->id, 'TOKEN PLN', 9, 'contains', false, false, 'Token listrik rumah', 23, $now);
        }

        return $keywords;
    }

    /**
     * Default keywords untuk company lain
     */
    private function getDefaultKeywords($companyId, $subCategories, $now): array
    {
        $keywords = [];

        // Add basic keywords untuk company baru
        $basicKeywords = [
            'QRIS' => ['QRIS', 10, 'Pembayaran QRIS'],
            'Indomaret' => ['INDOMARET', 9, 'Belanja Indomaret'],
            'Alfamart' => ['ALFAMART', 9, 'Belanja Alfamart'],
            'GoPay' => ['GOPAY', 8, 'Transfer GoPay'],
            'Dana' => ['DANA', 8, 'Transfer DANA'],
        ];

        foreach ($basicKeywords as $subCatName => $data) {
            if (isset($subCategories[$subCatName])) {
                $keywords[] = $this->makeKeyword(
                    $companyId,
                    $subCategories[$subCatName]->id,
                    $data[0],
                    $data[1],
                    'contains',
                    false,
                    false,
                    $data[2],
                    0,
                    $now
                );
            }
        }

        return $keywords;
    }

    /**
     * Helper untuk membuat keyword array
     */
    private function makeKeyword(
        int $companyId,
        int $subCategoryId,
        string $keyword,
        int $priority = 5,
        string $matchType = 'contains',
        bool $isRegex = false,
        bool $caseSensitive = false,
        ?string $description = null,
        int $matchCount = 0,
        Carbon $now
    ): array {
        $lastMatched = $matchCount > 0 ? $now->copy()->subDays(rand(1, 60)) : null;

        return [
            'uuid' => Str::uuid(),
            'company_id' => $companyId,
            'sub_category_id' => $subCategoryId,
            'keyword' => $keyword,
            'is_regex' => $isRegex,
            'case_sensitive' => $caseSensitive,
            'match_type' => $matchType,
            'pattern_description' => $description,
            'priority' => $priority,
            'is_active' => true,
            'match_count' => $matchCount,
            'last_matched_at' => $lastMatched,
            'created_at' => $now->copy()->subDays(rand(30, 90)),
            'updated_at' => $now,
        ];
    }
}