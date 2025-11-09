<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TypeSeeder extends Seeder
{
    private $now;

    /**
     * Run the database seeds.
     * 
     * CSV VERSION - Import from types.csv
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     * - File database/seeders/data/types.csv harus ada
     */
    public function run(): void
    {
        $this->command->info('🏷️  Seeding Transaction Types from CSV...');
        $this->command->newLine();
        
        $this->now = Carbon::now();

        // Path ke file CSV
        $csvPath = database_path('seeders/data/types.csv');
        
        // Validasi file CSV exists
        if (!file_exists($csvPath)) {
            $this->command->error('❌ CSV file not found at: ' . $csvPath);
            $this->command->info('💡 Please create the file at: database/seeders/data/types.csv');
            return;
        }

        // Ambil companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->error('❌ No companies found! Please run CompanySeeder first.');
            return;
        }

        // Baca dan parse CSV
        $transactionTypes = $this->readCsvFile($csvPath);
        
        if (empty($transactionTypes)) {
            $this->command->error('❌ No data found in CSV file!');
            return;
        }

        $this->command->info("📄 Found {$transactionTypes->count()} types in CSV");
        $this->command->newLine();

        $allTypes = [];

        // Generate types untuk setiap company
        foreach ($companies as $company) {
            $this->command->info("   Processing company: {$company->name}");
            
            foreach ($transactionTypes as $index => $type) {
                $allTypes[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company->id,
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'sort_order' => $index + 1, // Sort order mulai dari 1
                    'created_at' => $this->now->copy()->subDays(rand(30, 180)),
                    'updated_at' => $this->now,
                    'deleted_at' => null,
                ];
            }
        }

        if (!empty($allTypes)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($allTypes, 50);
            foreach ($chunks as $chunk) {
                DB::table('types')->insert($chunk);
            }
            
            $this->command->newLine();
            $this->command->info('✅ Types seeded successfully!');
            $this->command->info("   Total types created: " . count($allTypes));
            $this->command->info("   Companies: " . $companies->count());
            $this->command->info("   Types per company: " . $transactionTypes->count());
            
            $this->displayTypeSummary($transactionTypes);
        } else {
            $this->command->warn('⚠️  No types created. Check if companies exist.');
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
        $types = collect();
        
        try {
            $file = fopen($csvPath, 'r');
            
            if ($file === false) {
                throw new \Exception('Failed to open CSV file');
            }

            // Skip header row
            $header = fgetcsv($file);
            
            // Validasi header (opsional)
            if ($header !== ['name', 'description']) {
                $this->command->warn('⚠️  CSV header format: expected [name, description]');
            }

            // Baca setiap baris
            while (($row = fgetcsv($file)) !== false) {
                // Skip empty rows
                if (empty($row[0]) && empty($row[1])) {
                    continue;
                }

                $types->push([
                    'name' => trim($row[0] ?? ''),
                    'description' => trim($row[1] ?? ''),
                ]);
            }

            fclose($file);

            return $types;

        } catch (\Exception $e) {
            $this->command->error('❌ Error reading CSV: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Display type summary in console
     */
    private function displayTypeSummary($types): void
    {
        $this->command->newLine();
        $this->command->info('📊 TRANSACTION TYPES LOADED FROM CSV:');
        $this->command->info('='.str_repeat('=', 79));
        
        $counter = 1;
        foreach ($types as $type) {
            $this->command->line(sprintf(
                "   %2d. %-40s",
                $counter,
                $type['name']
            ));
            $counter++;
        }

        $this->command->newLine();
        $this->command->info('='.str_repeat('=', 79));
        $this->command->info("Total: " . $types->count() . " transaction types");
    }
}