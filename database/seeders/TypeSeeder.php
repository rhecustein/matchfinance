<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     */
    public function run(): void
    {
        // Ambil companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('⚠️  No companies found! Please run CompanySeeder first.');
            return;
        }

        $now = Carbon::now();
        $allTypes = [];

        // ============================================
        // STANDARD TRANSACTION TYPES
        // Type categories untuk klasifikasi transaksi
        // ============================================
        
        $standardTypes = [
            [
                'name' => 'Outlet',
                'description' => 'Transaksi di outlet fisik atau toko retail (Apotek, minimarket, supermarket, dll)',
                'sort_order' => 1,
            ],
            [
                'name' => 'Transaksi Perbankan',
                'description' => 'Jenis-jenis transaksi perbankan umum (Setor tunai, tarik tunai, kliring)',
                'sort_order' => 2,
            ],
            [
                'name' => 'Transfer',
                'description' => 'Transfer dana antar rekening atau antar bank (Transfer online, ATM, mobile banking)',
                'sort_order' => 3,
            ],
            [
                'name' => 'Pembayaran',
                'description' => 'Pembayaran tagihan dan layanan (Listrik, air, telepon, internet, BPJS, pajak)',
                'sort_order' => 4,
            ],
            [
                'name' => 'E-Commerce',
                'description' => 'Transaksi belanja online di marketplace atau toko online',
                'sort_order' => 5,
            ],
            [
                'name' => 'E-Wallet',
                'description' => 'Transaksi menggunakan dompet digital (GoPay, OVO, DANA, ShopeePay, LinkAja)',
                'sort_order' => 6,
            ],
            [
                'name' => 'Investasi',
                'description' => 'Transaksi investasi dan sekuritas (Saham, reksadana, obligasi, emas)',
                'sort_order' => 7,
            ],
            [
                'name' => 'Pinjaman',
                'description' => 'Transaksi terkait pinjaman (Cicilan, angsuran, pelunasan)',
                'sort_order' => 8,
            ],
            [
                'name' => 'Biaya Bank',
                'description' => 'Biaya administrasi dan layanan perbankan (Admin bulanan, biaya transfer, biaya ATM)',
                'sort_order' => 9,
            ],
            [
                'name' => 'Pajak',
                'description' => 'Pembayaran dan pemotongan pajak (PPh, PPN, pajak bunga, pajak lainnya)',
                'sort_order' => 10,
            ],
            [
                'name' => 'Gaji & Tunjangan',
                'description' => 'Penerimaan gaji, tunjangan, dan benefit karyawan',
                'sort_order' => 11,
            ],
            [
                'name' => 'Operasional',
                'description' => 'Biaya operasional bisnis (Sewa, utilitas, pemeliharaan, ATK)',
                'sort_order' => 12,
            ],
            [
                'name' => 'Transportasi',
                'description' => 'Biaya transportasi dan logistik (Bensin, tol, parkir, pengiriman)',
                'sort_order' => 13,
            ],
            [
                'name' => 'Lain-lain',
                'description' => 'Transaksi yang tidak termasuk kategori lainnya',
                'sort_order' => 99,
            ],
        ];

        // Generate types untuk setiap company
        foreach ($companies as $company) {
            foreach ($standardTypes as $type) {
                $allTypes[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company->id,
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'sort_order' => $type['sort_order'],
                    'created_at' => $now->copy()->subDays(rand(30, 180)),
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }
        }

        if (!empty($allTypes)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($allTypes, 50);
            foreach ($chunks as $chunk) {
                DB::table('types')->insert($chunk);
            }
            
            $this->command->info('✅ Types seeded successfully!');
            $this->command->info("   Total types: " . count($allTypes));
            $this->command->info("   Companies: " . $companies->count());
            $this->command->info("   Types per company: " . count($standardTypes));
            
            $this->command->newLine();
            $this->command->info('📊 Type Categories:');
            $this->command->info('   1. Outlet - Transaksi retail fisik');
            $this->command->info('   2. Transaksi Perbankan - Operasi perbankan umum');
            $this->command->info('   3. Transfer - Transfer dana');
            $this->command->info('   4. Pembayaran - Pembayaran tagihan');
            $this->command->info('   5. E-Commerce - Belanja online');
            $this->command->info('   6. E-Wallet - Dompet digital');
            $this->command->info('   7. Investasi - Investasi & sekuritas');
            $this->command->info('   8. Pinjaman - Cicilan & angsuran');
            $this->command->info('   9. Biaya Bank - Administrasi bank');
            $this->command->info('   10. Pajak - Pembayaran pajak');
            $this->command->info('   11. Gaji & Tunjangan - Payroll');
            $this->command->info('   12. Operasional - Biaya operasional');
            $this->command->info('   13. Transportasi - Logistik & transport');
            $this->command->info('   14. Lain-lain - Uncategorized');
        } else {
            $this->command->warn('⚠️  No types created. Check if companies exist.');
        }
    }
}