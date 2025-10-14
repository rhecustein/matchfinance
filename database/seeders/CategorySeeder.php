<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Note: Seeder ini membutuhkan:
     * - Types table sudah terisi (TypeSeeder)
     */
    public function run(): void
    {
        // Get all types grouped by company
        $types = DB::table('types')
            ->select('id', 'company_id', 'name')
            ->orderBy('company_id')
            ->orderBy('sort_order')
            ->get();
        
        if ($types->isEmpty()) {
            $this->command->warn('⚠️  No types found! Please run TypeSeeder first.');
            return;
        }

        $now = Carbon::now();
        $allCategories = [];
        
        // Categories mapping per type name
        $categoryMappings = [
            'Pemasukan' => [
                [
                    'slug' => 'setoran-tunai',
                    'name' => 'Setoran Tunai',
                    'description' => 'Setoran tunai dari berbagai sumber (LIPH, apotek, klinik)',
                    'color' => '#10B981',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'transfer-masuk',
                    'name' => 'Transfer Masuk',
                    'description' => 'Transfer masuk dari bank dan pihak eksternal',
                    'color' => '#06B6D4',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'penerimaan-operasional',
                    'name' => 'Penerimaan Operasional',
                    'description' => 'Penerimaan dari kegiatan operasional (COD, asuransi)',
                    'color' => '#8B5CF6',
                    'sort_order' => 3,
                ],
            ],
            'Pengeluaran' => [
                [
                    'slug' => 'pembayaran-vendor',
                    'name' => 'Pembayaran Vendor',
                    'description' => 'Pembayaran kepada supplier dan vendor',
                    'color' => '#F97316',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'biaya-admin',
                    'name' => 'Biaya Administrasi',
                    'description' => 'Biaya admin bank dan transfer fee',
                    'color' => '#EF4444',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'pembelian-barang',
                    'name' => 'Pembelian Barang',
                    'description' => 'Pembelian obat, alat kesehatan, dan supplies',
                    'color' => '#84CC16',
                    'sort_order' => 3,
                ],
            ],
            'Transfer Internal' => [
                [
                    'slug' => 'transfer-cabang',
                    'name' => 'Transfer Antar Cabang',
                    'description' => 'Transfer antar cabang perusahaan',
                    'color' => '#6366F1',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'transfer-inhouse',
                    'name' => 'Transfer InHouse',
                    'description' => 'Transfer internal MCM InhouseTrf',
                    'color' => '#A855F7',
                    'sort_order' => 2,
                ],
            ],
            'Biaya Operasional' => [
                [
                    'slug' => 'bop-bulanan',
                    'name' => 'BOP Bulanan',
                    'description' => 'Biaya operasional perusahaan bulanan',
                    'color' => '#DC2626',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'utilitas',
                    'name' => 'Utilitas',
                    'description' => 'Biaya listrik, air, telepon, internet',
                    'color' => '#EA580C',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'pemeliharaan',
                    'name' => 'Pemeliharaan',
                    'description' => 'Biaya pemeliharaan dan perbaikan',
                    'color' => '#CA8A04',
                    'sort_order' => 3,
                ],
            ],
            'Payroll' => [
                [
                    'slug' => 'gaji-karyawan',
                    'name' => 'Gaji Karyawan',
                    'description' => 'Pembayaran gaji bulanan karyawan',
                    'color' => '#059669',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'tunjangan',
                    'name' => 'Tunjangan',
                    'description' => 'Tunjangan dan benefit karyawan',
                    'color' => '#0891B2',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'potongan',
                    'name' => 'Potongan',
                    'description' => 'Potongan gaji (Koperasi, BPJS, dll)',
                    'color' => '#7C3AED',
                    'sort_order' => 3,
                ],
            ],
            'Vendor & Supplier' => [
                [
                    'slug' => 'kimia-farma',
                    'name' => 'Kimia Farma',
                    'description' => 'Transaksi dengan Kimia Farma',
                    'color' => '#2563EB',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'distributor-obat',
                    'name' => 'Distributor Obat',
                    'description' => 'Pembayaran ke distributor obat',
                    'color' => '#1D4ED8',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'vendor-lainnya',
                    'name' => 'Vendor Lainnya',
                    'description' => 'Vendor dan supplier lainnya',
                    'color' => '#4F46E5',
                    'sort_order' => 3,
                ],
            ],
            'Transaksi Bank' => [
                [
                    'slug' => 'transfer-bank',
                    'name' => 'Transfer Bank',
                    'description' => 'Transfer melalui bank (BCA, BNI, BSI)',
                    'color' => '#0284C7',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'biaya-bank',
                    'name' => 'Biaya Bank',
                    'description' => 'Biaya administrasi dan layanan bank',
                    'color' => '#BE185D',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'kliring',
                    'name' => 'Kliring',
                    'description' => 'Transaksi kliring antar bank',
                    'color' => '#DB2777',
                    'sort_order' => 3,
                ],
            ],
            'Cash Transaction' => [
                [
                    'slug' => 'cod',
                    'name' => 'Cash on Delivery',
                    'description' => 'Transaksi COD',
                    'color' => '#65A30D',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'setoran-cabang',
                    'name' => 'Setoran Cabang',
                    'description' => 'Setoran tunai dari cabang',
                    'color' => '#16A34A',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'tarik-tunai',
                    'name' => 'Tarik Tunai',
                    'description' => 'Penarikan tunai',
                    'color' => '#15803D',
                    'sort_order' => 3,
                ],
            ],
            'Pinjaman & Piutang' => [
                [
                    'slug' => 'bon-karyawan',
                    'name' => 'Bon Karyawan',
                    'description' => 'Bon sementara dan pinjaman karyawan',
                    'color' => '#EC4899',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'piutang-usaha',
                    'name' => 'Piutang Usaha',
                    'description' => 'Piutang dari pelanggan',
                    'color' => '#F43F5E',
                    'sort_order' => 2,
                ],
            ],
            'Asuransi' => [
                [
                    'slug' => 'klaim-asuransi',
                    'name' => 'Klaim Asuransi',
                    'description' => 'Penerimaan klaim asuransi',
                    'color' => '#14B8A6',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'premi-asuransi',
                    'name' => 'Premi Asuransi',
                    'description' => 'Pembayaran premi asuransi',
                    'color' => '#06B6D4',
                    'sort_order' => 2,
                ],
            ],
            'Outlet & Cabang' => [
                [
                    'slug' => 'apotek',
                    'name' => 'Apotek',
                    'description' => 'Transaksi dari apotek',
                    'color' => '#10B981',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'klinik',
                    'name' => 'Klinik',
                    'description' => 'Transaksi dari klinik',
                    'color' => '#14B8A6',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'cabang-bekasi',
                    'name' => 'Cabang Bekasi',
                    'description' => 'Transaksi cabang Bekasi',
                    'color' => '#06B6D4',
                    'sort_order' => 3,
                ],
                [
                    'slug' => 'wisma-asri',
                    'name' => 'Wisma Asri',
                    'description' => 'Transaksi Wisma Asri',
                    'color' => '#0284C7',
                    'sort_order' => 4,
                ],
            ],
            'Pembelian' => [
                [
                    'slug' => 'obat-obatan',
                    'name' => 'Obat-obatan',
                    'description' => 'Pembelian obat dan medicine',
                    'color' => '#16A34A',
                    'sort_order' => 1,
                ],
                [
                    'slug' => 'alat-kesehatan',
                    'name' => 'Alat Kesehatan',
                    'description' => 'Pembelian alat kesehatan',
                    'color' => '#15803D',
                    'sort_order' => 2,
                ],
                [
                    'slug' => 'inventaris',
                    'name' => 'Inventaris',
                    'description' => 'Pembelian inventaris kantor',
                    'color' => '#166534',
                    'sort_order' => 3,
                ],
            ],
        ];
        
        // Generate categories for each type
        foreach ($types as $type) {
            $typeName = $type->name;
            
            // Skip if no category mapping exists for this type
            if (!isset($categoryMappings[$typeName])) {
                // Add default category for unmapped types
                $allCategories[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $type->company_id,
                    'type_id' => $type->id,
                    'slug' => Str::slug($typeName) . '-general',
                    'name' => $typeName . ' General',
                    'description' => 'General category for ' . $typeName,
                    'color' => '#6B7280',
                    'sort_order' => 1,
                    'created_at' => $now->copy()->subDays(rand(20, 150)),
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
                continue;
            }
            
            // Add mapped categories
            foreach ($categoryMappings[$typeName] as $category) {
                $allCategories[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $type->company_id,
                    'type_id' => $type->id,
                    'slug' => $category['slug'],
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'color' => $category['color'],
                    'sort_order' => $category['sort_order'],
                    'created_at' => $now->copy()->subDays(rand(20, 150)),
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }
        }

        if (!empty($allCategories)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($allCategories, 50);
            foreach ($chunks as $chunk) {
                DB::table('categories')->insert($chunk);
            }
            
            $this->command->info('✅ Categories seeded successfully!');
            $this->command->info("   Total categories: " . count($allCategories));
            
            // Display summary
            $summary = DB::table('categories')
                ->select('type_id', DB::raw('count(*) as count'))
                ->groupBy('type_id')
                ->get();
            
            $this->command->newLine();
            $this->command->info('📊 Categories Summary:');
            foreach ($summary as $item) {
                $typeName = DB::table('types')->where('id', $item->type_id)->value('name');
                $this->command->info("   {$typeName}: {$item->count} categories");
            }
        } else {
            $this->command->warn('⚠️  No categories created. Check if types exist.');
        }
    }
}