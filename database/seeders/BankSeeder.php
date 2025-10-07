<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
                'code' => 'MANDIRI',
                'name' => 'Bank Mandiri',
                'logo' => null, // You can add logo path later
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'BCA',
                'name' => 'Bank Central Asia (BCA)',
                'logo' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'BNI',
                'name' => 'Bank Negara Indonesia (BNI)',
                'logo' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'BRI',
                'name' => 'Bank Rakyat Indonesia (BRI)',
                'logo' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'BTN',
                'name' => 'Bank Tabungan Negara (BTN)',
                'logo' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'CIMB',
                'name' => 'CIMB Niaga',
                'logo' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('banks')->insert($banks);

        $this->command->info('✅ Banks seeded successfully!');
    }
}