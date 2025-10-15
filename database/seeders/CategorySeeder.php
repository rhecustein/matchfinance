<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    private $now;

    /**
     * Run the database seeds.
     * 
     * FULL VERSION - Comprehensive Categories
     * Berdasarkan analisis 26,357 transaksi bank real
     * 
     * Note: Seeder ini membutuhkan:
     * - Types table sudah terisi (TypeSeeder)
     */
    public function run(): void
    {
        $this->command->info('📂 Seeding Categories (FULL VERSION)...');
        $this->command->newLine();
        
        $this->now = Carbon::now();

        // Get all types grouped by company
        $types = DB::table('types')
            ->select('id', 'company_id', 'name', 'sort_order')
            ->orderBy('company_id')
            ->orderBy('sort_order')
            ->get();
        
        if ($types->isEmpty()) {
            $this->command->error('❌ No types found! Please run TypeSeeder first.');
            return;
        }

        $allCategories = [];
        $categoriesCreated = 0;
        
        // Get comprehensive category mappings
        $categoryMappings = $this->getCategoryMappings();

        // Generate categories for each type
        foreach ($types as $type) {
            $this->command->info("   Processing type: {$type->name}");
            
            $typeName = $type->name;
            
            // Get categories for this type
            if (!isset($categoryMappings[$typeName])) {
                // Add default category for unmapped types
                $allCategories[] = $this->makeCategory(
                    $type->company_id,
                    $type->id,
                    [
                        'slug' => Str::slug($typeName) . '-general',
                        'name' => $typeName . ' - Umum',
                        'description' => 'Kategori umum untuk ' . $typeName,
                        'color' => '#6B7280',
                        'sort_order' => 1,
                    ]
                );
                $categoriesCreated++;
                continue;
            }
            
            // Add mapped categories
            foreach ($categoryMappings[$typeName] as $category) {
                $allCategories[] = $this->makeCategory(
                    $type->company_id,
                    $type->id,
                    $category
                );
                $categoriesCreated++;
            }
        }

        if (!empty($allCategories)) {
            // Insert in chunks
            $chunks = array_chunk($allCategories, 100);
            foreach ($chunks as $chunk) {
                DB::table('categories')->insert($chunk);
            }
            
            $this->command->newLine();
            $this->command->info('✅ Categories seeded successfully!');
            $this->command->info("   Total categories: {$categoriesCreated}");
            
            $this->displaySummary();
        } else {
            $this->command->warn('⚠️  No categories created. Check if types exist.');
        }
    }

    /**
     * Get comprehensive category mappings per type
     */
    private function getCategoryMappings(): array
    {
        return [
            // ========================================
            // REVENUE & INCOME CATEGORIES
            // ========================================
            'Penerimaan Penjualan' => [
                [
                    'slug' => 'penjualan-outlet-reguler',
                    'name' => 'Penjualan Outlet Reguler',
                    'description' => 'Penjualan produk obat-obatan di outlet apotek reguler',
                    'color' => '#10B981',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'penjualan-outlet-resep',
                    'name' => 'Penjualan Outlet Resep',
                    'description' => 'Penjualan khusus outlet resep dokter',
                    'color' => '#059669',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'penjualan-klinik',
                    'name' => 'Penjualan Klinik',
                    'description' => 'Penjualan di klinik kesehatan (Wisma Asri, dll)',
                    'color' => '#047857',
                    'sort_order' => 3,
                ],
                [
                    'slug' => 'penjualan-alkes',
                    'name' => 'Penjualan Alat Kesehatan',
                    'description' => 'Penjualan alat kesehatan dan medical devices',
                    'color' => '#065F46',
                    'sort_order' => 4,
                ],
            ],

            'Penerimaan Transfer' => [
                [
                    'slug' => 'transfer-dari-distributor',
                    'name' => 'Transfer dari Distributor',
                    'description' => 'Penerimaan transfer dari Kimia Farma dan distributor obat',
                    'color' => '#3B82F6',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'transfer-dari-customer',
                    'name' => 'Transfer dari Customer',
                    'description' => 'Penerimaan transfer dari customer/pasien',
                    'color' => '#2563EB',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'transfer-internal',
                    'name' => 'Transfer Internal',
                    'description' => 'Penerimaan transfer antar cabang internal',
                    'color' => '#1D4ED8',
                    'sort_order' => 3,
                ],
            ],

            'Penerimaan Tunai' => [
                [
                    'slug' => 'setor-tunai-outlet',
                    'name' => 'Setor Tunai Outlet',
                    'description' => 'Setoran tunai harian dari outlet (LIPH)',
                    'color' => '#84CC16',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'setor-tunai-klinik',
                    'name' => 'Setor Tunai Klinik',
                    'description' => 'Setoran tunai dari klinik',
                    'color' => '#65A30D',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'cod-pembayaran',
                    'name' => 'COD / Cash on Delivery',
                    'description' => 'Pembayaran COD dari customer',
                    'color' => '#4D7C0F',
                    'sort_order' => 3,
                ],
            ],

            'Penerimaan QR Code' => [
                [
                    'slug' => 'qris-payment',
                    'name' => 'QRIS Payment',
                    'description' => 'Pembayaran via QRIS (QR Code Indonesian Standard)',
                    'color' => '#8B5CF6',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'otopay-qr',
                    'name' => 'OTOPAY QR',
                    'description' => 'Pembayaran via OTOPAY QR system',
                    'color' => '#7C3AED',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'merchant-qr',
                    'name' => 'Merchant QR',
                    'description' => 'Pembayaran QR merchant specific',
                    'color' => '#6D28D9',
                    'sort_order' => 3,
                ],
            ],

            'Penerimaan Klaim Asuransi' => [
                [
                    'slug' => 'klaim-asuransi-kesehatan',
                    'name' => 'Klaim Asuransi Kesehatan',
                    'description' => 'Klaim dari asuransi kesehatan (Sinar Mas, Hanwha, Perta)',
                    'color' => '#14B8A6',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'klaim-bpjs',
                    'name' => 'Klaim BPJS',
                    'description' => 'Klaim dari BPJS Kesehatan',
                    'color' => '#0D9488',
                    'sort_order' => 2,
                ],
            ],

            // ========================================
            // OPERATIONAL EXPENSES CATEGORIES
            // ========================================
            'Biaya Operasional Outlet' => [
                [
                    'slug' => 'bop-bulanan-outlet',
                    'name' => 'BOP Bulanan Outlet',
                    'description' => 'Biaya Operasional Perusahaan (BOP) bulanan outlet',
                    'color' => '#EF4444',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'bop-klinik',
                    'name' => 'BOP Klinik',
                    'description' => 'Biaya operasional klinik kesehatan',
                    'color' => '#DC2626',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'rumah-tangga-outlet',
                    'name' => 'Rumah Tangga Outlet',
                    'description' => 'Biaya rumah tangga harian outlet (rumat)',
                    'color' => '#B91C1C',
                    'sort_order' => 3,
                ],
            ],

            'Biaya Utilitas' => [
                [
                    'slug' => 'listrik-pln',
                    'name' => 'Listrik PLN',
                    'description' => 'Pembayaran listrik ke PLN',
                    'color' => '#F59E0B',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'air-pdam',
                    'name' => 'Air PDAM',
                    'description' => 'Pembayaran air PDAM',
                    'color' => '#D97706',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'internet-telepon',
                    'name' => 'Internet & Telepon',
                    'description' => 'Biaya internet, telepon, dan komunikasi',
                    'color' => '#B45309',
                    'sort_order' => 3,
                ],
                [
                    'slug' => 'ipl-gedung',
                    'name' => 'IPL Gedung',
                    'description' => 'Iuran Pengelolaan Lingkungan gedung/kompleks',
                    'color' => '#92400E',
                    'sort_order' => 4,
                ],
            ],

            'Biaya Maintenance & Perbaikan' => [
                [
                    'slug' => 'service-ac',
                    'name' => 'Service AC',
                    'description' => 'Service dan maintenance AC outlet',
                    'color' => '#06B6D4',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'perbaikan-cctv',
                    'name' => 'Perbaikan CCTV',
                    'description' => 'Instalasi dan perbaikan CCTV',
                    'color' => '#0891B2',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'maintenance-peralatan',
                    'name' => 'Maintenance Peralatan',
                    'description' => 'Maintenance peralatan medis dan operasional',
                    'color' => '#0E7490',
                    'sort_order' => 3,
                ],
                [
                    'slug' => 'perbaikan-gedung',
                    'name' => 'Perbaikan Gedung',
                    'description' => 'Perbaikan dan renovasi gedung',
                    'color' => '#155E75',
                    'sort_order' => 4,
                ],
            ],

            // ========================================
            // PURCHASING CATEGORIES
            // ========================================
            'Pembelian Obat & Alkes' => [
                [
                    'slug' => 'pembelian-obat-generic',
                    'name' => 'Pembelian Obat Generic',
                    'description' => 'Pembelian obat generic dari distributor',
                    'color' => '#16A34A',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'pembelian-obat-paten',
                    'name' => 'Pembelian Obat Paten',
                    'description' => 'Pembelian obat paten/branded',
                    'color' => '#15803D',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'pembelian-alat-kesehatan',
                    'name' => 'Pembelian Alat Kesehatan',
                    'description' => 'Pembelian alat kesehatan dan medical devices',
                    'color' => '#166534',
                    'sort_order' => 3,
                ],
                [
                    'slug' => 'pembelian-obat-khusus',
                    'name' => 'Pembelian Obat Khusus',
                    'description' => 'Pembelian obat khusus (cold chain, narkotika)',
                    'color' => '#14532D',
                    'sort_order' => 4,
                ],
            ],

            'Pembelian Inventaris' => [
                [
                    'slug' => 'pembelian-furniture',
                    'name' => 'Pembelian Furniture',
                    'description' => 'Pembelian furniture kantor dan outlet',
                    'color' => '#A855F7',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'pembelian-elektronik',
                    'name' => 'Pembelian Elektronik',
                    'description' => 'Pembelian peralatan elektronik (komputer, printer)',
                    'color' => '#9333EA',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'pembelian-peralatan-medis',
                    'name' => 'Pembelian Peralatan Medis',
                    'description' => 'Pembelian peralatan medis non-disposable',
                    'color' => '#7E22CE',
                    'sort_order' => 3,
                ],
            ],

            'Pembelian Supplies' => [
                [
                    'slug' => 'pembelian-atk',
                    'name' => 'Pembelian ATK',
                    'description' => 'Pembelian alat tulis kantor',
                    'color' => '#EC4899',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'pembelian-packaging',
                    'name' => 'Pembelian Packaging',
                    'description' => 'Pembelian kantong plastik, paper bag, box',
                    'color' => '#DB2777',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'pembelian-cleaning-supplies',
                    'name' => 'Pembelian Cleaning Supplies',
                    'description' => 'Pembelian supplies kebersihan',
                    'color' => '#BE185D',
                    'sort_order' => 3,
                ],
            ],

            'Pembelian Marketing Material' => [
                [
                    'slug' => 'pembelian-sticker-spanduk',
                    'name' => 'Sticker & Spanduk',
                    'description' => 'Pembelian sticker, spanduk, banner',
                    'color' => '#F97316',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'pembelian-brosur',
                    'name' => 'Brosur & Leaflet',
                    'description' => 'Pembelian brosur dan leaflet promosi',
                    'color' => '#EA580C',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'pembelian-merchandise',
                    'name' => 'Merchandise',
                    'description' => 'Pembelian merchandise promosi',
                    'color' => '#C2410C',
                    'sort_order' => 3,
                ],
            ],

            // ========================================
            // PAYROLL & HR CATEGORIES
            // ========================================
            'Gaji Karyawan' => [
                [
                    'slug' => 'gaji-tetap-bulanan',
                    'name' => 'Gaji Tetap Bulanan',
                    'description' => 'Gaji pokok karyawan tetap',
                    'color' => '#3B82F6',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'gaji-parttime',
                    'name' => 'Gaji Part-time',
                    'description' => 'Gaji karyawan part-time',
                    'color' => '#2563EB',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'gaji-freelance',
                    'name' => 'Gaji Freelance',
                    'description' => 'Pembayaran ke freelancer',
                    'color' => '#1D4ED8',
                    'sort_order' => 3,
                ],
            ],

            'Tunjangan & Bonus' => [
                [
                    'slug' => 'tunjangan-transport',
                    'name' => 'Tunjangan Transport',
                    'description' => 'Tunjangan transportasi karyawan',
                    'color' => '#10B981',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'tunjangan-makan',
                    'name' => 'Tunjangan Makan',
                    'description' => 'Tunjangan makan karyawan',
                    'color' => '#059669',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'tunjangan-kesehatan',
                    'name' => 'Tunjangan Kesehatan',
                    'description' => 'Tunjangan kesehatan karyawan',
                    'color' => '#047857',
                    'sort_order' => 3,
                ],
                [
                    'slug' => 'bonus-kinerja',
                    'name' => 'Bonus Kinerja',
                    'description' => 'Bonus berdasarkan kinerja',
                    'color' => '#065F46',
                    'sort_order' => 4,
                ],
            ],

            'THR & Dinas Lebaran' => [
                [
                    'slug' => 'thr-karyawan',
                    'name' => 'THR Karyawan',
                    'description' => 'Tunjangan Hari Raya karyawan',
                    'color' => '#F59E0B',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'dinas-lebaran-karyawan',
                    'name' => 'Dinas Lebaran',
                    'description' => 'Dinas lebaran untuk karyawan',
                    'color' => '#D97706',
                    'sort_order' => 2,
                ],
            ],

            'Honor & Fee Professional' => [
                [
                    'slug' => 'jasa-praktik-dokter-spesialis',
                    'name' => 'Jasa Praktik Dokter Spesialis',
                    'description' => 'Honor jasa praktik dokter spesialis',
                    'color' => '#8B5CF6',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'jasa-praktik-dokter-umum',
                    'name' => 'Jasa Praktik Dokter Umum',
                    'description' => 'Honor jasa praktik dokter umum',
                    'color' => '#7C3AED',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'fee-apoteker',
                    'name' => 'Fee Apoteker',
                    'description' => 'Fee jasa apoteker',
                    'color' => '#6D28D9',
                    'sort_order' => 3,
                ],
                [
                    'slug' => 'fee-konsultan',
                    'name' => 'Fee Konsultan',
                    'description' => 'Fee konsultan eksternal',
                    'color' => '#5B21B6',
                    'sort_order' => 4,
                ],
            ],

            'Reimburse Karyawan' => [
                [
                    'slug' => 'reimburse-perjalanan-dinas',
                    'name' => 'Reimburse Perjalanan Dinas',
                    'description' => 'Penggantian biaya perjalanan dinas',
                    'color' => '#06B6D4',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'reimburse-bensin',
                    'name' => 'Reimburse Bensin',
                    'description' => 'Penggantian biaya bensin karyawan',
                    'color' => '#0891B2',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'reimburse-parkir-tol',
                    'name' => 'Reimburse Parkir & Tol',
                    'description' => 'Penggantian parkir dan tol',
                    'color' => '#0E7490',
                    'sort_order' => 3,
                ],
            ],

            // ========================================
            // VENDOR & SUPPLIER CATEGORIES
            // ========================================
            'Pembayaran Vendor Obat' => [
                [
                    'slug' => 'pembayaran-kimia-farma',
                    'name' => 'Pembayaran Kimia Farma',
                    'description' => 'Pembayaran ke Kimia Farma (distributor utama)',
                    'color' => '#EF4444',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'pembayaran-pbf',
                    'name' => 'Pembayaran PBF',
                    'description' => 'Pembayaran ke Pedagang Besar Farmasi',
                    'color' => '#DC2626',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'pembayaran-distributor-alkes',
                    'name' => 'Pembayaran Distributor Alkes',
                    'description' => 'Pembayaran ke distributor alat kesehatan',
                    'color' => '#B91C1C',
                    'sort_order' => 3,
                ],
            ],

            'Pembayaran Vendor Service' => [
                [
                    'slug' => 'pembayaran-it-support',
                    'name' => 'IT Support',
                    'description' => 'Pembayaran jasa IT support dan maintenance',
                    'color' => '#3B82F6',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'pembayaran-cleaning-service',
                    'name' => 'Cleaning Service',
                    'description' => 'Pembayaran jasa cleaning service',
                    'color' => '#2563EB',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'pembayaran-security',
                    'name' => 'Security',
                    'description' => 'Pembayaran jasa security',
                    'color' => '#1D4ED8',
                    'sort_order' => 3,
                ],
                [
                    'slug' => 'pembayaran-pest-control',
                    'name' => 'Pest Control',
                    'description' => 'Pembayaran jasa pest control',
                    'color' => '#1E40AF',
                    'sort_order' => 4,
                ],
            ],

            'Pembayaran Vendor Supplies' => [
                [
                    'slug' => 'pembayaran-percetakan',
                    'name' => 'Percetakan',
                    'description' => 'Pembayaran ke vendor percetakan',
                    'color' => '#A855F7',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'pembayaran-supplier-atk',
                    'name' => 'Supplier ATK',
                    'description' => 'Pembayaran ke supplier ATK',
                    'color' => '#9333EA',
                    'sort_order' => 2,
                ],
            ],

            // ========================================
            // BANKING & FEES CATEGORIES
            // ========================================
            'Transfer Fee & Admin Bank' => [
                [
                    'slug' => 'biaya-transfer-antarbank',
                    'name' => 'Biaya Transfer Antar Bank',
                    'description' => 'Biaya transfer ke bank lain',
                    'color' => '#EF4444',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'biaya-admin-bank',
                    'name' => 'Biaya Admin Bank',
                    'description' => 'Biaya administrasi bulanan bank',
                    'color' => '#DC2626',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'biaya-kliring',
                    'name' => 'Biaya Kliring',
                    'description' => 'Biaya kliring cek dan giro',
                    'color' => '#B91C1C',
                    'sort_order' => 3,
                ],
            ],

            'Transfer Antar Rekening' => [
                [
                    'slug' => 'transfer-internal-sesama-bank',
                    'name' => 'Transfer Internal Sesama Bank',
                    'description' => 'Transfer antar rekening di bank yang sama',
                    'color' => '#3B82F6',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'pemindahan-dana',
                    'name' => 'Pemindahan Dana',
                    'description' => 'Pemindahan dana antar rekening perusahaan',
                    'color' => '#2563EB',
                    'sort_order' => 2,
                ],
            ],

            'Transfer Antar Cabang' => [
                [
                    'slug' => 'transfer-ke-outlet',
                    'name' => 'Transfer ke Outlet',
                    'description' => 'Transfer dana ke outlet/apotek',
                    'color' => '#10B981',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'transfer-dari-outlet',
                    'name' => 'Transfer dari Outlet',
                    'description' => 'Penerimaan transfer dari outlet/apotek',
                    'color' => '#059669',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'mcm-inhousetrf',
                    'name' => 'MCM InhouseTrf',
                    'description' => 'Transfer via MCM InhouseTrf system',
                    'color' => '#047857',
                    'sort_order' => 3,
                ],
            ],

            // ========================================
            // INSURANCE CATEGORIES
            // ========================================
            'Premi Asuransi' => [
                [
                    'slug' => 'premi-asuransi-kesehatan',
                    'name' => 'Premi Asuransi Kesehatan',
                    'description' => 'Pembayaran premi asuransi kesehatan karyawan',
                    'color' => '#14B8A6',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'premi-asuransi-jiwa',
                    'name' => 'Premi Asuransi Jiwa',
                    'description' => 'Pembayaran premi asuransi jiwa',
                    'color' => '#0D9488',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'premi-asuransi-properti',
                    'name' => 'Premi Asuransi Properti',
                    'description' => 'Pembayaran premi asuransi properti/gedung',
                    'color' => '#0F766E',
                    'sort_order' => 3,
                ],
            ],

            'Klaim Asuransi Masuk' => [
                [
                    'slug' => 'klaim-dari-asuransi-kesehatan',
                    'name' => 'Klaim dari Asuransi Kesehatan',
                    'description' => 'Penerimaan klaim dari perusahaan asuransi',
                    'color' => '#10B981',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'klaim-dari-bpjs',
                    'name' => 'Klaim dari BPJS',
                    'description' => 'Penerimaan klaim dari BPJS Kesehatan',
                    'color' => '#059669',
                    'sort_order' => 2,
                ],
            ],

            'Klaim Asuransi Keluar' => [
                [
                    'slug' => 'pembayaran-klaim-ke-pasien',
                    'name' => 'Pembayaran Klaim ke Pasien',
                    'description' => 'Pembayaran klaim asuransi ke pasien',
                    'color' => '#EF4444',
                    'sort_order' => 1,
                ],
            ],

            // ========================================
            // TAX CATEGORIES
            // ========================================
            'Pajak Penghasilan' => [
                [
                    'slug' => 'pph-21',
                    'name' => 'PPh 21',
                    'description' => 'Pajak Penghasilan Pasal 21 (gaji karyawan)',
                    'color' => '#DC2626',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'pph-23',
                    'name' => 'PPh 23',
                    'description' => 'Pajak Penghasilan Pasal 23 (jasa)',
                    'color' => '#B91C1C',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'pph-final',
                    'name' => 'PPh Final',
                    'description' => 'Pajak Penghasilan Final',
                    'color' => '#991B1B',
                    'sort_order' => 3,
                ],
            ],

            'PPN & PPnBM' => [
                [
                    'slug' => 'ppn-masukan',
                    'name' => 'PPN Masukan',
                    'description' => 'PPN yang dibayar saat pembelian',
                    'color' => '#F97316',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'ppn-keluaran',
                    'name' => 'PPN Keluaran',
                    'description' => 'PPN yang dipungut dari penjualan',
                    'color' => '#EA580C',
                    'sort_order' => 2,
                ],
            ],

            'Pajak Lainnya' => [
                [
                    'slug' => 'pbb',
                    'name' => 'PBB',
                    'description' => 'Pajak Bumi dan Bangunan',
                    'color' => '#84CC16',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'pajak-kendaraan',
                    'name' => 'Pajak Kendaraan',
                    'description' => 'Pajak kendaraan bermotor',
                    'color' => '#65A30D',
                    'sort_order' => 2,
                ],
            ],

            // ========================================
            // LOANS & RECEIVABLES CATEGORIES
            // ========================================
            'Bon Karyawan' => [
                [
                    'slug' => 'bon-sementara-karyawan',
                    'name' => 'Bon Sementara Karyawan',
                    'description' => 'Bon sementara untuk operasional',
                    'color' => '#EC4899',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'bon-perbaikan',
                    'name' => 'Bon Perbaikan',
                    'description' => 'Bon untuk perbaikan dan maintenance',
                    'color' => '#DB2777',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'bon-pembelian',
                    'name' => 'Bon Pembelian',
                    'description' => 'Bon pembelian kebutuhan outlet',
                    'color' => '#BE185D',
                    'sort_order' => 3,
                ],
            ],

            // ========================================
            // OUTLET OPERATIONS CATEGORIES
            // ========================================
            'Setoran Outlet' => [
                [
                    'slug' => 'liph-outlet-reguler',
                    'name' => 'LIPH Outlet Reguler',
                    'description' => 'Setoran LIPH dari outlet apotek reguler',
                    'color' => '#10B981',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'liph-outlet-resep',
                    'name' => 'LIPH Outlet Resep',
                    'description' => 'Setoran LIPH dari outlet resep',
                    'color' => '#059669',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'liph-klinik',
                    'name' => 'LIPH Klinik',
                    'description' => 'Setoran LIPH dari klinik',
                    'color' => '#047857',
                    'sort_order' => 3,
                ],
            ],

            'Rumah Tangga Outlet' => [
                [
                    'slug' => 'rumat-outlet-harian',
                    'name' => 'Rumat Outlet Harian',
                    'description' => 'Biaya rumah tangga outlet harian',
                    'color' => '#F59E0B',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'rumat-klinik',
                    'name' => 'Rumat Klinik',
                    'description' => 'Biaya rumah tangga klinik',
                    'color' => '#D97706',
                    'sort_order' => 2,
                ],
            ],

            // ========================================
            // PAYMENT METHODS CATEGORIES
            // ========================================
            'Transaksi QR Code' => [
                [
                    'slug' => 'qris-static',
                    'name' => 'QRIS Static',
                    'description' => 'Pembayaran via QRIS static QR',
                    'color' => '#8B5CF6',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'qris-dynamic',
                    'name' => 'QRIS Dynamic',
                    'description' => 'Pembayaran via QRIS dynamic QR',
                    'color' => '#7C3AED',
                    'sort_order' => 2,
                ],
            ],

            'Transaksi EDC' => [
                [
                    'slug' => 'edc-debit',
                    'name' => 'EDC Debit Card',
                    'description' => 'Pembayaran via EDC kartu debit',
                    'color' => '#3B82F6',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'edc-credit',
                    'name' => 'EDC Credit Card',
                    'description' => 'Pembayaran via EDC kartu kredit',
                    'color' => '#2563EB',
                    'sort_order' => 2,
                ],
            ],

            'Transaksi Mobile Banking' => [
                [
                    'slug' => 'atmb-transfer',
                    'name' => 'ATMB Transfer',
                    'description' => 'Transfer via ATM/Mobile Banking',
                    'color' => '#06B6D4',
                    'sort_order' => 1,
                ],
            ],
        ];
    }

    /**
     * Make category array
     */
    private function makeCategory(int $companyId, int $typeId, array $data): array
    {
        return [
            'uuid' => Str::uuid(),
            'company_id' => $companyId,
            'type_id' => $typeId,
            'slug' => $data['slug'],
            'name' => $data['name'],
            'description' => $data['description'],
            'color' => $data['color'],
            'sort_order' => $data['sort_order'],
            'created_at' => $this->now->copy()->subDays(rand(20, 150)),
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
        $this->command->info('📊 CATEGORIES SUMMARY BY TYPE:');
        $this->command->info('='.str_repeat('=', 79));

        $summary = DB::table('categories as c')
            ->join('types as t', 't.id', '=', 'c.type_id')
            ->select('t.name as type_name', DB::raw('count(c.id) as count'))
            ->groupBy('t.id', 't.name')
            ->orderBy('t.name')
            ->get();

        foreach ($summary as $item) {
            $this->command->info(sprintf("   %-50s: %d categories", $item->type_name, $item->count));
        }

        $total = DB::table('categories')->count();
        $this->command->newLine();
        $this->command->info("Total Categories: {$total}");
    }
}