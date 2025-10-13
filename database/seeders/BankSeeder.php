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
        // MAJOR INDONESIAN BANKS ONLY
        // 6 bank utama yang paling umum digunakan
        // ============================================
        
        $standardBanks = [
            [
                'code' => '008',
                'name' => 'Bank Mandiri',
                'slug' => 'mandiri',
                'logo' => 'banks/mandiri.png',
            ],
            [
                'code' => '014',
                'name' => 'Bank BCA',
                'slug' => 'bca',
                'logo' => 'banks/bca.png',
            ],
            [
                'code' => '002',
                'name' => 'Bank BRI',
                'slug' => 'bri',
                'logo' => 'banks/bri.png',
            ],
            [
                'code' => '009',
                'name' => 'Bank BNI',
                'slug' => 'bni',
                'logo' => 'banks/bni.png',
            ],
            [
                'code' => '200',
                'name' => 'Bank BTN',
                'slug' => 'btn',
                'logo' => 'banks/btn.png',
            ],
            [
                'code' => '022',
                'name' => 'Bank CIMB Niaga',
                'slug' => 'cimb',
                'logo' => 'banks/cimb.png',
            ],
        ];

        // Generate banks untuk setiap company
        foreach ($companies as $company) {
            foreach ($standardBanks as $bank) {
                $banks[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company->id,
                    'code' => $bank['code'] . '-' . $company->id, // ✅ TAMBAH company_id
                    'slug' => $bank['slug'] . '-c' . $company->id, // ✅ TAMBAH company_id
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
            DB::table('banks')->insert($banks);
            
            $this->command->info('✅ Banks seeded successfully!');
            $this->command->info("   Total banks: " . count($banks));
            $this->command->info("   Companies: " . $companies->count());
            $this->command->info("   Banks per company: " . count($standardBanks));
            
            $this->command->newLine();
            $this->command->info('🏦 Major Banks:');
            $this->command->info('   1. Bank Mandiri (008)');
            $this->command->info('   2. Bank BCA (014)');
            $this->command->info('   3. Bank BRI (002)');
            $this->command->info('   4. Bank BNI (009)');
            $this->command->info('   5. Bank BTN (200)');
            $this->command->info('   6. Bank CIMB Niaga (022)');
        } else {
            $this->command->warn('⚠️  No banks created. Check if companies exist.');
        }
    }
}