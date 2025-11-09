<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SubCategorySeeder extends Seeder
{
    private $now;

    /**
     * Run the database seeds.
     * 
     * CSV VERSION - Import from sub_categories.csv
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     * - Types table sudah terisi (TypeSeeder)
     * - Categories table sudah terisi (CategorySeeder)
     * - File database/seeders/data/sub_categories.csv harus ada
     */
    public function run(): void
    {
        $this->command->info('📑 Seeding Sub-Categories from CSV...');
        $this->command->newLine();
        
        $this->now = Carbon::now();

        // Path ke file CSV
        $csvPath = database_path('seeders/data/sub_categories.csv');
        
        // Validasi file CSV exists
        if (!file_exists($csvPath)) {
            $this->command->error('❌ CSV file not found at: ' . $csvPath);
            $this->command->info('💡 Please create the file at: database/seeders/data/sub_categories.csv');
            return;
        }

        // Ambil companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->error('❌ No companies found! Please run CompanySeeder first.');
            return;
        }

        // Ambil categories
        $categories = DB::table('categories')->get();
        
        if ($categories->isEmpty()) {
            $this->command->error('❌ No categories found! Please run CategorySeeder first.');
            return;
        }

        // Baca dan parse CSV
        $subCategories = $this->readCsvFile($csvPath);
        
        if ($subCategories->isEmpty()) {
            $this->command->error('❌ No data found in CSV file!');
            return;
        }

        $this->command->info("📄 Found {$subCategories->count()} sub-categories in CSV");
        $this->command->newLine();

        $allSubCategories = [];
        $sortOrderCounter = [];

        // Generate sub-categories untuk setiap company
        foreach ($companies as $company) {
            $this->command->info("   Processing company: {$company->name}");
            
            // Filter sub-categories untuk company ini dari CSV
            $companySubCats = $subCategories->where('company_id', $company->id);
            
            foreach ($companySubCats as $subCategory) {
                // Cari category_id yang sesuai untuk company ini
                $category = $categories->where('company_id', $company->id)
                                      ->where('id', $subCategory['category_id'])
                                      ->first();
                
                if (!$category) {
                    $this->command->warn("   ⚠️  Category ID {$subCategory['category_id']} not found for company {$company->id}, skipping...");
                    continue;
                }

                // Track sort_order per category
                $categoryKey = "{$company->id}_{$category->id}";
                if (!isset($sortOrderCounter[$categoryKey])) {
                    $sortOrderCounter[$categoryKey] = 1;
                }

                $allSubCategories[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company->id,
                    'category_id' => $category->id,
                    'name' => $subCategory['name'],
                    'description' => $subCategory['description'],
                    'priority' => $this->calculatePriority($subCategory['name']),
                    'sort_order' => $sortOrderCounter[$categoryKey],
                    'created_at' => $this->now->copy()->subDays(rand(10, 140)),
                    'updated_at' => $this->now,
                    'deleted_at' => null,
                ];

                $sortOrderCounter[$categoryKey]++;
            }
        }

        if (!empty($allSubCategories)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($allSubCategories, 100);
            foreach ($chunks as $chunk) {
                DB::table('sub_categories')->insert($chunk);
            }
            
            $this->command->newLine();
            $this->command->info('✅ Sub-Categories seeded successfully!');
            $this->command->info("   Total sub-categories created: " . count($allSubCategories));
            $this->command->info("   Companies: " . $companies->count());
            
            $this->displaySummary();
        } else {
            $this->command->warn('⚠️  No sub-categories created. Check if categories and companies exist.');
        }
    }

    /**
     * Read and parse CSV file
     * 
     * @param string $csvPath
     * @return \Illuminate\Support\Collection
     */
    private function readCsvFile(string $csvPath): \Illuminate\Support\Collection
    {
        $subCategories = collect();
        
        try {
            $file = fopen($csvPath, 'r');
            
            if ($file === false) {
                throw new \Exception('Failed to open CSV file');
            }

            // Skip header row
            $header = fgetcsv($file, 0, ';'); // Delimiter adalah semicolon
            
            // Validasi header
            $expectedHeaders = ['company_id', 'category_id', 'name', 'description'];
            if ($header !== $expectedHeaders) {
                $this->command->warn('⚠️  CSV header format: expected ' . implode(', ', $expectedHeaders));
                $this->command->warn('    Got: ' . implode(', ', $header));
            }

            // Baca setiap baris
            $lineNumber = 1;
            while (($row = fgetcsv($file, 0, ';')) !== false) {
                $lineNumber++;
                
                // Skip empty rows
                if (empty($row[0]) && empty($row[1]) && empty($row[2])) {
                    continue;
                }

                // Validasi data
                if (count($row) < 4) {
                    $this->command->warn("   ⚠️  Line {$lineNumber}: Incomplete data, skipping...");
                    continue;
                }

                $subCategories->push([
                    'company_id' => (int) trim($row[0]),
                    'category_id' => (int) trim($row[1]),
                    'name' => trim($row[2]),
                    'description' => trim($row[3]),
                ]);
            }

            fclose($file);

            return $subCategories;

        } catch (\Exception $e) {
            $this->command->error('❌ Error reading CSV: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Calculate priority based on name patterns
     * Auto-assign priority based on keywords in name
     * 
     * @param string $name
     * @return int
     */
    private function calculatePriority(string $name): int
    {
        // Priority 10: High importance (specific outlets, main operations)
        if (preg_match('/KF \d{4}|LIPH|BOP|Kimia Farma|Wisma Asri/i', $name)) {
            return 10;
        }

        // Priority 9: Medium-high (specific categories)
        if (preg_match('/Outlet|Klinik|Transfer|Gaji|PPh|PPN|Premi|Klaim/i', $name)) {
            return 9;
        }

        // Priority 8: Medium (general categories)
        if (preg_match('/Umum|Lainnya|Other|General/i', $name)) {
            return 8;
        }

        // Priority 7: Medium-low
        if (preg_match('/Fee|Bonus|Tunjangan|Reimburse/i', $name)) {
            return 7;
        }

        // Default priority
        return 5;
    }

    /**
     * Display summary
     */
    private function displaySummary(): void
    {
        $this->command->newLine();
        $this->command->info('📊 SUB-CATEGORIES SUMMARY:');
        $this->command->info('='.str_repeat('=', 79));

        // Top categories with most sub-categories
        $topCategories = DB::table('categories as c')
            ->join('sub_categories as sc', 'sc.category_id', '=', 'c.id')
            ->select('c.name', DB::raw('count(sc.id) as sub_count'))
            ->groupBy('c.id', 'c.name')
            ->orderBy('sub_count', 'desc')
            ->limit(15)
            ->get();

        $this->command->info("\n📈 Top 15 Categories by Sub-Categories:\n");
        foreach ($topCategories as $cat) {
            $bar = str_repeat('█', min((int)($cat->sub_count / 2), 30));
            $this->command->info(sprintf("   %-40s %s (%d)", 
                substr($cat->name, 0, 40), 
                $bar, 
                $cat->sub_count
            ));
        }

        // Priority distribution
        $priorityDist = DB::table('sub_categories')
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->orderBy('priority', 'desc')
            ->get();

        $this->command->newLine();
        $this->command->info('🎯 Priority Distribution:');
        foreach ($priorityDist as $dist) {
            $bar = str_repeat('█', min((int)($dist->count / 10), 30));
            $this->command->info(sprintf("   Priority %d: %s (%d)", 
                $dist->priority, 
                $bar, 
                $dist->count
            ));
        }

        $total = DB::table('sub_categories')->count();
        $this->command->newLine();
        $this->command->info("Total Sub-Categories: {$total}");
    }
}
