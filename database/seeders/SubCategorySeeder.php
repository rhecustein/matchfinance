<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Get category IDs
        $apotekCat = DB::table('categories')->where('name', 'Apotek')->first();
        $minimarketCat = DB::table('categories')->where('name', 'Minimarket')->first();
        $restoranCat = DB::table('categories')->where('name', 'Restoran & F&B')->first();
        $spbuCat = DB::table('categories')->where('name', 'SPBU')->first();
        $qrcodeCat = DB::table('categories')->where('name', 'QR Code')->first();
        $ewalletCat = DB::table('categories')->where('name', 'E-Wallet')->first();
        $edcCat = DB::table('categories')->where('name', 'EDC/Debit')->first();
        $transferAntarCat = DB::table('categories')->where('name', 'Transfer Antar Bank')->first();
        $transferSesamaCat = DB::table('categories')->where('name', 'Transfer Sesama Bank')->first();
        $listrikCat = DB::table('categories')->where('name', 'Tagihan Listrik')->first();
        $pulsaCat = DB::table('categories')->where('name', 'Pulsa & Paket Data')->first();
        $streamingCat = DB::table('categories')->where('name', 'Streaming & Subscription')->first();
        $marketplaceCat = DB::table('categories')->where('name', 'Marketplace')->first();

        $subCategories = [
            // Sub Categories for Apotek
            [
                'category_id' => $apotekCat->id,
                'name' => 'Kimia Farma',
                'description' => 'Pembelian di Apotek Kimia Farma',
                'priority' => 8,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $apotekCat->id,
                'name' => 'Guardian',
                'description' => 'Pembelian di Guardian',
                'priority' => 7,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $apotekCat->id,
                'name' => 'Century Healthcare',
                'description' => 'Pembelian di Century',
                'priority' => 7,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sub Categories for Minimarket
            [
                'category_id' => $minimarketCat->id,
                'name' => 'Indomaret',
                'description' => 'Belanja di Indomaret',
                'priority' => 8,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $minimarketCat->id,
                'name' => 'Alfamart',
                'description' => 'Belanja di Alfamart',
                'priority' => 8,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $minimarketCat->id,
                'name' => 'Alfamidi',
                'description' => 'Belanja di Alfamidi',
                'priority' => 7,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sub Categories for Restoran & F&B
            [
                'category_id' => $restoranCat->id,
                'name' => 'Fast Food',
                'description' => 'McDonald, KFC, dll',
                'priority' => 6,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $restoranCat->id,
                'name' => 'Cafe & Coffee Shop',
                'description' => 'Starbucks, Kopi Kenangan, dll',
                'priority' => 6,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $restoranCat->id,
                'name' => 'Food Delivery',
                'description' => 'GoFood, GrabFood, dll',
                'priority' => 7,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sub Categories for SPBU
            [
                'category_id' => $spbuCat->id,
                'name' => 'Pertamina',
                'description' => 'SPBU Pertamina',
                'priority' => 8,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $spbuCat->id,
                'name' => 'Shell',
                'description' => 'SPBU Shell',
                'priority' => 7,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sub Categories for QR Code
            [
                'category_id' => $qrcodeCat->id,
                'name' => 'QRIS',
                'description' => 'Pembayaran dengan QRIS',
                'priority' => 9,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $qrcodeCat->id,
                'name' => 'Merchant QR',
                'description' => 'QR khusus merchant',
                'priority' => 8,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sub Categories for E-Wallet
            [
                'category_id' => $ewalletCat->id,
                'name' => 'GoPay',
                'description' => 'Top-up atau transaksi GoPay',
                'priority' => 8,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $ewalletCat->id,
                'name' => 'OVO',
                'description' => 'Top-up atau transaksi OVO',
                'priority' => 8,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $ewalletCat->id,
                'name' => 'Dana',
                'description' => 'Top-up atau transaksi Dana',
                'priority' => 8,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $ewalletCat->id,
                'name' => 'ShopeePay',
                'description' => 'Top-up atau transaksi ShopeePay',
                'priority' => 8,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sub Categories for EDC/Debit
            [
                'category_id' => $edcCat->id,
                'name' => 'EDC Payment',
                'description' => 'Pembayaran dengan mesin EDC',
                'priority' => 7,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sub Categories for Transfer Antar Bank
            [
                'category_id' => $transferAntarCat->id,
                'name' => 'Transfer Online',
                'description' => 'Transfer online antar bank',
                'priority' => 6,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $transferAntarCat->id,
                'name' => 'Transfer ATM',
                'description' => 'Transfer via ATM antar bank',
                'priority' => 6,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sub Categories for Transfer Sesama Bank
            [
                'category_id' => $transferSesamaCat->id,
                'name' => 'Transfer Online',
                'description' => 'Transfer online sesama bank',
                'priority' => 6,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sub Categories for Tagihan Listrik
            [
                'category_id' => $listrikCat->id,
                'name' => 'PLN Pascabayar',
                'description' => 'Bayar tagihan listrik pascabayar',
                'priority' => 8,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $listrikCat->id,
                'name' => 'Token PLN',
                'description' => 'Beli token listrik prabayar',
                'priority' => 8,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sub Categories for Pulsa & Paket Data
            [
                'category_id' => $pulsaCat->id,
                'name' => 'Pulsa',
                'description' => 'Pembelian pulsa',
                'priority' => 7,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $pulsaCat->id,
                'name' => 'Paket Data',
                'description' => 'Pembelian paket internet',
                'priority' => 7,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sub Categories for Streaming
            [
                'category_id' => $streamingCat->id,
                'name' => 'Netflix',
                'description' => 'Langganan Netflix',
                'priority' => 7,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $streamingCat->id,
                'name' => 'Spotify',
                'description' => 'Langganan Spotify',
                'priority' => 7,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $streamingCat->id,
                'name' => 'YouTube Premium',
                'description' => 'Langganan YouTube Premium',
                'priority' => 7,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Sub Categories for Marketplace
            [
                'category_id' => $marketplaceCat->id,
                'name' => 'Tokopedia',
                'description' => 'Belanja di Tokopedia',
                'priority' => 8,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $marketplaceCat->id,
                'name' => 'Shopee',
                'description' => 'Belanja di Shopee',
                'priority' => 8,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $marketplaceCat->id,
                'name' => 'Lazada',
                'description' => 'Belanja di Lazada',
                'priority' => 7,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_id' => $marketplaceCat->id,
                'name' => 'Blibli',
                'description' => 'Belanja di Blibli',
                'priority' => 7,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('sub_categories')->insert($subCategories);

        $this->command->info('✅ Sub Categories seeded successfully!');
    }
}