<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     * - Types table sudah terisi (TypeSeeder)
     * - Categories table sudah terisi (CategorySeeder)
     */
    public function run(): void
    {
        // Ambil categories
        $categories = DB::table('categories')->get();
        
        if ($categories->isEmpty()) {
            $this->command->warn('⚠️  No categories found! Please run CategorySeeder first.');
            return;
        }

        $now = Carbon::now();
        $allSubCategories = [];

        // ============================================
        // SUB-CATEGORIES MAPPING
        // Detail terbaik untuk setiap category
        // ============================================
        
        $subCategoryMapping = [
            // OUTLET CATEGORIES
            'Apotek' => [
                ['name' => 'Apotek Kimia Farma', 'description' => 'Transaksi di Apotek Kimia Farma', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'Apotek K-24', 'description' => 'Transaksi di Apotek K-24', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'Century Healthcare', 'description' => 'Transaksi di Century Healthcare', 'priority' => 8, 'sort_order' => 3],
                ['name' => 'Guardian', 'description' => 'Transaksi di Guardian', 'priority' => 8, 'sort_order' => 4],
                ['name' => 'Watsons', 'description' => 'Transaksi di Watsons', 'priority' => 8, 'sort_order' => 5],
                ['name' => 'Apotek Lainnya', 'description' => 'Apotek independen lainnya', 'priority' => 5, 'sort_order' => 99],
            ],
            
            'Minimarket' => [
                ['name' => 'Alfamart', 'description' => 'Belanja di Alfamart', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'Indomaret', 'description' => 'Belanja di Indomaret', 'priority' => 10, 'sort_order' => 2],
                ['name' => 'Alfamidi', 'description' => 'Belanja di Alfamidi', 'priority' => 8, 'sort_order' => 3],
                ['name' => 'Circle K', 'description' => 'Belanja di Circle K', 'priority' => 7, 'sort_order' => 4],
                ['name' => 'Lawson', 'description' => 'Belanja di Lawson', 'priority' => 7, 'sort_order' => 5],
            ],
            
            'Supermarket' => [
                ['name' => 'Carrefour', 'description' => 'Belanja di Carrefour', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'Giant', 'description' => 'Belanja di Giant', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'Hypermart', 'description' => 'Belanja di Hypermart', 'priority' => 9, 'sort_order' => 3],
                ['name' => 'Transmart', 'description' => 'Belanja di Transmart', 'priority' => 8, 'sort_order' => 4],
                ['name' => 'Lotte Mart', 'description' => 'Belanja di Lotte Mart', 'priority' => 8, 'sort_order' => 5],
                ['name' => 'Ranch Market', 'description' => 'Belanja di Ranch Market', 'priority' => 7, 'sort_order' => 6],
            ],
            
            'Restoran' => [
                ['name' => 'McDonald\'s', 'description' => 'Makan di McDonald\'s', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'KFC', 'description' => 'Makan di KFC', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'Burger King', 'description' => 'Makan di Burger King', 'priority' => 8, 'sort_order' => 3],
                ['name' => 'Pizza Hut', 'description' => 'Makan di Pizza Hut', 'priority' => 8, 'sort_order' => 4],
                ['name' => 'Domino\'s Pizza', 'description' => 'Makan di Domino\'s', 'priority' => 8, 'sort_order' => 5],
                ['name' => 'Solaria', 'description' => 'Makan di Solaria', 'priority' => 7, 'sort_order' => 6],
                ['name' => 'Restoran Padang', 'description' => 'Restoran masakan Padang', 'priority' => 7, 'sort_order' => 7],
                ['name' => 'Restoran Lainnya', 'description' => 'Restoran independen', 'priority' => 5, 'sort_order' => 99],
            ],
            
            'Cafe' => [
                ['name' => 'Starbucks', 'description' => 'Kopi di Starbucks', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'Kopi Kenangan', 'description' => 'Kopi di Kopi Kenangan', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'Janji Jiwa', 'description' => 'Kopi di Janji Jiwa', 'priority' => 9, 'sort_order' => 3],
                ['name' => 'Fore Coffee', 'description' => 'Kopi di Fore Coffee', 'priority' => 8, 'sort_order' => 4],
                ['name' => 'Kopi Tuku', 'description' => 'Kopi di Kopi Tuku', 'priority' => 8, 'sort_order' => 5],
                ['name' => 'Cafe Lokal', 'description' => 'Kafe independen lokal', 'priority' => 5, 'sort_order' => 99],
            ],
            
            'SPBU' => [
                ['name' => 'Pertamina', 'description' => 'SPBU Pertamina', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'Shell', 'description' => 'SPBU Shell', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'BP AKR', 'description' => 'SPBU BP AKR', 'priority' => 8, 'sort_order' => 3],
                ['name' => 'Vivo', 'description' => 'SPBU Vivo', 'priority' => 7, 'sort_order' => 4],
            ],
            
            // TRANSFER CATEGORIES
            'Transfer Internal' => [
                ['name' => 'Transfer Sesama Rekening', 'description' => 'Transfer antar rekening sendiri', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'Transfer ke User Lain', 'description' => 'Transfer ke pengguna lain 1 bank', 'priority' => 8, 'sort_order' => 2],
            ],
            
            'Transfer Antar Bank' => [
                ['name' => 'BI Fast', 'description' => 'Transfer real-time antar bank', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'SKN', 'description' => 'Sistem Kliring Nasional', 'priority' => 8, 'sort_order' => 2],
                ['name' => 'RTGS', 'description' => 'Real Time Gross Settlement', 'priority' => 7, 'sort_order' => 3],
            ],
            
            // E-WALLET CATEGORIES
            'GoPay' => [
                ['name' => 'GoPay Transfer', 'description' => 'Transfer saldo GoPay', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'GoFood', 'description' => 'Pembelian makanan via GoFood', 'priority' => 10, 'sort_order' => 2],
                ['name' => 'GoRide', 'description' => 'Pembayaran GoRide/GoCar', 'priority' => 9, 'sort_order' => 3],
                ['name' => 'GoMart', 'description' => 'Belanja via GoMart', 'priority' => 8, 'sort_order' => 4],
                ['name' => 'GoPay Merchant', 'description' => 'Pembayaran ke merchant GoPay', 'priority' => 8, 'sort_order' => 5],
            ],
            
            'OVO' => [
                ['name' => 'OVO Transfer', 'description' => 'Transfer saldo OVO', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'OVO Merchant', 'description' => 'Pembayaran ke merchant OVO', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'Grab via OVO', 'description' => 'Pembayaran Grab menggunakan OVO', 'priority' => 10, 'sort_order' => 3],
            ],
            
            'DANA' => [
                ['name' => 'DANA Transfer', 'description' => 'Transfer saldo DANA', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'DANA Merchant', 'description' => 'Pembayaran ke merchant DANA', 'priority' => 9, 'sort_order' => 2],
            ],
            
            'ShopeePay' => [
                ['name' => 'Shopee Belanja', 'description' => 'Pembayaran belanja di Shopee', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'ShopeePay Transfer', 'description' => 'Transfer saldo ShopeePay', 'priority' => 8, 'sort_order' => 2],
                ['name' => 'ShopeePay Merchant', 'description' => 'Pembayaran ke merchant ShopeePay', 'priority' => 8, 'sort_order' => 3],
            ],
            
            // PEMBAYARAN CATEGORIES
            'Listrik' => [
                ['name' => 'PLN Token', 'description' => 'Pembelian token listrik prabayar', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'PLN Pascabayar', 'description' => 'Pembayaran tagihan listrik pascabayar', 'priority' => 10, 'sort_order' => 2],
            ],
            
            'Pulsa' => [
                ['name' => 'Telkomsel', 'description' => 'Pulsa dan paket Telkomsel', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'Indosat', 'description' => 'Pulsa dan paket Indosat', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'XL Axiata', 'description' => 'Pulsa dan paket XL', 'priority' => 9, 'sort_order' => 3],
                ['name' => 'Tri', 'description' => 'Pulsa dan paket Tri', 'priority' => 8, 'sort_order' => 4],
                ['name' => 'Smartfren', 'description' => 'Pulsa dan paket Smartfren', 'priority' => 8, 'sort_order' => 5],
            ],
            
            'BPJS' => [
                ['name' => 'BPJS Kesehatan', 'description' => 'Iuran BPJS Kesehatan', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'BPJS Ketenagakerjaan', 'description' => 'Iuran BPJS Ketenagakerjaan', 'priority' => 9, 'sort_order' => 2],
            ],
            
            'Asuransi' => [
                ['name' => 'Asuransi Jiwa', 'description' => 'Premi asuransi jiwa', 'priority' => 8, 'sort_order' => 1],
                ['name' => 'Asuransi Kesehatan', 'description' => 'Premi asuransi kesehatan', 'priority' => 8, 'sort_order' => 2],
                ['name' => 'Asuransi Kendaraan', 'description' => 'Premi asuransi kendaraan', 'priority' => 8, 'sort_order' => 3],
                ['name' => 'Asuransi Properti', 'description' => 'Premi asuransi rumah/properti', 'priority' => 7, 'sort_order' => 4],
            ],
            
            // BIAYA BANK CATEGORIES
            'Administrasi Bulanan' => [
                ['name' => 'Admin Rekening Tabungan', 'description' => 'Biaya admin bulanan tabungan', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'Admin Rekening Giro', 'description' => 'Biaya admin bulanan giro', 'priority' => 9, 'sort_order' => 2],
            ],
            
            'Biaya Transfer' => [
                ['name' => 'Biaya Transfer Antar Bank', 'description' => 'Biaya transfer ke bank lain', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'Biaya BI Fast', 'description' => 'Biaya layanan BI Fast', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'Biaya RTGS', 'description' => 'Biaya transfer RTGS', 'priority' => 8, 'sort_order' => 3],
                ['name' => 'Biaya SKN', 'description' => 'Biaya transfer SKN', 'priority' => 8, 'sort_order' => 4],
            ],
            
            // PAJAK CATEGORIES
            'PPh 21' => [
                ['name' => 'Pemotongan PPh 21', 'description' => 'Potong pajak gaji karyawan', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'Setor PPh 21', 'description' => 'Penyetoran PPh 21 ke negara', 'priority' => 10, 'sort_order' => 2],
            ],
            
            'PPN' => [
                ['name' => 'PPN Masukan', 'description' => 'PPN atas pembelian', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'PPN Keluaran', 'description' => 'PPN atas penjualan', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'Setor PPN', 'description' => 'Penyetoran PPN kurang bayar', 'priority' => 10, 'sort_order' => 3],
            ],
            
            'Pajak Bunga' => [
                ['name' => 'Pajak Bunga Tabungan', 'description' => 'Pajak atas bunga tabungan', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'Pajak Bunga Deposito', 'description' => 'Pajak atas bunga deposito', 'priority' => 9, 'sort_order' => 2],
            ],
            
            // GAJI & TUNJANGAN
            'Gaji Pokok' => [
                ['name' => 'Gaji Bulanan', 'description' => 'Gaji pokok bulanan karyawan', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'Gaji Prorata', 'description' => 'Gaji proporsional', 'priority' => 8, 'sort_order' => 2],
            ],
            
            'Tunjangan Tetap' => [
                ['name' => 'Tunjangan Transportasi', 'description' => 'Tunjangan transport karyawan', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'Tunjangan Makan', 'description' => 'Tunjangan makan karyawan', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'Tunjangan Komunikasi', 'description' => 'Tunjangan pulsa/telepon', 'priority' => 8, 'sort_order' => 3],
                ['name' => 'Tunjangan Jabatan', 'description' => 'Tunjangan berdasarkan posisi', 'priority' => 8, 'sort_order' => 4],
            ],
            
            // OPERASIONAL
            'Sewa Kantor' => [
                ['name' => 'Sewa Gedung', 'description' => 'Biaya sewa gedung kantor', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'Sewa Ruko', 'description' => 'Biaya sewa ruko', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'Service Charge', 'description' => 'Biaya service charge gedung', 'priority' => 8, 'sort_order' => 3],
            ],
            
            'Utilitas' => [
                ['name' => 'Listrik Kantor', 'description' => 'Tagihan listrik operasional', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'Air Kantor', 'description' => 'Tagihan air operasional', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'Gas', 'description' => 'Biaya gas operasional', 'priority' => 7, 'sort_order' => 3],
            ],
            
            'ATK' => [
                ['name' => 'Kertas & Tinta', 'description' => 'Pembelian kertas dan tinta printer', 'priority' => 8, 'sort_order' => 1],
                ['name' => 'Alat Tulis', 'description' => 'Pembelian alat tulis', 'priority' => 7, 'sort_order' => 2],
                ['name' => 'Perlengkapan Kantor', 'description' => 'Supplies kantor lainnya', 'priority' => 6, 'sort_order' => 3],
            ],
            
            // TRANSPORTASI
            'Bensin' => [
                ['name' => 'Bensin Pertamax', 'description' => 'Pembelian Pertamax', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'Bensin Pertalite', 'description' => 'Pembelian Pertalite', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'Solar', 'description' => 'Pembelian Solar', 'priority' => 8, 'sort_order' => 3],
            ],
            
            'Transportasi Online' => [
                ['name' => 'GoRide/GoCar', 'description' => 'Transportasi Gojek', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'GrabCar/GrabBike', 'description' => 'Transportasi Grab', 'priority' => 10, 'sort_order' => 2],
            ],
            
            'Pengiriman' => [
                ['name' => 'JNE', 'description' => 'Pengiriman via JNE', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'J&T Express', 'description' => 'Pengiriman via J&T', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'SiCepat', 'description' => 'Pengiriman via SiCepat', 'priority' => 8, 'sort_order' => 3],
                ['name' => 'Anteraja', 'description' => 'Pengiriman via Anteraja', 'priority' => 8, 'sort_order' => 4],
                ['name' => 'POS Indonesia', 'description' => 'Pengiriman via POS', 'priority' => 7, 'sort_order' => 5],
            ],
            
            // LAIN-LAIN
            'Tidak Terkategori' => [
                ['name' => 'Belum Dikategorikan', 'description' => 'Transaksi yang belum dikategorikan', 'priority' => 5, 'sort_order' => 1],
            ],
        ];

        // Generate sub-categories untuk setiap category
        foreach ($categories as $category) {
            $categoryName = $category->name;
            
            if (isset($subCategoryMapping[$categoryName])) {
                foreach ($subCategoryMapping[$categoryName] as $subCategory) {
                    $allSubCategories[] = [
                        'uuid' => Str::uuid(),
                        'category_id' => $category->id,
                        'name' => $subCategory['name'],
                        'description' => $subCategory['description'],
                        'priority' => $subCategory['priority'],
                        'sort_order' => $subCategory['sort_order'],
                        'created_at' => $now->copy()->subDays(rand(30, 180)),
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ];
                }
            }
        }

        if (!empty($allSubCategories)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($allSubCategories, 100);
            foreach ($chunks as $chunk) {
                DB::table('sub_categories')->insert($chunk);
            }
            
            $this->command->info('✅ Sub-categories seeded successfully!');
            $this->command->info("   Total sub-categories: " . count($allSubCategories));
            
            $this->command->newLine();
            $this->command->info('📊 Top Categories with Most Sub-categories:');
            $counts = [];
            foreach ($subCategoryMapping as $catName => $subs) {
                $counts[$catName] = count($subs);
            }
            arsort($counts);
            $top5 = array_slice($counts, 0, 5, true);
            foreach ($top5 as $name => $count) {
                $this->command->info("   • {$name}: {$count} sub-categories");
            }
            
            $this->command->newLine();
            $this->command->info('⚡ High Priority Sub-categories (10):');
            $this->command->info('   - Apotek Kimia Farma');
            $this->command->info('   - Alfamart & Indomaret');
            $this->command->info('   - GoFood & Grab via OVO');
            $this->command->info('   - PLN Token & Pascabayar');
            $this->command->info('   - PPh 21 & PPN');
        } else {
            $this->command->warn('⚠️  No sub-categories created. Check if categories exist.');
        }
    }
}