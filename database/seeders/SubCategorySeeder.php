<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SubCategorySeeder extends Seeder
{
    private $now;

    /**
     * Run the database seeds.
     * 
     * FULL VERSION - Ultra Specific Sub-Categories
     * Berdasarkan analisis 26,357 transaksi bank real
     * 
     * Note: Seeder ini membutuhkan:
     * - Categories table sudah terisi (CategorySeeder)
     */
    public function run(): void
    {
        $this->command->info('📑 Seeding Sub-Categories (FULL VERSION)...');
        $this->command->newLine();
        
        $this->now = Carbon::now();

        // Get all categories with their slugs
        $categories = DB::table('categories')
            ->select('id', 'company_id', 'slug', 'name')
            ->orderBy('company_id')
            ->orderBy('sort_order')
            ->get();
        
        if ($categories->isEmpty()) {
            $this->command->error('❌ No categories found! Please run CategorySeeder first.');
            return;
        }

        $allSubCategories = [];
        $subCategoriesCreated = 0;
        
        // Get comprehensive sub-category mappings
        $subCategoryMappings = $this->getSubCategoryMappings();

        // Generate sub-categories for each category
        foreach ($categories as $category) {
            $this->command->info("   Processing category: {$category->name}");
            
            $categorySlug = $category->slug;
            
            // Check if we have sub-categories for this category
            if (!isset($subCategoryMappings[$categorySlug])) {
                // Add default sub-category
                $allSubCategories[] = $this->makeSubCategory(
                    $category->company_id,
                    $category->id,
                    [
                        'name' => $category->name . ' - Umum',
                        'description' => 'Sub kategori umum untuk ' . $category->name,
                        'priority' => 5,
                        'sort_order' => 1,
                    ]
                );
                $subCategoriesCreated++;
                continue;
            }
            
            // Add mapped sub-categories
            $sortOrder = 1;
            foreach ($subCategoryMappings[$categorySlug] as $subCategory) {
                $allSubCategories[] = $this->makeSubCategory(
                    $category->company_id,
                    $category->id,
                    array_merge($subCategory, ['sort_order' => $sortOrder++])
                );
                $subCategoriesCreated++;
            }
        }

        if (!empty($allSubCategories)) {
            // Insert in chunks
            $chunks = array_chunk($allSubCategories, 100);
            foreach ($chunks as $chunk) {
                DB::table('sub_categories')->insert($chunk);
            }
            
            $this->command->newLine();
            $this->command->info('✅ Sub-Categories seeded successfully!');
            $this->command->info("   Total sub-categories: {$subCategoriesCreated}");
            
            $this->displaySummary();
        } else {
            $this->command->warn('⚠️  No sub-categories created. Check if categories exist.');
        }
    }

    /**
     * Get comprehensive sub-category mappings
     * Berdasarkan data transaksi real
     */
    private function getSubCategoryMappings(): array
    {
        return [
            // ========================================
            // REVENUE - PENJUALAN OUTLET
            // ========================================
            'penjualan-outlet-reguler' => [
                ['name' => 'KF 0264 Narogong', 'description' => 'Penjualan outlet Narogong', 'priority' => 10],
                ['name' => 'KF 0330 Harapan Indah', 'description' => 'Penjualan outlet Harapan Indah', 'priority' => 10],
                ['name' => 'KF 0340 Cikarang', 'description' => 'Penjualan outlet Cikarang', 'priority' => 10],
                ['name' => 'KF 0347 Pekayon', 'description' => 'Penjualan outlet Pekayon', 'priority' => 10],
                ['name' => 'KF 0367 Jati Asih', 'description' => 'Penjualan outlet Jati Asih', 'priority' => 10],
                ['name' => 'KF 0390 Cakung Gede', 'description' => 'Penjualan outlet Cakung Gede', 'priority' => 10],
                ['name' => 'KF 0405 Daan', 'description' => 'Penjualan outlet Daan', 'priority' => 10],
                ['name' => 'KF 0406 Kalimalang', 'description' => 'Penjualan outlet Kalimalang', 'priority' => 10],
                ['name' => 'KF 0007 Cibitung', 'description' => 'Penjualan outlet Cibitung', 'priority' => 10],
                ['name' => 'KF 0456 Granwis', 'description' => 'Penjualan outlet Granwis', 'priority' => 10],
                ['name' => 'KF 0503 Taman Mini', 'description' => 'Penjualan outlet Taman Mini', 'priority' => 10],
                ['name' => 'KF 0586 Cileungsi', 'description' => 'Penjualan outlet Cileungsi', 'priority' => 10],
                ['name' => 'KF 0591 Zamrud', 'description' => 'Penjualan outlet Zamrud', 'priority' => 10],
                ['name' => 'KF 0624 CISC', 'description' => 'Penjualan outlet CISC', 'priority' => 10],
                ['name' => 'KF 0618 Kranji', 'description' => 'Penjualan outlet Kranji', 'priority' => 10],
                ['name' => 'KF 0810 Kintamani', 'description' => 'Penjualan outlet Kintamani', 'priority' => 10],
                ['name' => 'KF 0944 Parungkd', 'description' => 'Penjualan outlet Parungkd', 'priority' => 10],
                ['name' => 'KF Cakra Raya', 'description' => 'Penjualan outlet Cakra Raya', 'priority' => 9],
                ['name' => 'KF Kali Abang Bekasi', 'description' => 'Penjualan outlet Kali Abang', 'priority' => 9],
                ['name' => 'KF Jati Rahayu', 'description' => 'Penjualan outlet Jati Rahayu', 'priority' => 9],
                ['name' => 'KF Rawa Lumbu', 'description' => 'Penjualan outlet Rawa Lumbu', 'priority' => 9],
                ['name' => 'KF Sepat', 'description' => 'Penjualan outlet Sepat', 'priority' => 9],
                ['name' => 'KF Boulevard', 'description' => 'Penjualan outlet Boulevard', 'priority' => 9],
                ['name' => 'KF Summarecon', 'description' => 'Penjualan Apotek KF Summarecon', 'priority' => 9],
                ['name' => 'KF Kota Serang', 'description' => 'Penjualan outlet Kota Serang', 'priority' => 9],
            ],

            'penjualan-outlet-resep' => [
                ['name' => 'KF 264 Resep Suzi', 'description' => 'Penjualan outlet resep Suzi (Narogong)', 'priority' => 10],
                ['name' => 'KF 330 Resep HI', 'description' => 'Penjualan outlet resep Harapan Indah', 'priority' => 10],
                ['name' => 'KF 340 Resep KC', 'description' => 'Penjualan outlet resep Cikarang', 'priority' => 10],
                ['name' => 'KF 007 Resep CB', 'description' => 'Penjualan outlet resep Cibitung', 'priority' => 10],
                ['name' => 'KF Cakra Raya Resep', 'description' => 'Penjualan outlet resep Cakra Raya', 'priority' => 9],
            ],

            'penjualan-klinik' => [
                ['name' => 'Klinik KF Wisma Asri', 'description' => 'Penjualan Klinik Wisma Asri', 'priority' => 10],
                ['name' => 'Klinik Umum', 'description' => 'Penjualan klinik umum', 'priority' => 8],
            ],

            'penjualan-alkes' => [
                ['name' => 'Alat Kesehatan Medis', 'description' => 'Penjualan alat kesehatan medis', 'priority' => 9],
                ['name' => 'Alat Kesehatan Personal', 'description' => 'Penjualan alat kesehatan personal', 'priority' => 8],
                ['name' => 'Consumables Medis', 'description' => 'Penjualan consumables medis', 'priority' => 8],
            ],

            // ========================================
            // REVENUE - TRANSFER MASUK
            // ========================================
            'transfer-dari-distributor' => [
                ['name' => 'Transfer dari Kimia Farma Apotek', 'description' => 'Transfer masuk dari KF Apotek', 'priority' => 10],
                ['name' => 'Transfer dari Kimia Farma Trading', 'description' => 'Transfer dari KF Trading', 'priority' => 9],
                ['name' => 'Transfer dari PBF', 'description' => 'Transfer dari Pedagang Besar Farmasi', 'priority' => 9],
                ['name' => 'Transfer dari Distributor Obat', 'description' => 'Transfer dari distributor obat lainnya', 'priority' => 8],
            ],

            'transfer-dari-customer' => [
                ['name' => 'Transfer Customer Retail', 'description' => 'Transfer dari customer retail', 'priority' => 9],
                ['name' => 'Transfer Customer Corporate', 'description' => 'Transfer dari customer corporate', 'priority' => 9],
                ['name' => 'Transfer Customer Online', 'description' => 'Transfer dari pembelian online', 'priority' => 8],
            ],

            'transfer-internal' => [
                ['name' => 'Transfer Antar Outlet', 'description' => 'Transfer internal antar outlet', 'priority' => 10],
                ['name' => 'Transfer dari Pusat', 'description' => 'Transfer dari kantor pusat', 'priority' => 9],
                ['name' => 'Transfer Antar Cabang', 'description' => 'Transfer antar cabang regional', 'priority' => 9],
            ],

            // ========================================
            // REVENUE - SETOR TUNAI
            // ========================================
            'setor-tunai-outlet' => [
                ['name' => 'LIPH KF Narogong', 'description' => 'Setoran LIPH outlet Narogong', 'priority' => 10],
                ['name' => 'LIPH KF Harapan Indah', 'description' => 'Setoran LIPH outlet Harapan Indah', 'priority' => 10],
                ['name' => 'LIPH KF Cikarang', 'description' => 'Setoran LIPH outlet Cikarang', 'priority' => 10],
                ['name' => 'LIPH KF Pekayon', 'description' => 'Setoran LIPH outlet Pekayon', 'priority' => 10],
                ['name' => 'LIPH KF Jati Asih', 'description' => 'Setoran LIPH outlet Jati Asih', 'priority' => 10],
                ['name' => 'LIPH KF Kalimalang', 'description' => 'Setoran LIPH outlet Kalimalang', 'priority' => 10],
                ['name' => 'LIPH KF Cibitung', 'description' => 'Setoran LIPH outlet Cibitung', 'priority' => 10],
                ['name' => 'LIPH KF Granwis', 'description' => 'Setoran LIPH outlet Granwis', 'priority' => 10],
                ['name' => 'LIPH KF Zamrud', 'description' => 'Setoran LIPH outlet Zamrud', 'priority' => 10],
                ['name' => 'LIPH KF Kali Abang', 'description' => 'Setoran LIPH outlet Kali Abang', 'priority' => 10],
                ['name' => 'LIPH KF Summarecon', 'description' => 'Setoran LIPH outlet Summarecon', 'priority' => 10],
                ['name' => 'Setoran Tunai Outlet Lain', 'description' => 'Setoran tunai outlet lainnya', 'priority' => 8],
            ],

            'setor-tunai-klinik' => [
                ['name' => 'Setoran Klinik Wisma Asri', 'description' => 'Setoran tunai Klinik Wisma Asri', 'priority' => 10],
                ['name' => 'Setoran Klinik Lainnya', 'description' => 'Setoran klinik lainnya', 'priority' => 8],
            ],

            'cod-pembayaran' => [
                ['name' => 'COD Obat', 'description' => 'Pembayaran COD pembelian obat', 'priority' => 9],
                ['name' => 'COD Alkes', 'description' => 'Pembayaran COD alat kesehatan', 'priority' => 8],
                ['name' => 'COD Layanan', 'description' => 'Pembayaran COD layanan kesehatan', 'priority' => 8],
            ],

            // ========================================
            // REVENUE - QR CODE
            // ========================================
            'qris-payment' => [
                ['name' => 'QRIS Static KF Outlet', 'description' => 'Pembayaran QRIS static outlet', 'priority' => 10],
                ['name' => 'QRIS Dynamic', 'description' => 'Pembayaran QRIS dynamic', 'priority' => 9],
                ['name' => 'QRIS Merchant', 'description' => 'Pembayaran QRIS merchant', 'priority' => 8],
            ],

            'otopay-qr' => [
                ['name' => 'OTOPAY QR KF Summarecon', 'description' => 'OTOPAY QR outlet Summarecon', 'priority' => 10],
                ['name' => 'OTOPAY QR Outlet Lain', 'description' => 'OTOPAY QR outlet lainnya', 'priority' => 9],
            ],

            'merchant-qr' => [
                ['name' => 'Merchant QR BCA', 'description' => 'Pembayaran via Merchant QR BCA', 'priority' => 9],
                ['name' => 'Merchant QR Mandiri', 'description' => 'Pembayaran via Merchant QR Mandiri', 'priority' => 9],
                ['name' => 'Merchant QR Lainnya', 'description' => 'Merchant QR bank lainnya', 'priority' => 8],
            ],

            // ========================================
            // REVENUE - KLAIM ASURANSI
            // ========================================
            'klaim-asuransi-kesehatan' => [
                ['name' => 'Klaim Asuransi Sinar Mas', 'description' => 'Klaim dari Asuransi Sinar Mas', 'priority' => 10],
                ['name' => 'Klaim Hanwha Life Insurance', 'description' => 'Klaim dari Hanwha Life', 'priority' => 10],
                ['name' => 'Klaim Perta Life Insurance', 'description' => 'Klaim dari Perta Life', 'priority' => 10],
                ['name' => 'Klaim Asuransi Lainnya', 'description' => 'Klaim asuransi kesehatan lainnya', 'priority' => 8],
            ],

            'klaim-bpjs' => [
                ['name' => 'Klaim BPJS Kesehatan Rawat Jalan', 'description' => 'Klaim BPJS rawat jalan', 'priority' => 10],
                ['name' => 'Klaim BPJS Kesehatan Rawat Inap', 'description' => 'Klaim BPJS rawat inap', 'priority' => 10],
                ['name' => 'Klaim BPJS Obat', 'description' => 'Klaim BPJS obat-obatan', 'priority' => 9],
            ],

            // ========================================
            // OPERATIONAL EXPENSES - BOP
            // ========================================
            'bop-bulanan-outlet' => [
                ['name' => 'BOP MG3 Bekasi', 'description' => 'BOP bulanan MG3 Bekasi', 'priority' => 10],
                ['name' => 'BOP BO Bekasi', 'description' => 'BOP Branch Office Bekasi', 'priority' => 10],
                ['name' => 'BOP Outlet Januari', 'description' => 'BOP outlet bulan Januari', 'priority' => 9],
                ['name' => 'BOP Outlet Februari', 'description' => 'BOP outlet bulan Februari', 'priority' => 9],
                ['name' => 'BOP Outlet Maret', 'description' => 'BOP outlet bulan Maret', 'priority' => 9],
                ['name' => 'BOP Outlet April', 'description' => 'BOP outlet bulan April', 'priority' => 9],
                ['name' => 'BOP Outlet Mei', 'description' => 'BOP outlet bulan Mei', 'priority' => 9],
                ['name' => 'BOP Outlet Juni', 'description' => 'BOP outlet bulan Juni', 'priority' => 9],
                ['name' => 'BOP Outlet Juli', 'description' => 'BOP outlet bulan Juli', 'priority' => 9],
                ['name' => 'BOP Outlet Agustus', 'description' => 'BOP outlet bulan Agustus', 'priority' => 9],
                ['name' => 'BOP Outlet September', 'description' => 'BOP outlet bulan September', 'priority' => 9],
                ['name' => 'BOP Outlet Oktober', 'description' => 'BOP outlet bulan Oktober', 'priority' => 9],
                ['name' => 'BOP Outlet November', 'description' => 'BOP outlet bulan November', 'priority' => 9],
                ['name' => 'BOP Outlet Desember', 'description' => 'BOP outlet bulan Desember', 'priority' => 9],
            ],

            'bop-klinik' => [
                ['name' => 'BOP Klinik Wisma Asri', 'description' => 'BOP Klinik Wisma Asri', 'priority' => 10],
                ['name' => 'BOP Klinik Lainnya', 'description' => 'BOP klinik lainnya', 'priority' => 8],
            ],

            'rumah-tangga-outlet' => [
                ['name' => 'Rumat KF Wisma Asri', 'description' => 'Rumah tangga outlet Wisma Asri', 'priority' => 10],
                ['name' => 'Rumat KF Cikarang', 'description' => 'Rumah tangga outlet Cikarang', 'priority' => 9],
                ['name' => 'Rumat KF Narogong', 'description' => 'Rumah tangga outlet Narogong', 'priority' => 9],
                ['name' => 'Rumat KF Kalimalang', 'description' => 'Rumah tangga outlet Kalimalang', 'priority' => 9],
                ['name' => 'Rumat Outlet Lainnya', 'description' => 'Rumah tangga outlet lainnya', 'priority' => 8],
            ],

            // ========================================
            // OPERATIONAL - UTILITAS
            // ========================================
            'listrik-pln' => [
                ['name' => 'Listrik PLN Outlet Narogong', 'description' => 'Pembayaran listrik outlet Narogong', 'priority' => 9],
                ['name' => 'Listrik PLN Outlet Cikarang', 'description' => 'Pembayaran listrik outlet Cikarang', 'priority' => 9],
                ['name' => 'Listrik PLN Outlet Wisma Asri', 'description' => 'Pembayaran listrik Wisma Asri', 'priority' => 9],
                ['name' => 'Listrik PLN Kantor Pusat', 'description' => 'Pembayaran listrik kantor pusat', 'priority' => 10],
                ['name' => 'Listrik PLN Outlet Lainnya', 'description' => 'Pembayaran listrik outlet lainnya', 'priority' => 8],
            ],

            'air-pdam' => [
                ['name' => 'Air PDAM Outlet', 'description' => 'Pembayaran air PDAM outlet', 'priority' => 9],
                ['name' => 'Air PDAM Kantor Pusat', 'description' => 'Pembayaran air PDAM kantor', 'priority' => 9],
            ],

            'internet-telepon' => [
                ['name' => 'Internet Indihome', 'description' => 'Biaya internet Indihome', 'priority' => 9],
                ['name' => 'Internet Corporate', 'description' => 'Internet corporate dedicated', 'priority' => 10],
                ['name' => 'Telepon Kantor', 'description' => 'Biaya telepon kantor', 'priority' => 8],
            ],

            'ipl-gedung' => [
                ['name' => 'IPL KF Outlet', 'description' => 'IPL gedung outlet', 'priority' => 9],
                ['name' => 'IPL Kantor', 'description' => 'IPL gedung kantor', 'priority' => 9],
            ],

            // ========================================
            // OPERATIONAL - MAINTENANCE
            // ========================================
            'service-ac' => [
                ['name' => 'Service AC Outlet Cikarang', 'description' => 'Service AC outlet Cikarang', 'priority' => 9],
                ['name' => 'Service AC Outlet Wisma', 'description' => 'Service AC Wisma Asri', 'priority' => 9],
                ['name' => 'Service AC Outlet Lainnya', 'description' => 'Service AC outlet lainnya', 'priority' => 8],
                ['name' => 'Service AC Kantor Pusat', 'description' => 'Service AC kantor pusat', 'priority' => 9],
            ],

            'perbaikan-cctv' => [
                ['name' => 'CCTV Outlet Cibitung', 'description' => 'Instalasi/perbaikan CCTV Cibitung', 'priority' => 9],
                ['name' => 'CCTV Outlet Daan', 'description' => 'Instalasi/perbaikan CCTV Daan', 'priority' => 9],
                ['name' => 'CCTV Outlet Lainnya', 'description' => 'CCTV outlet lainnya', 'priority' => 8],
            ],

            'maintenance-peralatan' => [
                ['name' => 'Maintenance PC/Komputer', 'description' => 'Maintenance PC outlet', 'priority' => 9],
                ['name' => 'Maintenance Printer', 'description' => 'Maintenance printer outlet', 'priority' => 8],
                ['name' => 'Maintenance Peralatan Medis', 'description' => 'Maintenance peralatan medis', 'priority' => 10],
            ],

            'perbaikan-gedung' => [
                ['name' => 'Renovasi Outlet', 'description' => 'Renovasi dan perbaikan outlet', 'priority' => 9],
                ['name' => 'Cat & Pengecatan', 'description' => 'Pengecatan gedung', 'priority' => 7],
                ['name' => 'Perbaikan Atap', 'description' => 'Perbaikan atap gedung', 'priority' => 8],
            ],

            // ========================================
            // PURCHASING - OBAT & ALKES
            // ========================================
            'pembelian-obat-generic' => [
                ['name' => 'Obat Generic dari Kimia Farma', 'description' => 'Pembelian obat generic KF', 'priority' => 10],
                ['name' => 'Obat Generic dari PBF', 'description' => 'Pembelian obat generic PBF', 'priority' => 9],
                ['name' => 'Obat Generic Lainnya', 'description' => 'Obat generic distributor lain', 'priority' => 8],
            ],

            'pembelian-obat-paten' => [
                ['name' => 'Obat Paten Branded', 'description' => 'Pembelian obat paten branded', 'priority' => 10],
                ['name' => 'Obat Import', 'description' => 'Pembelian obat import', 'priority' => 9],
            ],

            'pembelian-alat-kesehatan' => [
                ['name' => 'Alat Medis', 'description' => 'Pembelian alat medis', 'priority' => 10],
                ['name' => 'Alat Kesehatan Personal', 'description' => 'Alat kesehatan personal', 'priority' => 8],
                ['name' => 'Consumables Medis', 'description' => 'Pembelian consumables medis', 'priority' => 9],
            ],

            'pembelian-obat-khusus' => [
                ['name' => 'Obat Cold Chain', 'description' => 'Pembelian obat cold chain', 'priority' => 10],
                ['name' => 'Obat Narkotika & Psikotropika', 'description' => 'Pembelian obat narkotika', 'priority' => 10],
                ['name' => 'Obat Onkologi', 'description' => 'Pembelian obat onkologi', 'priority' => 9],
            ],

            // ========================================
            // PURCHASING - INVENTARIS
            // ========================================
            'pembelian-furniture' => [
                ['name' => 'Meja & Kursi', 'description' => 'Pembelian meja dan kursi', 'priority' => 8],
                ['name' => 'Rak & Lemari', 'description' => 'Pembelian rak dan lemari', 'priority' => 8],
                ['name' => 'Furniture Display', 'description' => 'Furniture display outlet', 'priority' => 7],
            ],

            'pembelian-elektronik' => [
                ['name' => 'Komputer & Laptop', 'description' => 'Pembelian komputer dan laptop', 'priority' => 9],
                ['name' => 'Printer & Scanner', 'description' => 'Pembelian printer scanner', 'priority' => 8],
                ['name' => 'Perangkat Jaringan', 'description' => 'Router, switch, access point', 'priority' => 8],
            ],

            'pembelian-peralatan-medis' => [
                ['name' => 'Peralatan Diagnostik', 'description' => 'Peralatan diagnostik medis', 'priority' => 10],
                ['name' => 'Peralatan Penunjang', 'description' => 'Peralatan penunjang medis', 'priority' => 9],
            ],

            // ========================================
            // PURCHASING - SUPPLIES
            // ========================================
            'pembelian-atk' => [
                ['name' => 'ATK Kantor', 'description' => 'Pembelian ATK kantor', 'priority' => 8],
                ['name' => 'ATK Outlet', 'description' => 'Pembelian ATK outlet', 'priority' => 8],
            ],

            'pembelian-packaging' => [
                ['name' => 'Kantong Plastik', 'description' => 'Pembelian kantong plastik', 'priority' => 9],
                ['name' => 'Paper Bag', 'description' => 'Pembelian paper bag', 'priority' => 8],
                ['name' => 'Box Packaging', 'description' => 'Box packaging obat', 'priority' => 8],
            ],

            'pembelian-cleaning-supplies' => [
                ['name' => 'Pembersih & Disinfektan', 'description' => 'Pembersih dan disinfektan', 'priority' => 9],
                ['name' => 'Supplies Kebersihan', 'description' => 'Tissue, sapu, pel, dll', 'priority' => 8],
            ],

            // ========================================
            // PURCHASING - MARKETING
            // ========================================
            'sticker-spanduk' => [
                ['name' => 'Sticker KF SMC', 'description' => 'Sticker KF Summarecon', 'priority' => 9],
                ['name' => 'Spanduk Promo', 'description' => 'Spanduk promosi outlet', 'priority' => 8],
                ['name' => 'Banner Outlet', 'description' => 'Banner outlet', 'priority' => 8],
            ],

            'brosur-leaflet' => [
                ['name' => 'Brosur Produk', 'description' => 'Brosur produk obat', 'priority' => 8],
                ['name' => 'Leaflet Promo', 'description' => 'Leaflet promosi', 'priority' => 7],
            ],

            'merchandise' => [
                ['name' => 'Merchandise Promo', 'description' => 'Merchandise promosi brand', 'priority' => 7],
                ['name' => 'Giveaway Customer', 'description' => 'Giveaway untuk customer', 'priority' => 7],
            ],

            // ========================================
            // PAYROLL - GAJI
            // ========================================
            'gaji-tetap-bulanan' => [
                ['name' => 'Gaji Apoteker', 'description' => 'Gaji apoteker penanggung jawab', 'priority' => 10],
                ['name' => 'Gaji Asisten Apoteker', 'description' => 'Gaji asisten apoteker', 'priority' => 9],
                ['name' => 'Gaji Admin Outlet', 'description' => 'Gaji admin/kasir outlet', 'priority' => 9],
                ['name' => 'Gaji Manajemen', 'description' => 'Gaji level manajemen', 'priority' => 10],
                ['name' => 'Gaji Staff', 'description' => 'Gaji staff umum', 'priority' => 9],
            ],

            'gaji-parttime' => [
                ['name' => 'Gaji Part-time Outlet', 'description' => 'Gaji karyawan part-time outlet', 'priority' => 8],
                ['name' => 'Gaji Part-time Admin', 'description' => 'Gaji part-time admin', 'priority' => 8],
            ],

            'gaji-freelance' => [
                ['name' => 'Fee Freelancer IT', 'description' => 'Fee freelancer IT', 'priority' => 8],
                ['name' => 'Fee Freelancer Marketing', 'description' => 'Fee freelancer marketing', 'priority' => 7],
            ],

            // ========================================
            // PAYROLL - TUNJANGAN
            // ========================================
            'tunjangan-transport' => [
                ['name' => 'Tunjangan Transport Karyawan Tetap', 'description' => 'Transport karyawan tetap', 'priority' => 9],
                ['name' => 'Tunjangan Transport Part-time', 'description' => 'Transport part-time', 'priority' => 8],
            ],

            'tunjangan-makan' => [
                ['name' => 'Tunjangan Makan Harian', 'description' => 'Tunjangan makan harian', 'priority' => 9],
                ['name' => 'Tunjangan Makan Lembur', 'description' => 'Tunjangan makan lembur', 'priority' => 8],
            ],

            'tunjangan-kesehatan' => [
                ['name' => 'Tunjangan Kesehatan Karyawan', 'description' => 'Tunjangan kesehatan', 'priority' => 9],
                ['name' => 'Asuransi Kesehatan Karyawan', 'description' => 'Premi asuransi kesehatan', 'priority' => 9],
            ],

            'bonus-kinerja' => [
                ['name' => 'Bonus Target Penjualan', 'description' => 'Bonus achievement target', 'priority' => 9],
                ['name' => 'Bonus Kinerja Tahunan', 'description' => 'Bonus kinerja tahunan', 'priority' => 10],
            ],

            // ========================================
            // PAYROLL - THR & DINAS
            // ========================================
            'thr-karyawan' => [
                ['name' => 'THR Lebaran', 'description' => 'THR Idul Fitri', 'priority' => 10],
                ['name' => 'THR Natal', 'description' => 'THR Natal', 'priority' => 9],
            ],

            'dinas-lebaran-karyawan' => [
                ['name' => 'Dinas Lebaran Zamrud', 'description' => 'Dinas lebaran outlet Zamrud', 'priority' => 9],
                ['name' => 'Dinas Lebaran Pekayon', 'description' => 'Dinas lebaran outlet Pekayon', 'priority' => 9],
                ['name' => 'Dinas Lebaran Outlet Lainnya', 'description' => 'Dinas lebaran outlet lain', 'priority' => 8],
            ],

            // ========================================
            // PAYROLL - HONOR PROFESSIONAL
            // ========================================
            'jasa-praktik-dokter-spesialis' => [
                ['name' => 'Jasa Praktik Spesialis Kalimalang', 'description' => 'Jasa praktik spesialis KF Kalimalang', 'priority' => 10],
                ['name' => 'Jasa Praktik Spesialis Lainnya', 'description' => 'Jasa praktik spesialis outlet lain', 'priority' => 9],
            ],

            'jasa-praktik-dokter-umum' => [
                ['name' => 'Jasa Praktik Dokter Umum Klinik', 'description' => 'Jasa dokter umum klinik', 'priority' => 10],
            ],

            'fee-apoteker' => [
                ['name' => 'Fee Apoteker Konsultasi', 'description' => 'Fee konsultasi apoteker', 'priority' => 9],
            ],

            'fee-konsultan' => [
                ['name' => 'Fee Konsultan IT', 'description' => 'Fee konsultan IT', 'priority' => 8],
                ['name' => 'Fee Konsultan Bisnis', 'description' => 'Fee konsultan bisnis', 'priority' => 8],
            ],

            // ========================================
            // PAYROLL - REIMBURSE
            // ========================================
            'reimburse-perjalanan-dinas' => [
                ['name' => 'Reimburse Tiket Pesawat', 'description' => 'Penggantian tiket pesawat', 'priority' => 9],
                ['name' => 'Reimburse Hotel', 'description' => 'Penggantian hotel', 'priority' => 9],
                ['name' => 'Reimburse Transport Dinas', 'description' => 'Transport perjalanan dinas', 'priority' => 8],
            ],

            'reimburse-bensin' => [
                ['name' => 'Reimburse Bensin Karyawan', 'description' => 'Penggantian bensin karyawan', 'priority' => 8],
            ],

            'reimburse-parkir-tol' => [
                ['name' => 'Reimburse Parkir', 'description' => 'Penggantian parkir', 'priority' => 7],
                ['name' => 'Reimburse Tol', 'description' => 'Penggantian tol', 'priority' => 7],
            ],

            // ========================================
            // VENDOR - PEMBAYARAN OBAT
            // ========================================
            'pembayaran-kimia-farma' => [
                ['name' => 'Kimia Farma Apotek Pusat', 'description' => 'Pembayaran KF Apotek pusat', 'priority' => 10],
                ['name' => 'Kimia Farma Distribusi', 'description' => 'Pembayaran KF distribusi', 'priority' => 10],
                ['name' => 'Kimia Farma Trading', 'description' => 'Pembayaran KF trading', 'priority' => 9],
            ],

            'pembayaran-pbf' => [
                ['name' => 'PBF Utama', 'description' => 'Pembayaran PBF utama', 'priority' => 10],
                ['name' => 'PBF Sekunder', 'description' => 'Pembayaran PBF sekunder', 'priority' => 8],
            ],

            'pembayaran-distributor-alkes' => [
                ['name' => 'Distributor Alkes Utama', 'description' => 'Distributor alkes utama', 'priority' => 9],
                ['name' => 'Distributor Alkes Lainnya', 'description' => 'Distributor alkes lainnya', 'priority' => 8],
            ],

            // ========================================
            // VENDOR - SERVICE
            // ========================================
            'pembayaran-it-support' => [
                ['name' => 'IT Support Hardware', 'description' => 'Support hardware komputer', 'priority' => 9],
                ['name' => 'IT Support Software', 'description' => 'Support software & aplikasi', 'priority' => 9],
                ['name' => 'IT Support Network', 'description' => 'Support jaringan', 'priority' => 9],
            ],

            'pembayaran-cleaning-service' => [
                ['name' => 'Cleaning Service Outlet', 'description' => 'Jasa cleaning service outlet', 'priority' => 9],
                ['name' => 'Cleaning Service Kantor', 'description' => 'Jasa cleaning service kantor', 'priority' => 9],
            ],

            'pembayaran-security' => [
                ['name' => 'Security Outlet', 'description' => 'Jasa security outlet', 'priority' => 9],
                ['name' => 'Security Kantor', 'description' => 'Jasa security kantor', 'priority' => 9],
            ],

            'pembayaran-pest-control' => [
                ['name' => 'Pest Control Rutin', 'description' => 'Jasa pest control rutin', 'priority' => 8],
                ['name' => 'Pest Control Emergency', 'description' => 'Pest control emergency', 'priority' => 9],
            ],

            // ========================================
            // VENDOR - SUPPLIES
            // ========================================
            'pembayaran-percetakan' => [
                ['name' => 'Percetakan Primagraphia', 'description' => 'Primagraphia Digital PT', 'priority' => 9],
                ['name' => 'Percetakan Lainnya', 'description' => 'Vendor percetakan lainnya', 'priority' => 8],
            ],

            'pembayaran-supplier-atk' => [
                ['name' => 'Supplier ATK Utama', 'description' => 'Supplier ATK utama', 'priority' => 8],
            ],

            // ========================================
            // BANKING - TRANSFER FEE
            // ========================================
            'biaya-transfer-antarbank' => [
                ['name' => 'Transfer Fee BCA', 'description' => 'Biaya transfer via BCA', 'priority' => 9],
                ['name' => 'Transfer Fee BNI', 'description' => 'Biaya transfer via BNI', 'priority' => 9],
                ['name' => 'Transfer Fee Mandiri', 'description' => 'Biaya transfer via Mandiri', 'priority' => 9],
                ['name' => 'Transfer Fee Bank Lain', 'description' => 'Biaya transfer bank lainnya', 'priority' => 8],
            ],

            'biaya-admin-bank' => [
                ['name' => 'Admin Bank Bulanan', 'description' => 'Biaya admin bulanan', 'priority' => 9],
                ['name' => 'Biaya Materai', 'description' => 'Biaya materai digital', 'priority' => 7],
            ],

            'biaya-kliring' => [
                ['name' => 'Biaya Kliring Cek', 'description' => 'Biaya kliring cek', 'priority' => 8],
                ['name' => 'Biaya Kliring Giro', 'description' => 'Biaya kliring giro', 'priority' => 8],
            ],

            // ========================================
            // BANKING - TRANSFER INTERNAL
            // ========================================
            'transfer-internal-sesama-bank' => [
                ['name' => 'Transfer Internal BCA', 'description' => 'Transfer internal rekening BCA', 'priority' => 10],
                ['name' => 'Transfer Internal BNI', 'description' => 'Transfer internal rekening BNI', 'priority' => 10],
                ['name' => 'Transfer Internal Mandiri', 'description' => 'Transfer internal Mandiri', 'priority' => 10],
            ],

            'pemindahan-dana' => [
                ['name' => 'Pemindahan Antar Rekening', 'description' => 'Pemindahan dana antar rekening', 'priority' => 10],
            ],

            // ========================================
            // BANKING - TRANSFER CABANG
            // ========================================
            'transfer-ke-outlet' => [
                ['name' => 'Transfer ke Outlet Operasional', 'description' => 'Transfer dana operasional outlet', 'priority' => 10],
                ['name' => 'Transfer ke Outlet Emergency', 'description' => 'Transfer emergency outlet', 'priority' => 9],
            ],

            'transfer-dari-outlet' => [
                ['name' => 'Transfer dari Outlet Setoran', 'description' => 'Penerimaan setoran outlet', 'priority' => 10],
            ],

            'mcm-inhousetrf' => [
                ['name' => 'MCM InhouseTrf KE Karyawan', 'description' => 'Transfer MCM ke karyawan', 'priority' => 10],
                ['name' => 'MCM InhouseTrf DARI Vendor', 'description' => 'Penerimaan MCM dari vendor', 'priority' => 10],
                ['name' => 'MCM InhouseTrf Internal', 'description' => 'Transfer MCM internal', 'priority' => 9],
            ],

            // ========================================
            // INSURANCE - PREMI
            // ========================================
            'premi-asuransi-kesehatan' => [
                ['name' => 'Premi BPJS Kesehatan', 'description' => 'Premi BPJS kesehatan karyawan', 'priority' => 10],
                ['name' => 'Premi Asuransi Swasta', 'description' => 'Premi asuransi kesehatan swasta', 'priority' => 9],
            ],

            'premi-asuransi-jiwa' => [
                ['name' => 'Premi Asuransi Jiwa Karyawan', 'description' => 'Premi asuransi jiwa', 'priority' => 9],
            ],

            'premi-asuransi-properti' => [
                ['name' => 'Premi Asuransi Gedung', 'description' => 'Premi asuransi gedung/properti', 'priority' => 10],
                ['name' => 'Premi Asuransi Inventaris', 'description' => 'Premi asuransi inventaris', 'priority' => 9],
            ],

            // ========================================
            // INSURANCE - KLAIM MASUK
            // ========================================
            'klaim-dari-asuransi-kesehatan' => [
                ['name' => 'Klaim Sinar Mas', 'description' => 'Klaim dari Asuransi Sinar Mas', 'priority' => 10],
                ['name' => 'Klaim Hanwha', 'description' => 'Klaim dari Hanwha Life', 'priority' => 10],
                ['name' => 'Klaim Perta', 'description' => 'Klaim dari Perta Life', 'priority' => 10],
            ],

            'klaim-dari-bpjs' => [
                ['name' => 'Klaim BPJS Rawat Jalan', 'description' => 'Klaim BPJS rawat jalan', 'priority' => 10],
                ['name' => 'Klaim BPJS Rawat Inap', 'description' => 'Klaim BPJS rawat inap', 'priority' => 10],
            ],

            // ========================================
            // INSURANCE - KLAIM KELUAR
            // ========================================
            'pembayaran-klaim-ke-pasien' => [
                ['name' => 'Klaim Pasien Rawat Jalan', 'description' => 'Pembayaran klaim pasien rawat jalan', 'priority' => 9],
                ['name' => 'Klaim Pasien Rawat Inap', 'description' => 'Pembayaran klaim pasien rawat inap', 'priority' => 9],
            ],

            // ========================================
            // TAX - PPh
            // ========================================
            'pph-21' => [
                ['name' => 'PPh 21 Gaji Karyawan', 'description' => 'PPh 21 dari gaji karyawan', 'priority' => 10],
                ['name' => 'PPh 21 Bonus', 'description' => 'PPh 21 dari bonus', 'priority' => 9],
            ],

            'pph-23' => [
                ['name' => 'PPh 23 Jasa Profesional', 'description' => 'PPh 23 jasa profesional', 'priority' => 10],
                ['name' => 'PPh 23 Sewa', 'description' => 'PPh 23 sewa', 'priority' => 9],
            ],

            'pph-final' => [
                ['name' => 'PPh Final Usaha', 'description' => 'PPh final usaha', 'priority' => 10],
            ],

            // ========================================
            // TAX - PPN
            // ========================================
            'ppn-masukan' => [
                ['name' => 'PPN Masukan Pembelian', 'description' => 'PPN yang dibayar saat pembelian', 'priority' => 10],
            ],

            'ppn-keluaran' => [
                ['name' => 'PPN Keluaran Penjualan', 'description' => 'PPN yang dipungut dari penjualan', 'priority' => 10],
            ],

            // ========================================
            // TAX - LAINNYA
            // ========================================
            'pbb' => [
                ['name' => 'PBB Gedung Outlet', 'description' => 'PBB gedung outlet', 'priority' => 9],
                ['name' => 'PBB Kantor', 'description' => 'PBB gedung kantor', 'priority' => 9],
            ],

            'pajak-kendaraan' => [
                ['name' => 'Pajak Motor Operasional', 'description' => 'Pajak motor operasional', 'priority' => 8],
                ['name' => 'Pajak Mobil Operasional', 'description' => 'Pajak mobil operasional', 'priority' => 8],
            ],

            // ========================================
            // LOANS - BON KARYAWAN
            // ========================================
            'bon-sementara-karyawan' => [
                ['name' => 'Bon Sementara Kranggan', 'description' => 'Bon sementara outlet Kranggan', 'priority' => 9],
                ['name' => 'Bon Sementara Outlet Lain', 'description' => 'Bon sementara outlet lainnya', 'priority' => 8],
            ],

            'bon-perbaikan' => [
                ['name' => 'Bon Perbaikan Kali Abang', 'description' => 'Bon perbaikan outlet Kali Abang', 'priority' => 9],
                ['name' => 'Bon Perbaikan Outlet Lain', 'description' => 'Bon perbaikan outlet lainnya', 'priority' => 8],
            ],

            'bon-pembelian' => [
                ['name' => 'Bon Pembelian Operasional', 'description' => 'Bon pembelian kebutuhan operasional', 'priority' => 9],
            ],

            // ========================================
            // OUTLET OPERATIONS - SETORAN
            // ========================================
            'liph-outlet-reguler' => [
                ['name' => 'LIPH Narogong', 'description' => 'LIPH outlet Narogong', 'priority' => 10],
                ['name' => 'LIPH Harapan Indah', 'description' => 'LIPH outlet Harapan Indah', 'priority' => 10],
                ['name' => 'LIPH Cikarang', 'description' => 'LIPH outlet Cikarang', 'priority' => 10],
                ['name' => 'LIPH Pekayon', 'description' => 'LIPH outlet Pekayon', 'priority' => 10],
                ['name' => 'LIPH Kalimalang', 'description' => 'LIPH outlet Kalimalang', 'priority' => 10],
                ['name' => 'LIPH Kali Abang', 'description' => 'LIPH outlet Kali Abang', 'priority' => 10],
                ['name' => 'LIPH Outlet Lainnya', 'description' => 'LIPH outlet lainnya', 'priority' => 9],
            ],

            'liph-outlet-resep' => [
                ['name' => 'LIPH Resep Narogong', 'description' => 'LIPH outlet resep Narogong', 'priority' => 10],
                ['name' => 'LIPH Resep Cikarang', 'description' => 'LIPH outlet resep Cikarang', 'priority' => 10],
            ],

            'liph-klinik' => [
                ['name' => 'LIPH Klinik Wisma Asri', 'description' => 'LIPH Klinik Wisma Asri', 'priority' => 10],
            ],

            // ========================================
            // OUTLET OPERATIONS - RUMAT
            // ========================================
            'rumat-outlet-harian' => [
                ['name' => 'Rumat Wisma Asri', 'description' => 'Rumah tangga Wisma Asri', 'priority' => 10],
                ['name' => 'Rumat Cikarang', 'description' => 'Rumah tangga Cikarang', 'priority' => 9],
                ['name' => 'Rumat Outlet Lainnya', 'description' => 'Rumah tangga outlet lainnya', 'priority' => 8],
            ],

            'rumat-klinik' => [
                ['name' => 'Rumat Klinik Wisma', 'description' => 'Rumah tangga Klinik Wisma', 'priority' => 10],
            ],

            // ========================================
            // PAYMENT METHODS - QR
            // ========================================
            'qris-static' => [
                ['name' => 'QRIS Static Outlet', 'description' => 'Pembayaran QRIS static outlet', 'priority' => 10],
            ],

            'qris-dynamic' => [
                ['name' => 'QRIS Dynamic Transaction', 'description' => 'Pembayaran QRIS dynamic', 'priority' => 9],
            ],

            // ========================================
            // PAYMENT METHODS - EDC
            // ========================================
            'edc-debit' => [
                ['name' => 'EDC Debit BCA', 'description' => 'Pembayaran EDC debit BCA', 'priority' => 9],
                ['name' => 'EDC Debit Mandiri', 'description' => 'Pembayaran EDC debit Mandiri', 'priority' => 9],
                ['name' => 'EDC Debit BNI', 'description' => 'Pembayaran EDC debit BNI', 'priority' => 9],
            ],

            'edc-credit' => [
                ['name' => 'EDC Credit Visa', 'description' => 'Pembayaran kartu kredit Visa', 'priority' => 9],
                ['name' => 'EDC Credit Mastercard', 'description' => 'Pembayaran kartu kredit Mastercard', 'priority' => 9],
            ],

            // ========================================
            // PAYMENT METHODS - MOBILE BANKING
            // ========================================
            'atmb-transfer' => [
                ['name' => 'ATMB Transfer Credit', 'description' => 'Transfer via ATM Banking', 'priority' => 9],
                ['name' => 'Mobile Banking Transfer', 'description' => 'Transfer via mobile banking', 'priority' => 9],
            ],
        ];
    }

    /**
     * Make sub-category array
     */
    private function makeSubCategory(int $companyId, int $categoryId, array $data): array
    {
        return [
            'uuid' => Str::uuid(),
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'name' => $data['name'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'sort_order' => $data['sort_order'],
            'created_at' => $this->now->copy()->subDays(rand(10, 140)),
            'updated_at' => $this->now,
            'deleted_at' => null,
        ];
    }

    /**
     * Display summary
     */
    private function displaySummary(): void
    {
        $this->command->newLine();
        $this->command->info('📊 SUB-CATEGORIES SUMMARY:');
        $this->command->info('='.str_repeat('=', 79));

        // Top categories with most sub-categories
        $topCategories = DB::table('categories as c')
            ->join('sub_categories as sc', 'sc.category_id', '=', 'c.id')
            ->select('c.name', DB::raw('count(sc.id) as sub_count'))
            ->groupBy('c.id', 'c.name')
            ->orderBy('sub_count', 'desc')
            ->limit(15)
            ->get();

        $this->command->info("\n📈 Top 15 Categories by Sub-Categories:\n");
        foreach ($topCategories as $cat) {
            $bar = str_repeat('█', min($cat->sub_count, 30));
            $this->command->info(sprintf("   %-40s %s (%d)", $cat->name, $bar, $cat->sub_count));
        }

        // Priority distribution
        $priorityDist = DB::table('sub_categories')
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->orderBy('priority', 'desc')
            ->get();

        $this->command->newLine();
        $this->command->info('🎯 Priority Distribution:');
        foreach ($priorityDist as $dist) {
            $bar = str_repeat('█', min($dist->count / 10, 30));
            $this->command->info(sprintf("   Priority %d: %s (%d)", $dist->priority, $bar, $dist->count));
        }

        $total = DB::table('sub_categories')->count();
        $this->command->newLine();
        $this->command->info("Total Sub-Categories: {$total}");
    }
}