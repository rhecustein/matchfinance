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
        // Disesuaikan dengan data CSV yang dianalisis
        // ============================================
        
        $standardTypes = [
            [
                'name' => 'Pemasukan',
                'description' => 'Semua transaksi uang masuk (Setoran, transfer masuk, penerimaan)',
                'sort_order' => 1,
            ],
            [
                'name' => 'Pengeluaran',
                'description' => 'Semua transaksi uang keluar (Pembayaran, biaya, pembelian)',
                'sort_order' => 2,
            ],
            [
                'name' => 'Transfer Internal',
                'description' => 'Transfer antar rekening internal atau antar cabang',
                'sort_order' => 3,
            ],
            [
                'name' => 'Biaya Operasional',
                'description' => 'Biaya operasional perusahaan (BOP, utilitas, sewa)',
                'sort_order' => 4,
            ],
            [
                'name' => 'Payroll',
                'description' => 'Transaksi gaji, tunjangan, dan benefit karyawan',
                'sort_order' => 5,
            ],
            [
                'name' => 'Vendor & Supplier',
                'description' => 'Transaksi dengan vendor dan supplier (Kimia Farma, distributor obat)',
                'sort_order' => 6,
            ],
            [
                'name' => 'Transaksi Bank',
                'description' => 'Transaksi perbankan (Transfer fee, admin bank, kliring)',
                'sort_order' => 7,
            ],
            [
                'name' => 'Cash Transaction',
                'description' => 'Transaksi tunai (COD, setoran tunai, penarikan)',
                'sort_order' => 8,
            ],
            [
                'name' => 'Pinjaman & Piutang',
                'description' => 'Transaksi pinjaman, piutang, dan bon karyawan',
                'sort_order' => 9,
            ],
            [
                'name' => 'Asuransi',
                'description' => 'Transaksi terkait asuransi (Klaim, premi)',
                'sort_order' => 10,
            ],
            [
                'name' => 'Pajak',
                'description' => 'Pembayaran dan pemotongan pajak',
                'sort_order' => 11,
            ],
            [
                'name' => 'Outlet & Cabang',
                'description' => 'Transaksi dari outlet, apotek, dan cabang',
                'sort_order' => 12,
            ],
            [
                'name' => 'Pembelian',
                'description' => 'Pembelian obat, alat kesehatan, dan inventaris',
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
            $this->command->info('📊 Type Categories Created:');
            $this->command->info('   1. Pemasukan - All incoming transactions');
            $this->command->info('   2. Pengeluaran - All outgoing transactions');
            $this->command->info('   3. Transfer Internal - Internal transfers');
            $this->command->info('   4. Biaya Operasional - Operational costs');
            $this->command->info('   5. Payroll - Employee salaries');
            $this->command->info('   6. Vendor & Supplier - Vendor payments');
            $this->command->info('   7. Transaksi Bank - Banking transactions');
            $this->command->info('   8. Cash Transaction - Cash operations');
            $this->command->info('   9. Pinjaman & Piutang - Loans & receivables');
            $this->command->info('   10. Asuransi - Insurance');
            $this->command->info('   11. Pajak - Tax payments');
            $this->command->info('   12. Outlet & Cabang - Branch transactions');
            $this->command->info('   13. Pembelian - Purchases');
            $this->command->info('   14. Lain-lain - Others');
        } else {
            $this->command->warn('⚠️  No types created. Check if companies exist.');
        }
    }
}