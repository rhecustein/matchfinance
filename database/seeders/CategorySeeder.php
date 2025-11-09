<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    private $now;

    /**
     * Run the database seeds.
     * 
     * CSV VERSION - Import from categories.csv
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     * - Types table sudah terisi (TypeSeeder)
     * - File database/seeders/data/categories.csv harus ada
     */
    public function run(): void
    {
        $this->command->info('📂 Seeding Categories from CSV...');
        $this->command->newLine();
        
        $this->now = Carbon::now();

        // Path ke file CSV
        $csvPath = database_path('seeders/data/categories.csv');
        
        // Validasi file CSV exists
        if (!file_exists($csvPath)) {
            $this->command->error('❌ CSV file not found at: ' . $csvPath);
            $this->command->info('💡 Please create the file at: database/seeders/data/categories.csv');
            return;
        }

        // Ambil companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->error('❌ No companies found! Please run CompanySeeder first.');
            return;
        }

        // Ambil types
        $types = DB::table('types')->get();
        
        if ($types->isEmpty()) {
            $this->command->error('❌ No types found! Please run TypeSeeder first.');
            return;
        }

        // Baca dan parse CSV
        $categories = $this->readCsvFile($csvPath);
        
        if ($categories->isEmpty()) {
            $this->command->error('❌ No data found in CSV file!');
            return;
        }

        $this->command->info("📄 Found {$categories->count()} categories in CSV");
        $this->command->newLine();

        $allCategories = [];
        $sortOrderCounter = [];

        // Generate categories untuk setiap company
        foreach ($companies as $company) {
            $this->command->info("   Processing company: {$company->name}");
            
            // Filter categories untuk company ini dari CSV
            $companyCats = $categories->where('company_id', $company->id);
            
            foreach ($companyCats as $category) {
                // Cari type_id yang sesuai untuk company ini
                $type = $types->where('company_id', $company->id)
                              ->where('id', $category['type_id'])
                              ->first();
                
                if (!$type) {
                    $this->command->warn("   ⚠️  Type ID {$category['type_id']} not found for company {$company->id}, skipping...");
                    continue;
                }

                // Track sort_order per type
                $typeKey = "{$company->id}_{$type->id}";
                if (!isset($sortOrderCounter[$typeKey])) {
                    $sortOrderCounter[$typeKey] = 1;
                }

                $allCategories[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company->id,
                    'type_id' => $type->id,
                    'slug' => $category['slug'],
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'color' => $this->generateColor($sortOrderCounter[$typeKey]),
                    'sort_order' => $sortOrderCounter[$typeKey],
                    'created_at' => $this->now->copy()->subDays(rand(20, 150)),
                    'updated_at' => $this->now,
                    'deleted_at' => null,
                ];

                $sortOrderCounter[$typeKey]++;
            }
        }

        if (!empty($allCategories)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($allCategories, 100);
            foreach ($chunks as $chunk) {
                DB::table('categories')->insert($chunk);
            }
            
            $this->command->newLine();
            $this->command->info('✅ Categories seeded successfully!');
            $this->command->info("   Total categories created: " . count($allCategories));
            $this->command->info("   Companies: " . $companies->count());
            
            $this->displaySummary();
        } else {
            $this->command->warn('⚠️  No categories created. Check if types and companies exist.');
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
        $categories = collect();
        
        try {
            $file = fopen($csvPath, 'r');
            
            if ($file === false) {
                throw new \Exception('Failed to open CSV file');
            }

            // Skip header row
            $header = fgetcsv($file, 0, ';'); // Delimiter adalah semicolon
            
            // Validasi header
            $expectedHeaders = ['company_id', 'type_id', 'slug', 'name', 'description'];
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
                if (count($row) < 5) {
                    $this->command->warn("   ⚠️  Line {$lineNumber}: Incomplete data, skipping...");
                    continue;
                }

                $categories->push([
                    'company_id' => (int) trim($row[0]),
                    'type_id' => (int) trim($row[1]),
                    'slug' => trim($row[2]),
                    'name' => trim($row[3]),
                    'description' => trim($row[4]),
                ]);
            }

            fclose($file);

            return $categories;

        } catch (\Exception $e) {
            $this->command->error('❌ Error reading CSV: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Generate color based on sort order
     * Cycling through a predefined color palette
     * 
     * @param int $sortOrder
     * @return string
     */
    private function generateColor(int $sortOrder): string
    {
        $colors = [
            '#10B981', // Green
            '#3B82F6', // Blue
            '#F59E0B', // Orange
            '#EF4444', // Red
            '#8B5CF6', // Purple
            '#EC4899', // Pink
            '#14B8A6', // Teal
            '#F97316', // Deep Orange
            '#06B6D4', // Cyan
            '#84CC16', // Lime
            '#A855F7', // Violet
            '#059669', // Emerald
        ];

        return $colors[($sortOrder - 1) % count($colors)];
    }

    /**
     * Display summary
     */
    private function displaySummary(): void
    {
        $this->command->newLine();
        $this->command->info('📊 CATEGORIES SUMMARY BY TYPE:');
        $this->command->info('='.str_repeat('=', 79));

        $summary = DB::table('categories as c')
            ->join('types as t', 't.id', '=', 'c.type_id')
            ->select('t.name as type_name', DB::raw('count(c.id) as count'))
            ->groupBy('t.id', 't.name')
            ->orderBy('t.name')
            ->get();

        foreach ($summary as $item) {
            $this->command->info(sprintf("   %-50s: %d categories", $item->type_name, $item->count));
        }

        $total = DB::table('categories')->count();
        $this->command->newLine();
        $this->command->info("Total Categories: {$total}");
    }
}
