<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KeywordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Get sub category IDs
        $kimiaFarma = DB::table('sub_categories')->where('name', 'Kimia Farma')->first();
        $guardian = DB::table('sub_categories')->where('name', 'Guardian')->first();
        $century = DB::table('sub_categories')->where('name', 'Century Healthcare')->first();
        $indomaret = DB::table('sub_categories')->where('name', 'Indomaret')->first();
        $alfamart = DB::table('sub_categories')->where('name', 'Alfamart')->first();
        $qris = DB::table('sub_categories')->where('name', 'QRIS')->first();
        $gopay = DB::table('sub_categories')->where('name', 'GoPay')->first();
        $ovo = DB::table('sub_categories')->where('name', 'OVO')->first();
        $dana = DB::table('sub_categories')->where('name', 'Dana')->first();
        $shopeepay = DB::table('sub_categories')->where('name', 'ShopeePay')->first();
        $tokopedia = DB::table('sub_categories')->where('name', 'Tokopedia')->first();
        $shopee = DB::table('sub_categories')->where('name', 'Shopee')->first();
        $pertamina = DB::table('sub_categories')->where('name', 'Pertamina')->first();
        $pln = DB::table('sub_categories')->where('name', 'PLN Pascabayar')->first();
        $token = DB::table('sub_categories')->where('name', 'Token PLN')->first();

        $keywords = [
            // Keywords for Kimia Farma
            [
                'sub_category_id' => $kimiaFarma->id,
                'keyword' => 'APOTEK KIMIA FARMA',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => $kimiaFarma->id,
                'keyword' => 'KIMIA FARMA QR',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => $kimiaFarma->id,
                'keyword' => 'KF QR',
                'priority' => 8,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for Guardian
            [
                'sub_category_id' => $guardian->id,
                'keyword' => 'GUARDIAN',
                'priority' => 8,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for Century
            [
                'sub_category_id' => $century->id,
                'keyword' => 'CENTURY',
                'priority' => 8,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for Indomaret
            [
                'sub_category_id' => $indomaret->id,
                'keyword' => 'INDOMARET',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => $indomaret->id,
                'keyword' => 'INDO MARET',
                'priority' => 8,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for Alfamart
            [
                'sub_category_id' => $alfamart->id,
                'keyword' => 'ALFAMART',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => $alfamart->id,
                'keyword' => 'ALFA MART',
                'priority' => 8,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for QRIS
            [
                'sub_category_id' => $qris->id,
                'keyword' => 'QRIS',
                'priority' => 10,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => $qris->id,
                'keyword' => 'QR CODE',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => $qris->id,
                'keyword' => 'PAYMENT QR',
                'priority' => 8,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for GoPay
            [
                'sub_category_id' => $gopay->id,
                'keyword' => 'GOPAY',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => $gopay->id,
                'keyword' => 'GO-PAY',
                'priority' => 8,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for OVO
            [
                'sub_category_id' => $ovo->id,
                'keyword' => 'OVO',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for Dana
            [
                'sub_category_id' => $dana->id,
                'keyword' => 'DANA',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for ShopeePay
            [
                'sub_category_id' => $shopeepay->id,
                'keyword' => 'SHOPEEPAY',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => $shopeepay->id,
                'keyword' => 'SHOPEE PAY',
                'priority' => 8,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for Tokopedia
            [
                'sub_category_id' => $tokopedia->id,
                'keyword' => 'TOKOPEDIA',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => $tokopedia->id,
                'keyword' => 'TOKO PEDIA',
                'priority' => 8,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for Shopee
            [
                'sub_category_id' => $shopee->id,
                'keyword' => 'SHOPEE',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for Pertamina
            [
                'sub_category_id' => $pertamina->id,
                'keyword' => 'PERTAMINA',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => $pertamina->id,
                'keyword' => 'SPBU PERTAMINA',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for PLN
            [
                'sub_category_id' => $pln->id,
                'keyword' => 'PLN PASCABAYAR',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => $pln->id,
                'keyword' => 'TAGIHAN LISTRIK',
                'priority' => 8,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Keywords for Token PLN
            [
                'sub_category_id' => $token->id,
                'keyword' => 'TOKEN LISTRIK',
                'priority' => 9,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => $token->id,
                'keyword' => 'PLN PREPAID',
                'priority' => 8,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => $token->id,
                'keyword' => 'PLN PRABAYAR',
                'priority' => 8,
                'is_regex' => false,
                'case_sensitive' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('keywords')->insert($keywords);

        $this->command->info('✅ Keywords seeded successfully!');
    }
}