<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     * - Types table sudah terisi (TypeSeeder)
     */
    public function run(): void
    {
        $this->command->info('📂 Seeding Categories...');
        
        // Ambil companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('⚠️  No companies found! Please run CompanySeeder first.');
            return;
        }

        // Ambil types
        $types = DB::table('types')->get();
        
        if ($types->isEmpty()) {
            $this->command->warn('⚠️  No types found! Please run TypeSeeder first.');
            return;
        }

        $now = Carbon::now();
        $allCategories = [];

        // ============================================
        // CATEGORIES PER TYPE
        // Detailed categorization untuk setiap type
        // ============================================
        
        $categoryMapping = [
            // TYPE 1: OUTLET
            'Outlet' => [
                ['name' => 'Apotek', 'description' => 'Apotek dan toko obat', 'color' => '#10B981', 'sort_order' => 1],
                ['name' => 'Minimarket', 'description' => 'Toko retail kecil (Alfamart, Indomaret)', 'color' => '#3B82F6', 'sort_order' => 2],
                ['name' => 'Supermarket', 'description' => 'Toko retail besar (Carrefour, Giant)', 'color' => '#8B5CF6', 'sort_order' => 3],
                ['name' => 'Restoran', 'description' => 'Restoran dan rumah makan', 'color' => '#F59E0B', 'sort_order' => 4],
                ['name' => 'Cafe', 'description' => 'Kafe dan kedai kopi', 'color' => '#6B4423', 'sort_order' => 5],
                ['name' => 'SPBU', 'description' => 'Stasiun pengisian bahan bakar', 'color' => '#EF4444', 'sort_order' => 6],
            ],
            
            // TYPE 2: TRANSAKSI PERBANKAN
            'Transaksi Perbankan' => [
                ['name' => 'Setor Tunai', 'description' => 'Penyetoran uang tunai ke rekening', 'color' => '#10B981', 'sort_order' => 1],
                ['name' => 'Tarik Tunai', 'description' => 'Penarikan uang tunai dari ATM atau teller', 'color' => '#EF4444', 'sort_order' => 2],
                ['name' => 'Kliring', 'description' => 'Transaksi kliring cek/giro', 'color' => '#8B5CF6', 'sort_order' => 3],
                ['name' => 'Real Time', 'description' => 'Transfer real-time antar bank', 'color' => '#3B82F6', 'sort_order' => 4],
            ],
            
            // TYPE 3: TRANSFER
            'Transfer' => [
                ['name' => 'Transfer Internal', 'description' => 'Transfer antar rekening dalam 1 bank', 'color' => '#10B981', 'sort_order' => 1],
                ['name' => 'Transfer Antar Bank', 'description' => 'Transfer ke bank lain', 'color' => '#3B82F6', 'sort_order' => 2],
                ['name' => 'Transfer Online', 'description' => 'Transfer melalui internet/mobile banking', 'color' => '#8B5CF6', 'sort_order' => 3],
                ['name' => 'Transfer ATM', 'description' => 'Transfer melalui mesin ATM', 'color' => '#F59E0B', 'sort_order' => 4],
                ['name' => 'Transfer Terjadwal', 'description' => 'Transfer otomatis/terjadwal', 'color' => '#06B6D4', 'sort_order' => 5],
            ],
            
            // TYPE 4: PEMBAYARAN
            'Pembayaran' => [
                ['name' => 'Listrik', 'description' => 'Pembayaran tagihan listrik PLN', 'color' => '#FBBF24', 'sort_order' => 1],
                ['name' => 'Air', 'description' => 'Pembayaran tagihan air PDAM', 'color' => '#3B82F6', 'sort_order' => 2],
                ['name' => 'Telepon', 'description' => 'Pembayaran tagihan telepon rumah', 'color' => '#10B981', 'sort_order' => 3],
                ['name' => 'Internet', 'description' => 'Pembayaran layanan internet', 'color' => '#8B5CF6', 'sort_order' => 4],
                ['name' => 'TV Kabel', 'description' => 'Pembayaran layanan TV berlangganan', 'color' => '#EF4444', 'sort_order' => 5],
                ['name' => 'Pulsa', 'description' => 'Pembelian pulsa dan paket data', 'color' => '#F59E0B', 'sort_order' => 6],
                ['name' => 'BPJS', 'description' => 'Pembayaran iuran BPJS Kesehatan/Ketenagakerjaan', 'color' => '#059669', 'sort_order' => 7],
                ['name' => 'Asuransi', 'description' => 'Pembayaran premi asuransi', 'color' => '#DC2626', 'sort_order' => 8],
                ['name' => 'Kartu Kredit', 'description' => 'Pembayaran tagihan kartu kredit', 'color' => '#7C3AED', 'sort_order' => 9],
            ],
            
            // TYPE 5: E-COMMERCE
            'E-Commerce' => [
                ['name' => 'Tokopedia', 'description' => 'Belanja di Tokopedia', 'color' => '#42B549', 'sort_order' => 1],
                ['name' => 'Shopee', 'description' => 'Belanja di Shopee', 'color' => '#EE4D2D', 'sort_order' => 2],
                ['name' => 'Lazada', 'description' => 'Belanja di Lazada', 'color' => '#0F146D', 'sort_order' => 3],
                ['name' => 'Bukalapak', 'description' => 'Belanja di Bukalapak', 'color' => '#E31E52', 'sort_order' => 4],
                ['name' => 'Blibli', 'description' => 'Belanja di Blibli', 'color' => '#0095DA', 'sort_order' => 5],
                ['name' => 'Marketplace Lainnya', 'description' => 'Platform e-commerce lainnya', 'color' => '#6B7280', 'sort_order' => 99],
            ],
            
            // TYPE 6: E-WALLET
            'E-Wallet' => [
                ['name' => 'GoPay', 'description' => 'Transaksi menggunakan GoPay', 'color' => '#00AA13', 'sort_order' => 1],
                ['name' => 'OVO', 'description' => 'Transaksi menggunakan OVO', 'color' => '#4C3494', 'sort_order' => 2],
                ['name' => 'DANA', 'description' => 'Transaksi menggunakan DANA', 'color' => '#118EEA', 'sort_order' => 3],
                ['name' => 'ShopeePay', 'description' => 'Transaksi menggunakan ShopeePay', 'color' => '#EE4D2D', 'sort_order' => 4],
                ['name' => 'LinkAja', 'description' => 'Transaksi menggunakan LinkAja', 'color' => '#F03C3C', 'sort_order' => 5],
            ],
            
            // TYPE 7: INVESTASI
            'Investasi' => [
                ['name' => 'Saham', 'description' => 'Pembelian/penjualan saham', 'color' => '#10B981', 'sort_order' => 1],
                ['name' => 'Reksadana', 'description' => 'Investasi reksadana', 'color' => '#3B82F6', 'sort_order' => 2],
                ['name' => 'Obligasi', 'description' => 'Pembelian obligasi/surat utang', 'color' => '#8B5CF6', 'sort_order' => 3],
                ['name' => 'Emas', 'description' => 'Investasi emas digital/fisik', 'color' => '#FBBF24', 'sort_order' => 4],
                ['name' => 'Deposito', 'description' => 'Penempatan deposito berjangka', 'color' => '#059669', 'sort_order' => 5],
                ['name' => 'P2P Lending', 'description' => 'Peer-to-peer lending', 'color' => '#F59E0B', 'sort_order' => 6],
            ],
            
            // TYPE 8: PINJAMAN
            'Pinjaman' => [
                ['name' => 'Kredit Tanpa Agunan', 'description' => 'KTA atau pinjaman tanpa jaminan', 'color' => '#EF4444', 'sort_order' => 1],
                ['name' => 'Kredit Kendaraan', 'description' => 'Cicilan pembelian kendaraan', 'color' => '#3B82F6', 'sort_order' => 2],
                ['name' => 'KPR', 'description' => 'Kredit pemilikan rumah', 'color' => '#10B981', 'sort_order' => 3],
                ['name' => 'Kredit Multiguna', 'description' => 'Pinjaman dengan jaminan aset', 'color' => '#8B5CF6', 'sort_order' => 4],
                ['name' => 'Pinjaman Online', 'description' => 'Pinjaman dari fintech/P2P lending', 'color' => '#F59E0B', 'sort_order' => 5],
            ],
            
            // TYPE 9: BIAYA BANK
            'Biaya Bank' => [
                ['name' => 'Administrasi Bulanan', 'description' => 'Biaya admin rekening bulanan', 'color' => '#EF4444', 'sort_order' => 1],
                ['name' => 'Biaya Transfer', 'description' => 'Biaya transfer antar bank', 'color' => '#F59E0B', 'sort_order' => 2],
                ['name' => 'Biaya ATM', 'description' => 'Biaya penarikan di ATM bank lain', 'color' => '#DC2626', 'sort_order' => 3],
                ['name' => 'Biaya Kartu', 'description' => 'Biaya pembuatan/perpanjangan kartu', 'color' => '#7C3AED', 'sort_order' => 4],
                ['name' => 'Biaya Cek/Buku', 'description' => 'Biaya cetak cek atau buku tabungan', 'color' => '#F87171', 'sort_order' => 5],
                ['name' => 'Denda', 'description' => 'Denda keterlambatan atau pelanggaran', 'color' => '#991B1B', 'sort_order' => 6],
            ],
            
            // TYPE 10: PAJAK
            'Pajak' => [
                ['name' => 'PPh 21', 'description' => 'Pajak penghasilan karyawan', 'color' => '#DC2626', 'sort_order' => 1],
                ['name' => 'PPh 23', 'description' => 'Pajak atas jasa dan sewa', 'color' => '#EF4444', 'sort_order' => 2],
                ['name' => 'PPh 25', 'description' => 'Angsuran pajak penghasilan badan', 'color' => '#F87171', 'sort_order' => 3],
                ['name' => 'PPN', 'description' => 'Pajak pertambahan nilai', 'color' => '#FCA5A5', 'sort_order' => 4],
                ['name' => 'PBB', 'description' => 'Pajak bumi dan bangunan', 'color' => '#7C3AED', 'sort_order' => 5],
                ['name' => 'Pajak Kendaraan', 'description' => 'Pajak kendaraan bermotor', 'color' => '#8B5CF6', 'sort_order' => 6],
                ['name' => 'Pajak Bunga', 'description' => 'Pajak atas bunga deposito/tabungan', 'color' => '#F59E0B', 'sort_order' => 7],
            ],
            
            // TYPE 11: GAJI & TUNJANGAN
            'Gaji & Tunjangan' => [
                ['name' => 'Gaji Pokok', 'description' => 'Gaji pokok karyawan', 'color' => '#10B981', 'sort_order' => 1],
                ['name' => 'Tunjangan Tetap', 'description' => 'Tunjangan transport, makan, dll', 'color' => '#3B82F6', 'sort_order' => 2],
                ['name' => 'Bonus', 'description' => 'Bonus kinerja atau tahunan', 'color' => '#FBBF24', 'sort_order' => 3],
                ['name' => 'THR', 'description' => 'Tunjangan Hari Raya', 'color' => '#10B981', 'sort_order' => 4],
                ['name' => 'Insentif', 'description' => 'Insentif tambahan', 'color' => '#F59E0B', 'sort_order' => 5],
                ['name' => 'Lembur', 'description' => 'Upah lembur', 'color' => '#8B5CF6', 'sort_order' => 6],
            ],
            
            // TYPE 12: OPERASIONAL
            'Operasional' => [
                ['name' => 'Sewa Kantor', 'description' => 'Biaya sewa tempat usaha', 'color' => '#3B82F6', 'sort_order' => 1],
                ['name' => 'Utilitas', 'description' => 'Listrik, air, gas', 'color' => '#FBBF24', 'sort_order' => 2],
                ['name' => 'Pemeliharaan', 'description' => 'Biaya perawatan aset', 'color' => '#10B981', 'sort_order' => 3],
                ['name' => 'ATK', 'description' => 'Alat tulis kantor', 'color' => '#8B5CF6', 'sort_order' => 4],
                ['name' => 'Konsumsi', 'description' => 'Biaya makan dan minum operasional', 'color' => '#F59E0B', 'sort_order' => 5],
                ['name' => 'Internet & Telekomunikasi', 'description' => 'Biaya internet dan telepon kantor', 'color' => '#06B6D4', 'sort_order' => 6],
                ['name' => 'Kebersihan', 'description' => 'Biaya cleaning service', 'color' => '#10B981', 'sort_order' => 7],
                ['name' => 'Keamanan', 'description' => 'Biaya satpam atau security', 'color' => '#EF4444', 'sort_order' => 8],
            ],
            
            // TYPE 13: TRANSPORTASI
            'Transportasi' => [
                ['name' => 'Bensin', 'description' => 'Pembelian bahan bakar kendaraan', 'color' => '#EF4444', 'sort_order' => 1],
                ['name' => 'Tol', 'description' => 'Biaya jalan tol', 'color' => '#3B82F6', 'sort_order' => 2],
                ['name' => 'Parkir', 'description' => 'Biaya parkir kendaraan', 'color' => '#10B981', 'sort_order' => 3],
                ['name' => 'Transportasi Online', 'description' => 'Gojek, Grab, dll', 'color' => '#00AA13', 'sort_order' => 4],
                ['name' => 'Taksi', 'description' => 'Biaya taksi konvensional', 'color' => '#FBBF24', 'sort_order' => 5],
                ['name' => 'Pengiriman', 'description' => 'Biaya kurir dan ekspedisi', 'color' => '#8B5CF6', 'sort_order' => 6],
                ['name' => 'Service Kendaraan', 'description' => 'Biaya perawatan kendaraan', 'color' => '#F59E0B', 'sort_order' => 7],
            ],
            
            // TYPE 14: LAIN-LAIN
            'Lain-lain' => [
                ['name' => 'Tidak Terkategori', 'description' => 'Transaksi yang belum dikategorikan', 'color' => '#6B7280', 'sort_order' => 1],
                ['name' => 'Lain-lain', 'description' => 'Kategori umum lainnya', 'color' => '#9CA3AF', 'sort_order' => 99],
            ],
        ];

        // Generate categories untuk setiap company
        foreach ($companies as $company) {
            $companyTypes = $types->where('company_id', $company->id);
            
            foreach ($companyTypes as $type) {
                $typeName = $type->name;
                
                if (isset($categoryMapping[$typeName])) {
                    foreach ($categoryMapping[$typeName] as $category) {
                        // PERBAIKAN: slug hanya dari nama category (tanpa company_id & type_id)
                        // Karena unique constraint adalah kombinasi (company_id + slug)
                        $slug = Str::slug($category['name']);
                        
                        $allCategories[] = [
                            'uuid' => Str::uuid(),
                            'company_id' => $company->id,
                            'type_id' => $type->id,
                            'slug' => $slug,
                            'name' => $category['name'],
                            'description' => $category['description'],
                            'color' => $category['color'],
                            'sort_order' => $category['sort_order'],
                            'created_at' => $now->copy()->subDays(rand(30, 180)),
                            'updated_at' => $now,
                            'deleted_at' => null,
                        ];
                    }
                }
            }
        }

        if (!empty($allCategories)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($allCategories, 100);
            foreach ($chunks as $chunk) {
                DB::table('categories')->insert($chunk);
            }
            
            $this->command->info('✅ Categories seeded successfully!');
            $this->command->info("   Total categories: " . count($allCategories));
            $this->command->info("   Companies: " . $companies->count());
            
            $categoriesPerCompany = count($allCategories) / $companies->count();
            $this->command->info("   Categories per company: ~" . round($categoriesPerCompany));
            
            $this->command->newLine();
            $this->command->info('📊 Categories by Type:');
            foreach ($categoryMapping as $typeName => $categories) {
                $count = count($categories);
                $this->command->info("   • {$typeName}: {$count} categories");
            }
        } else {
            $this->command->warn('⚠️  No categories created. Check if companies and types exist.');
        }
    }
}