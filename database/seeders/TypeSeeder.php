<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TypeSeeder extends Seeder
{
    private $now;

    /**
     * Run the database seeds.
     * 
     * FULL VERSION - Complete Transaction Types
     * Berdasarkan analisis 26,357 transaksi bank real
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     */
    public function run(): void
    {
        $this->command->info('🏷️  Seeding Transaction Types (FULL VERSION)...');
        $this->command->newLine();
        
        $this->now = Carbon::now();

        // Ambil companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->error('❌ No companies found! Please run CompanySeeder first.');
            return;
        }

        $allTypes = [];

        // ============================================
        // COMPREHENSIVE TRANSACTION TYPES
        // Berdasarkan analisis data real bank statement
        // ============================================
        
        $transactionTypes = $this->getTransactionTypes();

        // Generate types untuk setiap company
        foreach ($companies as $company) {
            $this->command->info("   Processing company: {$company->name}");
            
            foreach ($transactionTypes as $type) {
                $allTypes[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company->id,
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'sort_order' => $type['sort_order'],
                    'created_at' => $this->now->copy()->subDays(rand(30, 180)),
                    'updated_at' => $this->now,
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
            
            $this->command->newLine();
            $this->command->info('✅ Types seeded successfully!');
            $this->command->info("   Total types created: " . count($allTypes));
            $this->command->info("   Companies: " . $companies->count());
            $this->command->info("   Types per company: " . count($transactionTypes));
            
            $this->displayTypeSummary($transactionTypes);
        } else {
            $this->command->warn('⚠️  No types created. Check if companies exist.');
        }
    }

    /**
     * Get comprehensive transaction types
     * Organized by business function
     */
    private function getTransactionTypes(): array
    {
        return [
            // ========================================
            // GROUP 1: REVENUE & INCOME (Pemasukan)
            // ========================================
            [
                'name' => 'Penerimaan Penjualan',
                'description' => 'Penerimaan dari penjualan produk/obat di outlet (LIPH, Setor Tunai outlet)',
                'sort_order' => 10,
            ],
            [
                'name' => 'Penerimaan Transfer',
                'description' => 'Penerimaan uang via transfer bank dari customer atau pihak lain',
                'sort_order' => 11,
            ],
            [
                'name' => 'Penerimaan Tunai',
                'description' => 'Penerimaan uang tunai (Setor Tunai, COD)',
                'sort_order' => 12,
            ],
            [
                'name' => 'Penerimaan QR Code',
                'description' => 'Penerimaan pembayaran via QR Code (QRIS, OTOPAY)',
                'sort_order' => 13,
            ],
            [
                'name' => 'Penerimaan Klaim Asuransi',
                'description' => 'Penerimaan klaim dari asuransi kesehatan',
                'sort_order' => 14,
            ],

            // ========================================
            // GROUP 2: OPERATIONAL EXPENSES (Pengeluaran Operasional)
            // ========================================
            [
                'name' => 'Biaya Operasional Outlet',
                'description' => 'BOP, biaya operasional harian outlet (listrik, air, maintenance)',
                'sort_order' => 20,
            ],
            [
                'name' => 'Biaya Utilitas',
                'description' => 'Pembayaran listrik (PLN), air, internet, telepon',
                'sort_order' => 21,
            ],
            [
                'name' => 'Biaya Maintenance & Perbaikan',
                'description' => 'Service AC, perbaikan CCTV, maintenance peralatan',
                'sort_order' => 22,
            ],
            [
                'name' => 'Biaya Sewa',
                'description' => 'Sewa tempat, sewa kendaraan, sewa peralatan',
                'sort_order' => 23,
            ],
            [
                'name' => 'Biaya Kebersihan & Keamanan',
                'description' => 'Cleaning service, security, pest control',
                'sort_order' => 24,
            ],

            // ========================================
            // GROUP 3: PURCHASING (Pembelian)
            // ========================================
            [
                'name' => 'Pembelian Obat & Alkes',
                'description' => 'Pembelian obat-obatan dan alat kesehatan dari distributor',
                'sort_order' => 30,
            ],
            [
                'name' => 'Pembelian Inventaris',
                'description' => 'Pembelian barang inventaris (furniture, elektronik, komputer)',
                'sort_order' => 31,
            ],
            [
                'name' => 'Pembelian Supplies',
                'description' => 'Pembelian supplies kantor (ATK, printer, toner)',
                'sort_order' => 32,
            ],
            [
                'name' => 'Pembelian Marketing Material',
                'description' => 'Pembelian sticker, spanduk, brosur, merchandise',
                'sort_order' => 33,
            ],

            // ========================================
            // GROUP 4: PAYROLL & HR (Gaji & SDM)
            // ========================================
            [
                'name' => 'Gaji Karyawan',
                'description' => 'Pembayaran gaji bulanan karyawan tetap',
                'sort_order' => 40,
            ],
            [
                'name' => 'Tunjangan & Bonus',
                'description' => 'Tunjangan karyawan (transport, makan, kesehatan) dan bonus',
                'sort_order' => 41,
            ],
            [
                'name' => 'THR & Dinas Lebaran',
                'description' => 'THR (Tunjangan Hari Raya) dan dinas lebaran karyawan',
                'sort_order' => 42,
            ],
            [
                'name' => 'Honor & Fee Professional',
                'description' => 'Jasa praktik spesialis, honor dokter, konsultan',
                'sort_order' => 43,
            ],
            [
                'name' => 'Reimburse Karyawan',
                'description' => 'Penggantian biaya karyawan (perjalanan dinas, bensin)',
                'sort_order' => 44,
            ],

            // ========================================
            // GROUP 5: VENDOR & SUPPLIER
            // ========================================
            [
                'name' => 'Pembayaran Vendor Obat',
                'description' => 'Pembayaran ke distributor obat (Kimia Farma, PBF)',
                'sort_order' => 50,
            ],
            [
                'name' => 'Pembayaran Vendor Service',
                'description' => 'Pembayaran vendor jasa (IT support, service AC, cleaning)',
                'sort_order' => 51,
            ],
            [
                'name' => 'Pembayaran Vendor Supplies',
                'description' => 'Pembayaran vendor supplies (percetakan, ATK)',
                'sort_order' => 52,
            ],

            // ========================================
            // GROUP 6: BANKING & FEES
            // ========================================
            [
                'name' => 'Transfer Fee & Admin Bank',
                'description' => 'Biaya transfer, admin bank, biaya transaksi',
                'sort_order' => 60,
            ],
            [
                'name' => 'Transfer Antar Rekening',
                'description' => 'Transfer internal antar rekening perusahaan',
                'sort_order' => 61,
            ],
            [
                'name' => 'Transfer Antar Cabang',
                'description' => 'Transfer dana antar outlet/cabang (MCM InhouseTrf)',
                'sort_order' => 62,
            ],

            // ========================================
            // GROUP 7: INSURANCE & CLAIMS
            // ========================================
            [
                'name' => 'Premi Asuransi',
                'description' => 'Pembayaran premi asuransi (kesehatan, jiwa, properti)',
                'sort_order' => 70,
            ],
            [
                'name' => 'Klaim Asuransi Masuk',
                'description' => 'Penerimaan klaim dari asuransi',
                'sort_order' => 71,
            ],
            [
                'name' => 'Klaim Asuransi Keluar',
                'description' => 'Pembayaran klaim ke pasien/customer',
                'sort_order' => 72,
            ],

            // ========================================
            // GROUP 8: TAX & COMPLIANCE
            // ========================================
            [
                'name' => 'Pajak Penghasilan',
                'description' => 'Pembayaran PPh 21, PPh 23, PPh Final',
                'sort_order' => 80,
            ],
            [
                'name' => 'PPN & PPnBM',
                'description' => 'Pembayaran PPN, PPnBM',
                'sort_order' => 81,
            ],
            [
                'name' => 'Pajak Lainnya',
                'description' => 'PBB, pajak kendaraan, retribusi',
                'sort_order' => 82,
            ],

            // ========================================
            // GROUP 9: LOANS & RECEIVABLES
            // ========================================
            [
                'name' => 'Pinjaman Diberikan',
                'description' => 'Pemberian pinjaman ke karyawan atau pihak lain',
                'sort_order' => 90,
            ],
            [
                'name' => 'Pelunasan Pinjaman Diterima',
                'description' => 'Penerimaan pelunasan pinjaman dari peminjam',
                'sort_order' => 91,
            ],
            [
                'name' => 'Bon Karyawan',
                'description' => 'Bon sementara, bon pembelian karyawan',
                'sort_order' => 92,
            ],
            [
                'name' => 'Piutang',
                'description' => 'Piutang usaha, tagihan ke customer',
                'sort_order' => 93,
            ],

            // ========================================
            // GROUP 10: OUTLET OPERATIONS
            // ========================================
            [
                'name' => 'Setoran Outlet',
                'description' => 'Setoran penjualan harian dari outlet (LIPH)',
                'sort_order' => 100,
            ],
            [
                'name' => 'Kas Kecil Outlet',
                'description' => 'Pengeluaran kas kecil outlet untuk operasional',
                'sort_order' => 101,
            ],
            [
                'name' => 'Rumah Tangga Outlet',
                'description' => 'Biaya rumah tangga outlet (rumat), operasional harian',
                'sort_order' => 102,
            ],

            // ========================================
            // GROUP 11: PAYMENT METHODS
            // ========================================
            [
                'name' => 'Transaksi QR Code',
                'description' => 'Transaksi via QR Code (QRIS, OTOPAY QR)',
                'sort_order' => 110,
            ],
            [
                'name' => 'Transaksi EDC',
                'description' => 'Transaksi via mesin EDC, kartu debit/kredit',
                'sort_order' => 111,
            ],
            [
                'name' => 'Transaksi Tunai',
                'description' => 'Transaksi pembayaran tunai (cash)',
                'sort_order' => 112,
            ],
            [
                'name' => 'Transaksi Mobile Banking',
                'description' => 'Transaksi via mobile banking/ATM',
                'sort_order' => 113,
            ],

            // ========================================
            // GROUP 12: SPECIAL CATEGORIES
            // ========================================
            [
                'name' => 'Koreksi Transaksi',
                'description' => 'Koreksi, reversal, atau penyesuaian transaksi',
                'sort_order' => 120,
            ],
            [
                'name' => 'Refund & Return',
                'description' => 'Pengembalian dana ke customer, retur barang',
                'sort_order' => 121,
            ],
            [
                'name' => 'Investasi',
                'description' => 'Investasi jangka pendek/panjang',
                'sort_order' => 122,
            ],
            [
                'name' => 'Dividen',
                'description' => 'Pembagian dividen ke pemegang saham',
                'sort_order' => 123,
            ],

            // ========================================
            // GROUP 13: OTHERS
            // ========================================
            [
                'name' => 'Lain-lain',
                'description' => 'Transaksi yang tidak termasuk kategori lainnya',
                'sort_order' => 999,
            ],
        ];
    }

    /**
     * Display type summary in console
     */
    private function displayTypeSummary(array $types): void
    {
        $this->command->newLine();
        $this->command->info('📊 TRANSACTION TYPES CREATED:');
        $this->command->info('='.str_repeat('=', 79));
        
        // Group by sort_order range
        $groups = [
            [10, 14, '💰 REVENUE & INCOME'],
            [20, 24, '🏢 OPERATIONAL EXPENSES'],
            [30, 33, '🛒 PURCHASING'],
            [40, 44, '👥 PAYROLL & HR'],
            [50, 52, '📦 VENDOR & SUPPLIER'],
            [60, 62, '🏦 BANKING & FEES'],
            [70, 72, '🛡️  INSURANCE & CLAIMS'],
            [80, 82, '📋 TAX & COMPLIANCE'],
            [90, 93, '💳 LOANS & RECEIVABLES'],
            [100, 102, '🏪 OUTLET OPERATIONS'],
            [110, 113, '💻 PAYMENT METHODS'],
            [120, 123, '⚡ SPECIAL CATEGORIES'],
            [999, 999, '📌 OTHERS'],
        ];

        foreach ($groups as [$start, $end, $label]) {
            $groupTypes = array_filter($types, function($t) use ($start, $end) {
                return $t['sort_order'] >= $start && $t['sort_order'] <= $end;
            });

            if (!empty($groupTypes)) {
                $this->command->newLine();
                $this->command->info($label);
                foreach ($groupTypes as $type) {
                    $this->command->line("   • {$type['name']}");
                }
            }
        }

        $this->command->newLine();
        $this->command->info('='.str_repeat('=', 79));
        $this->command->info("Total: " . count($types) . " transaction types");
    }
}