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
     * Seeder untuk master data bank Indonesia
     * Banks bersifat global/shared (tidak per company)
     */
    public function run(): void
    {
        $banks = [];
        $now = Carbon::now();

        // ============================================
        // MAJOR INDONESIAN BANKS
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
                'slug' => 'cimb-niaga',
                'logo' => 'banks/cimb.png',
            ],
        ];

        // Buat data untuk setiap bank
        foreach ($standardBanks as $bank) {
            $banks[] = [
                'uuid' => Str::uuid()->toString(),
                'code' => $bank['code'],
                'slug' => $bank['slug'],
                'name' => $bank['name'],
                'logo' => $bank['logo'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];
        }

        // Insert ke database
        if (!empty($banks)) {
            // Cek apakah sudah ada data
            $existingCount = DB::table('banks')->count();
            
            if ($existingCount > 0) {
                $this->command->warn('⚠️  Banks table already has data. Skipping seed.');
                $this->command->info("   Existing banks: {$existingCount}");
                return;
            }

            DB::table('banks')->insert($banks);
            
            $this->command->info('✅ Banks seeded successfully!');
            $this->command->info("   Total banks created: " . count($banks));
            
            $this->command->newLine();
            $this->command->info('🏦 Major Indonesian Banks:');
            foreach ($standardBanks as $index => $bank) {
                $num = $index + 1;
                $this->command->info("   {$num}. {$bank['name']} ({$bank['code']})");
            }
        }
    }
}