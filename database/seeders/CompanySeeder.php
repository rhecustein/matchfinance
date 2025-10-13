<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'uuid' => Str::uuid(),
                'name' => 'PT Kimia Farma Apotek',
                'slug' => 'kimia-farma-apotek',
                'domain' => 'apotek.kimiafarma.co.id',
                'subdomain' => 'kf-apotek',
                'status' => 'active',
                'logo' => 'logos/kimia-farma-apotek.png',
                'settings' => json_encode([
                    'timezone' => 'Asia/Jakarta',
                    'currency' => 'IDR',
                    'language' => 'id',
                    'date_format' => 'd/m/Y',
                    'time_format' => 'H:i',
                    'primary_color' => '#00A651',
                    'secondary_color' => '#0066CC',
                    'company_info' => [
                        'address' => 'Jl. Veteran No. 9, Jakarta Pusat',
                        'phone' => '+62-21-3841031',
                        'email' => 'info@kimiafarma.co.id',
                        'npwp' => '01.001.234.5-678.000',
                        'business_type' => 'Farmasi & Apotek',
                    ],
                    'features' => [
                        'bank_reconciliation' => true,
                        'multi_currency' => false,
                        'inventory_management' => true,
                        'pos_integration' => true,
                        'auto_backup' => true,
                    ],
                    'notifications' => [
                        'email_notifications' => true,
                        'sms_notifications' => false,
                        'whatsapp_notifications' => true,
                    ],
                ]),
                'trial_ends_at' => null,
                'created_at' => Carbon::now()->subMonths(6),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'PT Kimia Farma Diagnostika',
                'slug' => 'kimia-farma-diagnostika',
                'domain' => 'diagnostik.kimiafarma.co.id',
                'subdomain' => 'kf-diagnostika',
                'status' => 'active',
                'logo' => 'logos/kimia-farma-diagnostika.png',
                'settings' => json_encode([
                    'timezone' => 'Asia/Jakarta',
                    'currency' => 'IDR',
                    'language' => 'id',
                    'date_format' => 'd/m/Y',
                    'time_format' => 'H:i',
                    'primary_color' => '#E31E24',
                    'secondary_color' => '#1A1A1A',
                    'company_info' => [
                        'address' => 'Jl. Raya Bogor KM 27, Ciracas, Jakarta Timur',
                        'phone' => '+62-21-8710271',
                        'email' => 'info@kfdiagnostik.com',
                        'npwp' => '01.002.345.6-789.000',
                        'business_type' => 'Laboratorium Klinik',
                    ],
                    'features' => [
                        'bank_reconciliation' => true,
                        'multi_currency' => false,
                        'inventory_management' => true,
                        'pos_integration' => false,
                        'auto_backup' => true,
                        'lab_integration' => true,
                    ],
                    'notifications' => [
                        'email_notifications' => true,
                        'sms_notifications' => true,
                        'whatsapp_notifications' => true,
                    ],
                ]),
                'trial_ends_at' => null,
                'created_at' => Carbon::now()->subMonths(4),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'ALBAHJAH',
                'slug' => 'albahjah',
                'domain' => null,
                'subdomain' => 'albahjah',
                'status' => 'trial',
                'logo' => 'logos/albahjah.png',
                'settings' => json_encode([
                    'timezone' => 'Asia/Jakarta',
                    'currency' => 'IDR',
                    'language' => 'id',
                    'date_format' => 'd/m/Y',
                    'time_format' => 'H:i',
                    'primary_color' => '#10B981',
                    'secondary_color' => '#3B82F6',
                    'company_info' => [
                        'address' => 'Bekasi, Jawa Barat',
                        'phone' => '+62-812-3456-7890',
                        'email' => 'info@albahjah.com',
                        'npwp' => null,
                        'business_type' => 'Trading & Distribution',
                    ],
                    'features' => [
                        'bank_reconciliation' => true,
                        'multi_currency' => false,
                        'inventory_management' => true,
                        'pos_integration' => false,
                        'auto_backup' => false,
                    ],
                    'notifications' => [
                        'email_notifications' => true,
                        'sms_notifications' => false,
                        'whatsapp_notifications' => false,
                    ],
                ]),
                'trial_ends_at' => Carbon::now()->addDays(14),
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now(),
                'deleted_at' => null,
            ],
        ];

        DB::table('companies')->insert($companies);
        
        $this->command->info('✅ 3 Companies seeded successfully!');
        $this->command->info('   - PT Kimia Farma Apotek (Active)');
        $this->command->info('   - PT Kimia Farma Diagnostika (Active)');
        $this->command->info('   - ALBAHJAH (Trial - 14 days remaining)');
    }
}