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
     */
    public function run(): void
    {
        $now = Carbon::now();

        $banks = [
            [
                'code' => '008',
                'slug' => 'mandiri',
                'name' => 'Bank Mandiri',
                'logo' => null, // You can add logo path later
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '014',
                'slug' => 'bca',
                'name' => 'Bank Central Asia (BCA)',
                'logo' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '009',
                'slug' => 'bni',
                'name' => 'Bank Negara Indonesia (BNI)',
                'logo' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '002',
                'slug' => 'bri',
                'name' => 'Bank Rakyat Indonesia (BRI)',
                'logo' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '200',
                'slug' => 'btn',
                'name' => 'Bank Tabungan Negara (BTN)',
                'logo' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => '022',
                'slug' => 'cimb',
                'name' => 'CIMB Niaga',
                'logo' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Check if banks table is empty
        if (DB::table('banks')->count() === 0) {
            DB::table('banks')->insert($banks);
            $this->command->info('✅ Banks seeded successfully!');
            $this->command->info('📊 Total banks inserted: ' . count($banks));
        } else {
            $this->command->warn('⚠️  Banks table is not empty. Skipping seed...');
            
            // Optional: Update existing records
            if ($this->command->confirm('Do you want to update existing banks?', false)) {
                foreach ($banks as $bank) {
                    DB::table('banks')
                        ->updateOrInsert(
                            ['slug' => $bank['slug']], // Match by slug
                            [
                                'code' => $bank['code'],
                                'name' => $bank['name'],
                                'is_active' => $bank['is_active'],
                                'updated_at' => $now,
                            ]
                        );
                }
                $this->command->info('✅ Banks updated successfully!');
            }
        }

        // Display seeded banks
        $this->displayBanks();
    }

    /**
     * Display seeded banks in table format
     */
    private function displayBanks(): void
    {
        $banks = DB::table('banks')
            ->select('id', 'code', 'slug', 'name', 'is_active')
            ->orderBy('id')
            ->get();

        if ($banks->isEmpty()) {
            $this->command->warn('No banks found in database.');
            return;
        }

        $this->command->newLine();
        $this->command->info('📋 Current Banks in Database:');
        $this->command->table(
            ['ID', 'Code', 'Slug', 'Name', 'Active'],
            $banks->map(function ($bank) {
                return [
                    $bank->id,
                    $bank->code,
                    $bank->slug,
                    $bank->name,
                    $bank->is_active ? '✓' : '✗',
                ];
            })
        );
    }
}