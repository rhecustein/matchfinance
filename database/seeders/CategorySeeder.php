<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Get type IDs
        $outletType = DB::table('types')->where('name', 'Outlet')->first();
        $transaksiType = DB::table('types')->where('name', 'Transaksi')->first();
        $transferType = DB::table('types')->where('name', 'Transfer')->first();
        $pembayaranType = DB::table('types')->where('name', 'Pembayaran')->first();
        $ecommerceType = DB::table('types')->where('name', 'E-Commerce')->first();

        $categories = [
            // Categories for Outlet
            [
                'type_id' => $outletType->id,
                'name' => 'Apotek',
                'description' => 'Pembelian obat dan produk kesehatan',
                'color' => '#10B981', // Green
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type_id' => $outletType->id,
                'name' => 'Minimarket',
                'description' => 'Belanja kebutuhan sehari-hari',
                'color' => '#F59E0B', // Orange
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type_id' => $outletType->id,
                'name' => 'Restoran & F&B',
                'description' => 'Makanan dan minuman',
                'color' => '#EF4444', // Red
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type_id' => $outletType->id,
                'name' => 'SPBU',
                'description' => 'Pembelian bahan bakar',
                'color' => '#8B5CF6', // Purple
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Categories for Transaksi
            [
                'type_id' => $transaksiType->id,
                'name' => 'QR Code',
                'description' => 'Transaksi menggunakan QR code',
                'color' => '#3B82F6', // Blue
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type_id' => $transaksiType->id,
                'name' => 'E-Wallet',
                'description' => 'Top-up dan transaksi e-wallet',
                'color' => '#06B6D4', // Cyan
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type_id' => $transaksiType->id,
                'name' => 'EDC/Debit',
                'description' => 'Transaksi dengan kartu debit',
                'color' => '#14B8A6', // Teal
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Categories for Transfer
            [
                'type_id' => $transferType->id,
                'name' => 'Transfer Antar Bank',
                'description' => 'Transfer ke bank lain',
                'color' => '#6366F1', // Indigo
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type_id' => $transferType->id,
                'name' => 'Transfer Sesama Bank',
                'description' => 'Transfer dalam bank yang sama',
                'color' => '#8B5CF6', // Purple
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Categories for Pembayaran
            [
                'type_id' => $pembayaranType->id,
                'name' => 'Tagihan Listrik',
                'description' => 'Pembayaran PLN',
                'color' => '#F59E0B', // Orange
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type_id' => $pembayaranType->id,
                'name' => 'Pulsa & Paket Data',
                'description' => 'Pembelian pulsa dan internet',
                'color' => '#EC4899', // Pink
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type_id' => $pembayaranType->id,
                'name' => 'Streaming & Subscription',
                'description' => 'Langganan layanan digital',
                'color' => '#EF4444', // Red
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Categories for E-Commerce
            [
                'type_id' => $ecommerceType->id,
                'name' => 'Marketplace',
                'description' => 'Belanja di marketplace online',
                'color' => '#F97316', // Orange
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('categories')->insert($categories);

        $this->command->info('✅ Categories seeded successfully!');
    }
}