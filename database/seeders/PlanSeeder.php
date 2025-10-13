<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'uuid' => Str::uuid(),
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Paket gratis untuk mencoba fitur dasar aplikasi',
                'price' => 0,
                'billing_period' => 'monthly',
                'features' => json_encode([
                    'max_users' => 1,
                    'max_products' => 10,
                    'max_transactions' => 100,
                    'max_storage_mb' => 100,
                    'bank_statements' => false,
                    'advanced_reports' => false,
                    'api_access' => false,
                    'priority_support' => false,
                    'custom_branding' => false,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Paket untuk usaha kecil dan startup',
                'price' => 99000,
                'billing_period' => 'monthly',
                'features' => json_encode([
                    'max_users' => 3,
                    'max_products' => 100,
                    'max_transactions' => 1000,
                    'max_storage_mb' => 1024,
                    'bank_statements' => true,
                    'advanced_reports' => false,
                    'api_access' => false,
                    'priority_support' => false,
                    'custom_branding' => false,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Starter Yearly',
                'slug' => 'starter-yearly',
                'description' => 'Paket untuk usaha kecil dan startup (hemat 20%)',
                'price' => 950000,
                'billing_period' => 'yearly',
                'features' => json_encode([
                    'max_users' => 3,
                    'max_products' => 100,
                    'max_transactions' => 1000,
                    'max_storage_mb' => 1024,
                    'bank_statements' => true,
                    'advanced_reports' => false,
                    'api_access' => false,
                    'priority_support' => false,
                    'custom_branding' => false,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Paket untuk bisnis berkembang dengan fitur lengkap',
                'price' => 299000,
                'billing_period' => 'monthly',
                'features' => json_encode([
                    'max_users' => 10,
                    'max_products' => 1000,
                    'max_transactions' => 10000,
                    'max_storage_mb' => 5120,
                    'bank_statements' => true,
                    'advanced_reports' => true,
                    'api_access' => true,
                    'priority_support' => true,
                    'custom_branding' => false,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Professional Yearly',
                'slug' => 'professional-yearly',
                'description' => 'Paket untuk bisnis berkembang dengan fitur lengkap (hemat 20%)',
                'price' => 2870000,
                'billing_period' => 'yearly',
                'features' => json_encode([
                    'max_users' => 10,
                    'max_products' => 1000,
                    'max_transactions' => 10000,
                    'max_storage_mb' => 5120,
                    'bank_statements' => true,
                    'advanced_reports' => true,
                    'api_access' => true,
                    'priority_support' => true,
                    'custom_branding' => false,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Paket untuk perusahaan dengan kebutuhan enterprise',
                'price' => 599000,
                'billing_period' => 'monthly',
                'features' => json_encode([
                    'max_users' => 50,
                    'max_products' => 10000,
                    'max_transactions' => 100000,
                    'max_storage_mb' => 20480,
                    'bank_statements' => true,
                    'advanced_reports' => true,
                    'api_access' => true,
                    'priority_support' => true,
                    'custom_branding' => true,
                    'white_label' => false,
                    'dedicated_account_manager' => true,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Business Yearly',
                'slug' => 'business-yearly',
                'description' => 'Paket untuk perusahaan dengan kebutuhan enterprise (hemat 20%)',
                'price' => 5750000,
                'billing_period' => 'yearly',
                'features' => json_encode([
                    'max_users' => 50,
                    'max_products' => 10000,
                    'max_transactions' => 100000,
                    'max_storage_mb' => 20480,
                    'bank_statements' => true,
                    'advanced_reports' => true,
                    'api_access' => true,
                    'priority_support' => true,
                    'custom_branding' => true,
                    'white_label' => false,
                    'dedicated_account_manager' => true,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Paket khusus untuk perusahaan besar dengan kebutuhan unlimited',
                'price' => 1999000,
                'billing_period' => 'monthly',
                'features' => json_encode([
                    'max_users' => -1, // unlimited
                    'max_products' => -1, // unlimited
                    'max_transactions' => -1, // unlimited
                    'max_storage_mb' => -1, // unlimited
                    'bank_statements' => true,
                    'advanced_reports' => true,
                    'api_access' => true,
                    'priority_support' => true,
                    'custom_branding' => true,
                    'white_label' => true,
                    'dedicated_account_manager' => true,
                    'custom_integrations' => true,
                    'sla_guarantee' => true,
                    'onboarding_training' => true,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Enterprise Yearly',
                'slug' => 'enterprise-yearly',
                'description' => 'Paket khusus untuk perusahaan besar dengan kebutuhan unlimited (hemat 25%)',
                'price' => 17990000,
                'billing_period' => 'yearly',
                'features' => json_encode([
                    'max_users' => -1, // unlimited
                    'max_products' => -1, // unlimited
                    'max_transactions' => -1, // unlimited
                    'max_storage_mb' => -1, // unlimited
                    'bank_statements' => true,
                    'advanced_reports' => true,
                    'api_access' => true,
                    'priority_support' => true,
                    'custom_branding' => true,
                    'white_label' => true,
                    'dedicated_account_manager' => true,
                    'custom_integrations' => true,
                    'sla_guarantee' => true,
                    'onboarding_training' => true,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('plans')->insert($plans);
        
        $this->command->info('Plans seeded successfully!');
    }
}