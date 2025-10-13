<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AccountKeywordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     * - Accounts table sudah terisi (AccountSeeder)
     */
    public function run(): void
    {
        // Ambil companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('⚠️  No companies found! Please run CompanySeeder first.');
            return;
        }

        // Ambil accounts
        $accounts = DB::table('accounts')->get();
        
        if ($accounts->isEmpty()) {
            $this->command->warn('⚠️  No accounts found! Please run AccountSeeder first.');
            return;
        }

        $keywords = [];
        $now = Carbon::now();

        // ============================================
        // MAPPING KEYWORDS PER ACCOUNT TYPE
        // ============================================
        
        foreach ($companies as $company) {
            $companyAccounts = $accounts->where('company_id', $company->id);
            
            // ========================================
            // BANK ACCOUNTS - Keywords untuk identifikasi transaksi bank
            // ========================================
            
            // Bank BCA
            $bcaAccount = $companyAccounts->where('name', 'Bank BCA')->first();
            if ($bcaAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $bcaAccount->id,
                        'keyword' => 'BCA',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transaksi yang mengandung kata BCA',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $bcaAccount->id,
                        'keyword' => '0998',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Kode cabang BCA 0998',
                        'priority' => 8,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $bcaAccount->id,
                        'keyword' => 'KR OTOMATIS',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transfer otomatis BCA',
                        'priority' => 7,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // Bank BNI
            $bniAccount = $companyAccounts->where('name', 'Bank BNI')->first();
            if ($bniAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $bniAccount->id,
                        'keyword' => 'BNI',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transaksi yang mengandung kata BNI',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $bniAccount->id,
                        'keyword' => 'ECHANNEL',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transfer via BNI E-Channel',
                        'priority' => 8,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $bniAccount->id,
                        'keyword' => 'TRF/PAY',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transfer atau pembayaran BNI',
                        'priority' => 7,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // Bank BRI
            $briAccount = $companyAccounts->where('name', 'Bank BRI')->first();
            if ($briAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $briAccount->id,
                        'keyword' => 'BRI',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transaksi yang mengandung kata BRI',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $briAccount->id,
                        'keyword' => 'BRIMCRDT',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'BRI Mobile Credit/Debit',
                        'priority' => 8,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $briAccount->id,
                        'keyword' => 'OnUs',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transfer internal BRI (OnUs)',
                        'priority' => 7,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $briAccount->id,
                        'keyword' => 'CMSPOOL',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'CMS Pool transfer BRI',
                        'priority' => 7,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // Bank Mandiri
            $mandiriAccount = $companyAccounts->where('name', 'Bank Mandiri')->first();
            if ($mandiriAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $mandiriAccount->id,
                        'keyword' => 'Mandiri',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transaksi yang mengandung kata Mandiri',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $mandiriAccount->id,
                        'keyword' => 'MCM',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Mandiri Cash Management',
                        'priority' => 8,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $mandiriAccount->id,
                        'keyword' => 'InhouseTrf',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transfer internal Mandiri',
                        'priority' => 7,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // Bank BTN
            $btnAccount = $companyAccounts->where('name', 'Bank BTN')->first();
            if ($btnAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $btnAccount->id,
                        'keyword' => 'BTN',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transaksi yang mengandung kata BTN',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $btnAccount->id,
                        'keyword' => 'SETTLEMENT EDCMTI',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Settlement EDC BTN',
                        'priority' => 8,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // Bank CIMB
            $cimbAccount = $companyAccounts->where('name', 'Bank CIMB Niaga')->first();
            if ($cimbAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $cimbAccount->id,
                        'keyword' => 'CIMB',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transaksi yang mengandung kata CIMB',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $cimbAccount->id,
                        'keyword' => 'ATM Prima',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transaksi ATM Prima CIMB',
                        'priority' => 8,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // ========================================
            // E-WALLETS - Keywords untuk transaksi digital
            // ========================================
            
            // GoPay
            $gopayAccount = $companyAccounts->where('name', 'E-Wallet GoPay')->first();
            if ($gopayAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $gopayAccount->id,
                        'keyword' => 'GOPAY',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transaksi GoPay',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $gopayAccount->id,
                        'keyword' => 'GOJEK',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transaksi Gojek/GoPay',
                        'priority' => 8,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // OVO
            $ovoAccount = $companyAccounts->where('name', 'E-Wallet OVO')->first();
            if ($ovoAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $ovoAccount->id,
                        'keyword' => 'OVO',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transaksi OVO',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // Dana
            $danaAccount = $companyAccounts->where('name', 'E-Wallet Dana')->first();
            if ($danaAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $danaAccount->id,
                        'keyword' => 'DANA',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Transaksi Dana',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // ========================================
            // REVENUE ACCOUNTS - Keywords untuk pendapatan
            // ========================================
            
            $revenueAccount = $companyAccounts->where('name', 'Pendapatan Penjualan')->first();
            if ($revenueAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $revenueAccount->id,
                        'keyword' => 'APOTEK KIMIA FARMA',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Penjualan dari Apotek Kimia Farma',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $revenueAccount->id,
                        'keyword' => 'KF',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'starts_with',
                        'pattern_description' => 'Transaksi yang dimulai dengan KF (Kimia Farma)',
                        'priority' => 8,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $revenueAccount->id,
                        'keyword' => 'SETTLEMENT',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Settlement penjualan EDC',
                        'priority' => 8,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // Pendapatan Bunga
            $interestAccount = $companyAccounts->where('name', 'Pendapatan Bunga')->first();
            if ($interestAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $interestAccount->id,
                        'keyword' => 'BUNGA',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Pendapatan bunga bank',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $interestAccount->id,
                        'keyword' => 'INTEREST',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Interest income',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // ========================================
            // EXPENSE ACCOUNTS - Keywords untuk beban
            // ========================================
            
            // Beban Admin Bank
            $adminBankAccount = $companyAccounts->where('name', 'Beban Administrasi Bank')->first();
            if ($adminBankAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $adminBankAccount->id,
                        'keyword' => 'BIAYA ADM',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Biaya administrasi bank',
                        'priority' => 10,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $adminBankAccount->id,
                        'keyword' => 'ADMIN FEE',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Admin fee bank',
                        'priority' => 10,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $adminBankAccount->id,
                        'keyword' => 'Biaya Statement',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Biaya cetak statement bank',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // Beban Pajak
            $pajakAccount = $companyAccounts->where('name', 'Beban Pajak')->first();
            if ($pajakAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $pajakAccount->id,
                        'keyword' => 'PAJAK',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Pembayaran pajak',
                        'priority' => 10,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $pajakAccount->id,
                        'keyword' => 'TAX',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Tax payment',
                        'priority' => 10,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $pajakAccount->id,
                        'keyword' => 'PPH',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Pajak Penghasilan',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $pajakAccount->id,
                        'keyword' => 'PAJAK BUNGA',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Pajak atas bunga deposito/tabungan',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // Beban Gaji
            $gajiAccount = $companyAccounts->where('name', 'Beban Gaji')->first();
            if ($gajiAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $gajiAccount->id,
                        'keyword' => 'GAJI',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Pembayaran gaji karyawan',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $gajiAccount->id,
                        'keyword' => 'SALARY',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Salary payment',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $gajiAccount->id,
                        'keyword' => 'PAYROLL',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Payroll transfer',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }

            // Beban Listrik & Air
            $utilitasAccount = $companyAccounts->where('name', 'Beban Listrik & Air')->first();
            if ($utilitasAccount) {
                $keywords = array_merge($keywords, [
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $utilitasAccount->id,
                        'keyword' => 'PLN',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Pembayaran listrik PLN',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $utilitasAccount->id,
                        'keyword' => 'PDAM',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Pembayaran air PDAM',
                        'priority' => 9,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'company_id' => $company->id,
                        'account_id' => $utilitasAccount->id,
                        'keyword' => 'TELKOM',
                        'is_regex' => false,
                        'case_sensitive' => false,
                        'match_type' => 'contains',
                        'pattern_description' => 'Pembayaran telepon/internet Telkom',
                        'priority' => 8,
                        'is_active' => true,
                        'match_count' => 0,
                        'last_matched_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ]);
            }
        }

        if (!empty($keywords)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($keywords, 100);
            foreach ($chunks as $chunk) {
                DB::table('account_keywords')->insert($chunk);
            }
            
            $this->command->info('✅ Account keywords seeded successfully!');
            $this->command->info("   Total keywords: " . count($keywords));
            $this->command->info("   Companies: " . $companies->count());
            
            $keywordsPerCompany = count($keywords) / $companies->count();
            $this->command->info("   Keywords per company: ~" . round($keywordsPerCompany));
            
            $this->command->newLine();
            $this->command->info('🔍 Keyword Categories:');
            $this->command->info('   - Bank identifiers (BCA, BNI, BRI, Mandiri, BTN, CIMB)');
            $this->command->info('   - E-Wallet identifiers (GoPay, OVO, Dana)');
            $this->command->info('   - Revenue keywords (KF, SETTLEMENT, APOTEK)');
            $this->command->info('   - Expense keywords (PAJAK, GAJI, PLN, ADMIN)');
            
            $this->command->newLine();
            $this->command->info('⚡ High Priority Keywords (9-10):');
            $this->command->info('   - Bank names and admin fees');
            $this->command->info('   - Tax and salary payments');
            $this->command->info('   - E-wallet transactions');
        } else {
            $this->command->warn('⚠️  No keywords created. Check if companies and accounts exist.');
        }
    }
}