<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SubCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = DB::table('categories')->get();
        
        if ($categories->isEmpty()) {
            $this->command->warn('⚠️  No categories found! Please run CategorySeeder first.');
            return;
        }

        $now = Carbon::now();
        $allSubCategories = [];

        $subCategoryMapping = [
            // OUTLET
            'Apotek' => [
                ['name' => 'Apotek Kimia Farma', 'description' => 'Transaksi di Apotek Kimia Farma', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'Apotek K-24', 'description' => 'Transaksi di Apotek K-24', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'Guardian', 'description' => 'Transaksi di Guardian', 'priority' => 8, 'sort_order' => 3],
                ['name' => 'Apotek Lainnya', 'description' => 'Apotek independen lainnya', 'priority' => 5, 'sort_order' => 99],
            ],
            
            'Minimarket' => [
                ['name' => 'Alfamart', 'description' => 'Belanja di Alfamart', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'Indomaret', 'description' => 'Belanja di Indomaret', 'priority' => 10, 'sort_order' => 2],
            ],
            
            'Restoran' => [
                ['name' => 'McDonald\'s', 'description' => 'Makan di McDonald\'s', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'KFC', 'description' => 'Makan di KFC', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'Restoran Lainnya', 'description' => 'Restoran independen', 'priority' => 5, 'sort_order' => 99],
            ],
            
            'Cafe' => [
                ['name' => 'Starbucks', 'description' => 'Kopi di Starbucks', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'Kopi Kenangan', 'description' => 'Kopi di Kopi Kenangan', 'priority' => 9, 'sort_order' => 2],
            ],
            
            'SPBU' => [
                ['name' => 'Pertamina', 'description' => 'SPBU Pertamina', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'Shell', 'description' => 'SPBU Shell', 'priority' => 9, 'sort_order' => 2],
            ],
            
            // TRANSFER
            'Transfer Internal' => [
                ['name' => 'Transfer Sesama Rekening', 'description' => 'Transfer antar rekening sendiri', 'priority' => 9, 'sort_order' => 1],
            ],
            
            'Transfer Antar Bank' => [
                ['name' => 'BI Fast', 'description' => 'Transfer real-time antar bank', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'SKN', 'description' => 'Sistem Kliring Nasional', 'priority' => 8, 'sort_order' => 2],
            ],
            
            // E-WALLET
            'GoPay' => [
                ['name' => 'GoFood', 'description' => 'Pembelian makanan via GoFood', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'GoRide', 'description' => 'Pembayaran GoRide/GoCar', 'priority' => 9, 'sort_order' => 2],
            ],
            
            'OVO' => [
                ['name' => 'Grab via OVO', 'description' => 'Pembayaran Grab menggunakan OVO', 'priority' => 10, 'sort_order' => 1],
            ],
            
            // PEMBAYARAN
            'Listrik' => [
                ['name' => 'PLN Token', 'description' => 'Pembelian token listrik prabayar', 'priority' => 10, 'sort_order' => 1],
                ['name' => 'PLN Pascabayar', 'description' => 'Pembayaran tagihan listrik pascabayar', 'priority' => 10, 'sort_order' => 2],
            ],
            
            'Pulsa' => [
                ['name' => 'Telkomsel', 'description' => 'Pulsa dan paket Telkomsel', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'Indosat', 'description' => 'Pulsa dan paket Indosat', 'priority' => 9, 'sort_order' => 2],
                ['name' => 'XL Axiata', 'description' => 'Pulsa dan paket XL', 'priority' => 9, 'sort_order' => 3],
            ],
            
            // GAJI
            'Gaji Pokok' => [
                ['name' => 'Gaji Bulanan', 'description' => 'Gaji pokok bulanan karyawan', 'priority' => 10, 'sort_order' => 1],
            ],
            
            'Tunjangan Tetap' => [
                ['name' => 'Tunjangan Transportasi', 'description' => 'Tunjangan transport karyawan', 'priority' => 9, 'sort_order' => 1],
                ['name' => 'Tunjangan Makan', 'description' => 'Tunjangan makan karyawan', 'priority' => 9, 'sort_order' => 2],
            ],
            
            // LAIN-LAIN
            'Tidak Terkategori' => [
                ['name' => 'Belum Dikategorikan', 'description' => 'Transaksi yang belum dikategorikan', 'priority' => 5, 'sort_order' => 1],
            ],
        ];

        foreach ($categories as $category) {
            $categoryName = $category->name;
            
            if (isset($subCategoryMapping[$categoryName])) {
                foreach ($subCategoryMapping[$categoryName] as $subCategory) {
                    $allSubCategories[] = [
                        'uuid' => Str::uuid(),
                        'company_id' => $category->company_id, // ✅ TAMBAHKAN INI!
                        'category_id' => $category->id,
                        'name' => $subCategory['name'],
                        'description' => $subCategory['description'],
                        'priority' => $subCategory['priority'],
                        'sort_order' => $subCategory['sort_order'],
                        'created_at' => $now->copy()->subDays(rand(30, 180)),
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ];
                }
            }
        }

        if (!empty($allSubCategories)) {
            $chunks = array_chunk($allSubCategories, 100);
            foreach ($chunks as $chunk) {
                DB::table('sub_categories')->insert($chunk);
            }
            
            $this->command->info('✅ Sub-categories seeded successfully!');
            $this->command->info("   Total sub-categories: " . count($allSubCategories));
        } else {
            $this->command->warn('⚠️  No sub-categories created.');
        }
    }
}