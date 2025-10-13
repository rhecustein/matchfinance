<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BankSeeder extends Seeder
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

        $banks = [];
        $now = Carbon::now();

        // ============================================
        // STANDARD INDONESIAN BANKS
        // Bank list berdasarkan kode bank Indonesia
        // ============================================
        
        $standardBanks = [
            // BANK BUMN (BUKU 4)
            [
                'code' => '002',
                'name' => 'Bank BRI',
                'slug' => 'bank-bri',
                'logo' => 'banks/bri.png',
            ],
            [
                'code' => '008',
                'name' => 'Bank Mandiri',
                'slug' => 'bank-mandiri',
                'logo' => 'banks/mandiri.png',
            ],
            [
                'code' => '009',
                'name' => 'Bank BNI',
                'slug' => 'bank-bni',
                'logo' => 'banks/bni.png',
            ],
            [
                'code' => '200',
                'name' => 'Bank BTN',
                'slug' => 'bank-btn',
                'logo' => 'banks/btn.png',
            ],

            // BANK SWASTA NASIONAL (BUKU 4)
            [
                'code' => '014',
                'name' => 'Bank BCA',
                'slug' => 'bank-bca',
                'logo' => 'banks/bca.png',
            ],
            [
                'code' => '022',
                'name' => 'Bank CIMB Niaga',
                'slug' => 'bank-cimb-niaga',
                'logo' => 'banks/cimb.png',
            ],
            [
                'code' => '011',
                'name' => 'Bank Danamon',
                'slug' => 'bank-danamon',
                'logo' => 'banks/danamon.png',
            ],
            [
                'code' => '213',
                'name' => 'Bank BTPN',
                'slug' => 'bank-btpn',
                'logo' => 'banks/btpn.png',
            ],
            [
                'code' => '013',
                'name' => 'Bank Permata',
                'slug' => 'bank-permata',
                'logo' => 'banks/permata.png',
            ],
            [
                'code' => '426',
                'name' => 'Bank Mega',
                'slug' => 'bank-mega',
                'logo' => 'banks/mega.png',
            ],
            [
                'code' => '016',
                'name' => 'Bank Maybank Indonesia',
                'slug' => 'bank-maybank',
                'logo' => 'banks/maybank.png',
            ],
            [
                'code' => '019',
                'name' => 'Bank Panin',
                'slug' => 'bank-panin',
                'logo' => 'banks/panin.png',
            ],
            [
                'code' => '023',
                'name' => 'Bank UOB Indonesia',
                'slug' => 'bank-uob',
                'logo' => 'banks/uob.png',
            ],
            [
                'code' => '028',
                'name' => 'Bank OCBC NISP',
                'slug' => 'bank-ocbc-nisp',
                'logo' => 'banks/ocbc.png',
            ],

            // BANK DIGITAL
            [
                'code' => '490',
                'name' => 'Bank Neo Commerce (BNC)',
                'slug' => 'bank-neo-commerce',
                'logo' => 'banks/neo.png',
            ],
            [
                'code' => '501',
                'name' => 'Bank Jago',
                'slug' => 'bank-jago',
                'logo' => 'banks/jago.png',
            ],
            [
                'code' => '213',
                'name' => 'Bank Jenius (BTPN)',
                'slug' => 'bank-jenius',
                'logo' => 'banks/jenius.png',
            ],
            [
                'code' => '110',
                'name' => 'Bank Seabank Indonesia',
                'slug' => 'bank-seabank',
                'logo' => 'banks/seabank.png',
            ],

            // BANK SYARIAH
            [
                'code' => '451',
                'name' => 'Bank Syariah Indonesia (BSI)',
                'slug' => 'bank-bsi',
                'logo' => 'banks/bsi.png',
            ],
            [
                'code' => '422',
                'name' => 'Bank BRI Syariah',
                'slug' => 'bank-bri-syariah',
                'logo' => 'banks/bri-syariah.png',
            ],
            [
                'code' => '506',
                'name' => 'Bank Muamalat',
                'slug' => 'bank-muamalat',
                'logo' => 'banks/muamalat.png',
            ],

            // BANK ASING
            [
                'code' => '031',
                'name' => 'Citibank',
                'slug' => 'citibank',
                'logo' => 'banks/citibank.png',
            ],
            [
                'code' => '032',
                'name' => 'JP Morgan Chase Bank',
                'slug' => 'jpmorgan',
                'logo' => 'banks/jpmorgan.png',
            ],
            [
                'code' => '033',
                'name' => 'Bank of America',
                'slug' => 'bank-of-america',
                'logo' => 'banks/boa.png',
            ],
            [
                'code' => '034',
                'name' => 'HSBC Indonesia',
                'slug' => 'hsbc',
                'logo' => 'banks/hsbc.png',
            ],
            [
                'code' => '037',
                'name' => 'Standard Chartered Bank',
                'slug' => 'standard-chartered',
                'logo' => 'banks/scb.png',
            ],

            // BANK PEMBANGUNAN DAERAH
            [
                'code' => '111',
                'name' => 'Bank DKI',
                'slug' => 'bank-dki',
                'logo' => 'banks/dki.png',
            ],
            [
                'code' => '110',
                'name' => 'Bank BJB',
                'slug' => 'bank-bjb',
                'logo' => 'banks/bjb.png',
            ],
            [
                'code' => '112',
                'name' => 'Bank Jateng',
                'slug' => 'bank-jateng',
                'logo' => 'banks/jateng.png',
            ],
            [
                'code' => '113',
                'name' => 'Bank Jatim',
                'slug' => 'bank-jatim',
                'logo' => 'banks/jatim.png',
            ],

            // E-MONEY / FINTECH
            [
                'code' => 'GOPAY',
                'name' => 'GoPay',
                'slug' => 'gopay',
                'logo' => 'banks/gopay.png',
            ],
            [
                'code' => 'OVO',
                'name' => 'OVO',
                'slug' => 'ovo',
                'logo' => 'banks/ovo.png',
            ],
            [
                'code' => 'DANA',
                'name' => 'DANA',
                'slug' => 'dana',
                'logo' => 'banks/dana.png',
            ],
            [
                'code' => 'SHOPEEPAY',
                'name' => 'ShopeePay',
                'slug' => 'shopeepay',
                'logo' => 'banks/shopeepay.png',
            ],
            [
                'code' => 'LINKAJA',
                'name' => 'LinkAja',
                'slug' => 'linkaja',
                'logo' => 'banks/linkaja.png',
            ],
        ];

        // Generate banks untuk setiap company
        foreach ($companies as $company) {
            foreach ($standardBanks as $index => $bank) {
                $banks[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company->id,
                    'code' => $bank['code'] . '-' . $company->id,
                    'slug' => $bank['slug'] . '-' . $company->id,
                    'name' => $bank['name'],
                    'logo' => $bank['logo'],
                    'is_active' => true,
                    'created_at' => $now->copy()->subDays(rand(30, 180)),
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }
        }

        if (!empty($banks)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($banks, 100);
            foreach ($chunks as $chunk) {
                DB::table('banks')->insert($chunk);
            }
            
            $this->command->info('✅ Banks seeded successfully!');
            $this->command->info("   Total banks: " . count($banks));
            $this->command->info("   Companies: " . $companies->count());
            $this->command->info("   Banks per company: " . count($standardBanks));
            
            $this->command->newLine();
            $this->command->info('🏦 Bank Categories:');
            $this->command->info('   - BUMN Banks: 4 (BRI, Mandiri, BNI, BTN)');
            $this->command->info('   - Private Banks: 10 (BCA, CIMB, Danamon, dll)');
            $this->command->info('   - Digital Banks: 4 (Neo, Jago, Jenius, Seabank)');
            $this->command->info('   - Syariah Banks: 3 (BSI, BRI Syariah, Muamalat)');
            $this->command->info('   - Foreign Banks: 5 (Citibank, HSBC, dll)');
            $this->command->info('   - Regional Banks: 4 (DKI, BJB, Jateng, Jatim)');
            $this->command->info('   - E-Wallets: 5 (GoPay, OVO, DANA, ShopeePay, LinkAja)');
            
            $this->command->newLine();
            $this->command->info('📊 Total: 35 banks per company');
        } else {
            $this->command->warn('⚠️  No banks created. Check if companies exist.');
        }
    }
}