<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AccountSeeder extends Seeder
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

        $accounts = [];

        // ============================================
        // STANDARD CHART OF ACCOUNTS
        // Akan diterapkan ke semua companies
        // ============================================
        
        $standardAccounts = [
            // ASSETS (Aktiva)
            [
                'name' => 'Kas',
                'code' => '1-1000',
                'description' => 'Uang tunai yang tersedia',
                'account_type' => 'Asset',
                'color' => '#10B981',
                'priority' => 9,
            ],
            [
                'name' => 'Bank BCA',
                'code' => '1-1100',
                'description' => 'Rekening bank di Bank Central Asia',
                'account_type' => 'Asset',
                'color' => '#0066CC',
                'priority' => 9,
            ],
            [
                'name' => 'Bank BNI',
                'code' => '1-1110',
                'description' => 'Rekening bank di Bank Negara Indonesia',
                'account_type' => 'Asset',
                'color' => '#FF6B00',
                'priority' => 9,
            ],
            [
                'name' => 'Bank BRI',
                'code' => '1-1120',
                'description' => 'Rekening bank di Bank Rakyat Indonesia',
                'account_type' => 'Asset',
                'color' => '#003D7A',
                'priority' => 9,
            ],
            [
                'name' => 'Bank Mandiri',
                'code' => '1-1130',
                'description' => 'Rekening bank di Bank Mandiri',
                'account_type' => 'Asset',
                'color' => '#003D79',
                'priority' => 9,
            ],
            [
                'name' => 'Bank BTN',
                'code' => '1-1140',
                'description' => 'Rekening bank di Bank Tabungan Negara',
                'account_type' => 'Asset',
                'color' => '#00A651',
                'priority' => 8,
            ],
            [
                'name' => 'Bank CIMB Niaga',
                'code' => '1-1150',
                'description' => 'Rekening bank di Bank CIMB Niaga',
                'account_type' => 'Asset',
                'color' => '#C8102E',
                'priority' => 8,
            ],
            [
                'name' => 'E-Wallet GoPay',
                'code' => '1-1200',
                'description' => 'Saldo GoPay untuk transaksi digital',
                'account_type' => 'Asset',
                'color' => '#00AA13',
                'priority' => 7,
            ],
            [
                'name' => 'E-Wallet OVO',
                'code' => '1-1210',
                'description' => 'Saldo OVO untuk transaksi digital',
                'account_type' => 'Asset',
                'color' => '#4C3494',
                'priority' => 7,
            ],
            [
                'name' => 'E-Wallet Dana',
                'code' => '1-1220',
                'description' => 'Saldo Dana untuk transaksi digital',
                'account_type' => 'Asset',
                'color' => '#118EEA',
                'priority' => 7,
            ],
            [
                'name' => 'Piutang Usaha',
                'code' => '1-2000',
                'description' => 'Tagihan kepada pelanggan',
                'account_type' => 'Asset',
                'color' => '#F59E0B',
                'priority' => 6,
            ],
            [
                'name' => 'Persediaan Barang',
                'code' => '1-3000',
                'description' => 'Nilai persediaan barang dagangan',
                'account_type' => 'Asset',
                'color' => '#8B5CF6',
                'priority' => 5,
            ],
            [
                'name' => 'Aset Tetap',
                'code' => '1-4000',
                'description' => 'Peralatan, kendaraan, dan aset tetap lainnya',
                'account_type' => 'Asset',
                'color' => '#6B7280',
                'priority' => 4,
            ],

            // LIABILITIES (Kewajiban)
            [
                'name' => 'Utang Usaha',
                'code' => '2-1000',
                'description' => 'Utang kepada supplier',
                'account_type' => 'Liability',
                'color' => '#EF4444',
                'priority' => 6,
            ],
            [
                'name' => 'Utang Bank',
                'code' => '2-2000',
                'description' => 'Pinjaman dari bank',
                'account_type' => 'Liability',
                'color' => '#DC2626',
                'priority' => 6,
            ],
            [
                'name' => 'Utang Pajak',
                'code' => '2-3000',
                'description' => 'Kewajiban pajak yang belum dibayar',
                'account_type' => 'Liability',
                'color' => '#F87171',
                'priority' => 7,
            ],
            [
                'name' => 'Utang Gaji',
                'code' => '2-4000',
                'description' => 'Gaji karyawan yang belum dibayar',
                'account_type' => 'Liability',
                'color' => '#FCA5A5',
                'priority' => 6,
            ],

            // EQUITY (Modal)
            [
                'name' => 'Modal Pemilik',
                'code' => '3-1000',
                'description' => 'Modal yang disetor pemilik',
                'account_type' => 'Equity',
                'color' => '#3B82F6',
                'priority' => 5,
            ],
            [
                'name' => 'Laba Ditahan',
                'code' => '3-2000',
                'description' => 'Akumulasi laba yang tidak dibagikan',
                'account_type' => 'Equity',
                'color' => '#60A5FA',
                'priority' => 5,
            ],
            [
                'name' => 'Prive',
                'code' => '3-3000',
                'description' => 'Pengambilan pribadi pemilik',
                'account_type' => 'Equity',
                'color' => '#93C5FD',
                'priority' => 5,
            ],

            // REVENUE (Pendapatan)
            [
                'name' => 'Pendapatan Penjualan',
                'code' => '4-1000',
                'description' => 'Pendapatan dari penjualan barang/jasa',
                'account_type' => 'Revenue',
                'color' => '#059669',
                'priority' => 8,
            ],
            [
                'name' => 'Pendapatan Jasa',
                'code' => '4-2000',
                'description' => 'Pendapatan dari penyediaan jasa',
                'account_type' => 'Revenue',
                'color' => '#10B981',
                'priority' => 7,
            ],
            [
                'name' => 'Pendapatan Bunga',
                'code' => '4-3000',
                'description' => 'Pendapatan bunga dari bank',
                'account_type' => 'Revenue',
                'color' => '#34D399',
                'priority' => 6,
            ],
            [
                'name' => 'Pendapatan Lain-lain',
                'code' => '4-9000',
                'description' => 'Pendapatan di luar operasional utama',
                'account_type' => 'Revenue',
                'color' => '#6EE7B7',
                'priority' => 5,
            ],

            // EXPENSES (Beban)
            [
                'name' => 'Beban Gaji',
                'code' => '5-1000',
                'description' => 'Beban gaji karyawan',
                'account_type' => 'Expense',
                'color' => '#F97316',
                'priority' => 7,
            ],
            [
                'name' => 'Beban Sewa',
                'code' => '5-2000',
                'description' => 'Beban sewa tempat usaha',
                'account_type' => 'Expense',
                'color' => '#FB923C',
                'priority' => 6,
            ],
            [
                'name' => 'Beban Listrik & Air',
                'code' => '5-3000',
                'description' => 'Beban utilitas (listrik, air, telepon)',
                'account_type' => 'Expense',
                'color' => '#FDBA74',
                'priority' => 6,
            ],
            [
                'name' => 'Beban Transportasi',
                'code' => '5-4000',
                'description' => 'Beban transportasi dan pengiriman',
                'account_type' => 'Expense',
                'color' => '#FED7AA',
                'priority' => 5,
            ],
            [
                'name' => 'Beban ATK',
                'code' => '5-5000',
                'description' => 'Beban alat tulis kantor',
                'account_type' => 'Expense',
                'color' => '#FCA5A5',
                'priority' => 4,
            ],
            [
                'name' => 'Beban Pemeliharaan',
                'code' => '5-6000',
                'description' => 'Beban perawatan aset dan perbaikan',
                'account_type' => 'Expense',
                'color' => '#FBBF24',
                'priority' => 5,
            ],
            [
                'name' => 'Beban Pajak',
                'code' => '5-7000',
                'description' => 'Beban pajak penghasilan',
                'account_type' => 'Expense',
                'color' => '#DC2626',
                'priority' => 7,
            ],
            [
                'name' => 'Beban Bunga',
                'code' => '5-8000',
                'description' => 'Beban bunga pinjaman',
                'account_type' => 'Expense',
                'color' => '#EF4444',
                'priority' => 6,
            ],
            [
                'name' => 'Beban Administrasi Bank',
                'code' => '5-8100',
                'description' => 'Biaya administrasi dan layanan bank',
                'account_type' => 'Expense',
                'color' => '#F87171',
                'priority' => 8,
            ],
            [
                'name' => 'Beban Lain-lain',
                'code' => '5-9000',
                'description' => 'Beban operasional lainnya',
                'account_type' => 'Expense',
                'color' => '#FCA5A5',
                'priority' => 4,
            ],
        ];

        // Generate accounts untuk setiap company
        foreach ($companies as $company) {
            foreach ($standardAccounts as $account) {
                $accounts[] = [
                    'uuid' => Str::uuid(),
                    'name' => $account['name'],
                    'code' => $company->id . '-' . $account['code'],
                    'company_id' => $company->id,
                    'description' => $account['description'],
                    'account_type' => $account['account_type'],
                    'color' => $account['color'],
                    'priority' => $account['priority'],
                    'is_active' => true,
                    'created_at' => Carbon::now()->subMonths(rand(1, 6)),
                    'updated_at' => Carbon::now(),
                    'deleted_at' => null,
                ];
            }
        }

        if (!empty($accounts)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($accounts, 100);
            foreach ($chunks as $chunk) {
                DB::table('accounts')->insert($chunk);
            }
            
            $this->command->info('✅ Accounts seeded successfully!');
            $this->command->info("   Total accounts: " . count($accounts));
            $this->command->info("   Companies: " . $companies->count());
            $this->command->info("   Accounts per company: " . count($standardAccounts));
            
            $this->command->newLine();
            $this->command->info('📊 Account Types Distribution:');
            
            $accountTypes = collect($standardAccounts)->groupBy('account_type');
            foreach ($accountTypes as $type => $items) {
                $count = $items->count();
                $this->command->info("   - {$type}: {$count} accounts");
            }
            
            $this->command->newLine();
            $this->command->info('🏦 Bank Accounts: 7 (BCA, BNI, BRI, Mandiri, BTN, CIMB, Cash)');
            $this->command->info('💳 E-Wallets: 3 (GoPay, OVO, Dana)');
        } else {
            $this->command->warn('⚠️  No accounts created. Check if companies exist.');
        }
    }
}