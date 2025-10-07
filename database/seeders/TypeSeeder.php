<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $types = [
            [
                'name' => 'Outlet',
                'description' => 'Transaksi di outlet fisik atau toko',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Transaksi',
                'description' => 'Jenis-jenis transaksi perbankan',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Transfer',
                'description' => 'Transfer dana antar rekening atau bank',
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Pembayaran',
                'description' => 'Pembayaran tagihan dan layanan',
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'E-Commerce',
                'description' => 'Transaksi belanja online',
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('types')->insert($types);

        $this->command->info('✅ Types seeded successfully!');
    }
}