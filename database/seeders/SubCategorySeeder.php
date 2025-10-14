<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Note: Seeder ini membutuhkan:
     * - Categories table sudah terisi (CategorySeeder)
     */
    public function run(): void
    {
        // Get all categories with their slugs
        $categories = DB::table('categories')
            ->select('id', 'company_id', 'slug', 'name')
            ->orderBy('company_id')
            ->orderBy('sort_order')
            ->get();
        
        if ($categories->isEmpty()) {
            $this->command->warn('⚠️  No categories found! Please run CategorySeeder first.');
            return;
        }

        $now = Carbon::now();
        $allSubCategories = [];
        
        // Sub-categories mapping per category slug
        $subCategoryMappings = [
            // PEMASUKAN Sub-Categories
            'setoran-tunai' => [
                ['name' => 'Setoran LIPH', 'description' => 'Setoran melalui LIPH', 'priority' => 10],
                ['name' => 'Setoran Apotek', 'description' => 'Setoran dari apotek', 'priority' => 9],
                ['name' => 'Setoran Klinik', 'description' => 'Setoran dari klinik', 'priority' => 9],
                ['name' => 'Setoran Cabang Bekasi', 'description' => 'Setoran cabang Bekasi', 'priority' => 8],
                ['name' => 'Setoran Wisma Asri', 'description' => 'Setoran Wisma Asri', 'priority' => 8],
            ],
            'transfer-masuk' => [
                ['name' => 'Transfer BCA', 'description' => 'Transfer masuk dari BCA', 'priority' => 10],
                ['name' => 'Transfer BNI', 'description' => 'Transfer masuk dari BNI', 'priority' => 10],
                ['name' => 'Transfer BSI', 'description' => 'Transfer masuk dari BSI', 'priority' => 10],
                ['name' => 'Transfer Mandiri', 'description' => 'Transfer masuk dari Mandiri', 'priority' => 10],
                ['name' => 'Transfer Bank Lain', 'description' => 'Transfer dari bank lainnya', 'priority' => 8],
            ],
            'penerimaan-operasional' => [
                ['name' => 'COD', 'description' => 'Penerimaan Cash on Delivery', 'priority' => 9],
                ['name' => 'Klaim Asuransi', 'description' => 'Penerimaan klaim asuransi', 'priority' => 8],
                ['name' => 'Penerimaan Lainnya', 'description' => 'Penerimaan operasional lainnya', 'priority' => 5],
            ],
            
            // PENGELUARAN Sub-Categories
            'pembayaran-vendor' => [
                ['name' => 'Kimia Farma Apotek', 'description' => 'Pembayaran ke Kimia Farma', 'priority' => 10],
                ['name' => 'PT Penta Valent', 'description' => 'Pembayaran ke PT Penta Valent', 'priority' => 9],
                ['name' => 'Distributor Obat', 'description' => 'Pembayaran distributor obat', 'priority' => 9],
                ['name' => 'Vendor Alkes', 'description' => 'Pembayaran vendor alat kesehatan', 'priority' => 8],
                ['name' => 'Supplier Lainnya', 'description' => 'Pembayaran supplier lain', 'priority' => 7],
            ],
            'biaya-admin' => [
                ['name' => 'Transfer Fee', 'description' => 'Biaya transfer bank', 'priority' => 10],
                ['name' => 'Admin Bank', 'description' => 'Biaya administrasi bank', 'priority' => 9],
                ['name' => 'Biaya Kliring', 'description' => 'Biaya kliring', 'priority' => 8],
                ['name' => 'Biaya ATM', 'description' => 'Biaya transaksi ATM', 'priority' => 7],
            ],
            'pembelian-barang' => [
                ['name' => 'Pembelian Medicine', 'description' => 'Pembelian obat/medicine', 'priority' => 10],
                ['name' => 'Pembelian Alkes', 'description' => 'Pembelian alat kesehatan', 'priority' => 9],
                ['name' => 'Pembelian ATK', 'description' => 'Pembelian alat tulis kantor', 'priority' => 7],
                ['name' => 'Pembelian Inventaris', 'description' => 'Pembelian inventaris', 'priority' => 6],
            ],
            
            // TRANSFER INTERNAL Sub-Categories
            'transfer-cabang' => [
                ['name' => 'Transfer Bekasi', 'description' => 'Transfer ke/dari cabang Bekasi', 'priority' => 9],
                ['name' => 'Transfer Wisma Asri', 'description' => 'Transfer ke/dari Wisma Asri', 'priority' => 9],
                ['name' => 'Transfer Kranggan', 'description' => 'Transfer ke/dari Kranggan', 'priority' => 8],
                ['name' => 'Transfer Cabang Lain', 'description' => 'Transfer cabang lainnya', 'priority' => 7],
            ],
            'transfer-inhouse' => [
                ['name' => 'MCM InhouseTrf DARI', 'description' => 'Transfer internal masuk MCM', 'priority' => 10],
                ['name' => 'MCM InhouseTrf KE', 'description' => 'Transfer internal keluar MCM', 'priority' => 10],
                ['name' => 'InHouse Transfer Lain', 'description' => 'Transfer internal lainnya', 'priority' => 7],
            ],
            
            // BIAYA OPERASIONAL Sub-Categories
            'bop-bulanan' => [
                ['name' => 'BOP Januari', 'description' => 'BOP bulan Januari', 'priority' => 10],
                ['name' => 'BOP Februari', 'description' => 'BOP bulan Februari', 'priority' => 10],
                ['name' => 'BOP Maret', 'description' => 'BOP bulan Maret', 'priority' => 10],
                ['name' => 'BOP Triwulan', 'description' => 'BOP per triwulan', 'priority' => 8],
                ['name' => 'BOP Tahunan', 'description' => 'BOP tahunan', 'priority' => 7],
            ],
            
            // PAYROLL Sub-Categories
            'gaji-karyawan' => [
                ['name' => 'Gaji Staff', 'description' => 'Gaji karyawan staff', 'priority' => 10],
                ['name' => 'Gaji Manajemen', 'description' => 'Gaji level manajemen', 'priority' => 10],
                ['name' => 'Gaji Apoteker', 'description' => 'Gaji apoteker', 'priority' => 9],
                ['name' => 'Gaji Driver', 'description' => 'Gaji driver/kurir', 'priority' => 8],
            ],
            'tunjangan' => [
                ['name' => 'Tunjangan Kesehatan', 'description' => 'Tunjangan kesehatan', 'priority' => 9],
                ['name' => 'Tunjangan Transport', 'description' => 'Tunjangan transportasi', 'priority' => 8],
                ['name' => 'Tunjangan Makan', 'description' => 'Tunjangan makan', 'priority' => 8],
                ['name' => 'THR', 'description' => 'Tunjangan hari raya', 'priority' => 10],
            ],
            'potongan' => [
                ['name' => 'Potongan Koperasi', 'description' => 'Potongan koperasi karyawan', 'priority' => 10],
                ['name' => 'Potongan BPJS', 'description' => 'Potongan BPJS', 'priority' => 9],
                ['name' => 'Potongan Pinjaman', 'description' => 'Potongan pinjaman karyawan', 'priority' => 8],
            ],
            
            // VENDOR & SUPPLIER Sub-Categories
            'kimia-farma' => [
                ['name' => 'KF Apotek', 'description' => 'Kimia Farma Apotek', 'priority' => 10],
                ['name' => 'KF Distribusi', 'description' => 'Kimia Farma Distribusi', 'priority' => 9],
                ['name' => 'KF Trading', 'description' => 'Kimia Farma Trading', 'priority' => 8],
            ],
            
            // CASH TRANSACTION Sub-Categories
            'cod' => [
                ['name' => 'COD Apotek', 'description' => 'COD untuk apotek', 'priority' => 10],
                ['name' => 'COD Customer', 'description' => 'COD ke customer', 'priority' => 9],
                ['name' => 'COD Vendor', 'description' => 'COD ke vendor', 'priority' => 8],
            ],
            
            // PINJAMAN & PIUTANG Sub-Categories
            'bon-karyawan' => [
                ['name' => 'Bon Sementara', 'description' => 'Bon sementara karyawan', 'priority' => 10],
                ['name' => 'Pinjaman Karyawan', 'description' => 'Pinjaman jangka panjang', 'priority' => 8],
                ['name' => 'Kasbon', 'description' => 'Kasbon karyawan', 'priority' => 9],
            ],
            
            // OUTLET & CABANG Sub-Categories
            'apotek' => [
                ['name' => 'Apotek Bekasi', 'description' => 'Apotek cabang Bekasi', 'priority' => 9],
                ['name' => 'Apotek Wisma Asri', 'description' => 'Apotek Wisma Asri', 'priority' => 9],
                ['name' => 'Apotek Kranggan', 'description' => 'Apotek Kranggan', 'priority' => 8],
            ],
            'klinik' => [
                ['name' => 'Klinik Utama', 'description' => 'Klinik utama', 'priority' => 10],
                ['name' => 'Klinik Cabang', 'description' => 'Klinik cabang', 'priority' => 8],
            ],
        ];
        
        $sortOrder = 0;
        
        // Generate sub-categories for each category
        foreach ($categories as $category) {
            $categorySlug = $category->slug;
            
            // Check if we have sub-categories for this category
            if (!isset($subCategoryMappings[$categorySlug])) {
                // Add a default sub-category for unmapped categories
                $allSubCategories[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $category->company_id,
                    'category_id' => $category->id,
                    'name' => $category->name . ' - Umum',
                    'description' => 'Sub kategori umum untuk ' . $category->name,
                    'priority' => 5,
                    'sort_order' => ++$sortOrder,
                    'created_at' => $now->copy()->subDays(rand(10, 140)),
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
                continue;
            }
            
            // Add mapped sub-categories
            foreach ($subCategoryMappings[$categorySlug] as $subCategory) {
                $allSubCategories[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $category->company_id,
                    'category_id' => $category->id,
                    'name' => $subCategory['name'],
                    'description' => $subCategory['description'],
                    'priority' => $subCategory['priority'],
                    'sort_order' => ++$sortOrder,
                    'created_at' => $now->copy()->subDays(rand(10, 140)),
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }
        }

        if (!empty($allSubCategories)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($allSubCategories, 50);
            foreach ($chunks as $chunk) {
                DB::table('sub_categories')->insert($chunk);
            }
            
            $this->command->info('✅ SubCategories seeded successfully!');
            $this->command->info("   Total sub-categories: " . count($allSubCategories));
            
            // Display summary
            $summary = DB::table('sub_categories')
                ->select('category_id', DB::raw('count(*) as count'))
                ->groupBy('category_id')
                ->get();
            
            $this->command->newLine();
            $this->command->info('📊 SubCategories Summary:');
            
            $topCategories = DB::table('categories')
                ->join('sub_categories', 'categories.id', '=', 'sub_categories.category_id')
                ->select('categories.name', DB::raw('count(sub_categories.id) as sub_count'))
                ->groupBy('categories.name')
                ->orderBy('sub_count', 'desc')
                ->limit(10)
                ->get();
            
            foreach ($topCategories as $cat) {
                $this->command->info("   {$cat->name}: {$cat->sub_count} sub-categories");
            }
            
            // Show priority distribution
            $priorityDist = DB::table('sub_categories')
                ->select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->orderBy('priority', 'desc')
                ->get();
            
            $this->command->newLine();
            $this->command->info('🎯 Priority Distribution:');
            foreach ($priorityDist as $dist) {
                $bar = str_repeat('█', min($dist->count / 10, 20));
                $this->command->info("   Priority {$dist->priority}: {$bar} ({$dist->count})");
            }
        } else {
            $this->command->warn('⚠️  No sub-categories created. Check if categories exist.');
        }
    }
}