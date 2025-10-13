<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting database seeding...');
        $this->command->newLine();

        // Run seeders in correct order (important for foreign keys!)
        $this->call([
            BankSeeder::class,
            TypeSeeder::class,
            CategorySeeder::class,
            SubCategorySeeder::class,
            KeywordSeeder::class,
            CompanySeeder::class,
            UserSeeder::class,
            PlanSeeder::class,
            CompanyInvitationSeeder::class,
            CompanySubscriptionSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->newLine();
        
        // Display summary
        $this->displaySummary();
    }

    /**
     * Display seeding summary
     */
    private function displaySummary(): void
    {
        $banks = DB::table('banks')->count();
        $types = DB::table('types')->count();
        $categories = DB::table('categories')->count();
        $subCategories = DB::table('sub_categories')->count();
        $keywords = DB::table('keywords')->count();

        $this->command->table(
            ['Table', 'Records'],
            [
                ['Banks', $banks],
                ['Types', $types],
                ['Categories', $categories],
                ['Sub Categories', $subCategories],
                ['Keywords', $keywords],
            ]
        );

        $this->command->newLine();
        $this->command->info('📊 Total: ' . ($banks + $types + $categories + $subCategories + $keywords) . ' records inserted');
    }
}