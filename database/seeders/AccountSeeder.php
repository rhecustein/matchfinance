<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AccountSeeder extends Seeder
{
    private $now;
    private $companyId = 1; // Static company_id

    /**
     * Run the database seeds.
     * 
     * Note: Seeder ini untuk Accounts (Outlet) berdasarkan data gambar
     * Company ID = 1 (Static)
     */
    public function run(): void
    {
        $this->command->info('🏪 Seeding Accounts (Outlets)...');
        
        $this->now = Carbon::now();

        // Cek apakah company_id = 1 ada
        $company = DB::table('companies')->where('id', $this->companyId)->first();
        
        if (!$company) {
            $this->command->error('❌ Company with ID = 1 not found! Please run CompanySeeder first.');
            return;
        }

        $this->command->info("   Company: {$company->name}");

        // Data Outlets dari gambar
        $outlets = $this->getOutletsData();

        $accounts = [];
        foreach ($outlets as $outlet) {
            $accounts[] = [
                'uuid' => Str::uuid(),
                'company_id' => $this->companyId,
                'name' => $outlet['name'],
                'code' => $outlet['code'],
                'description' => "Outlet {$outlet['name']}",
                'account_type' => 'revenue', // Semua outlet = revenue account
                'color' => $this->getRandomColor(),
                'priority' => 5,
                'is_active' => true,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }

        // Insert ke database
        DB::table('accounts')->insert($accounts);

        $this->command->newLine();
        $this->command->info('✅ Accounts seeded successfully!');
        $this->command->info("   Total outlets: " . count($accounts));
        $this->command->info("   Company ID: {$this->companyId}");
    }

    /**
     * Data Outlets dari gambar yang diberikan
     */
    private function getOutletsData(): array
    {
        return [
            ['code' => 'A_01', 'name' => 'KF 0264 NAROGONG'],
            ['code' => 'A_01I1', 'name' => 'KF_264 RESEP SUZI'],
            ['code' => 'A_02', 'name' => 'KF 0330 HARAPAN INDAH'],
            ['code' => 'A_02I1', 'name' => 'KF 0330 RESEP HI'],
            ['code' => 'A_03', 'name' => 'KF 0340 CIKARANG'],
            ['code' => 'A_03I1', 'name' => 'KF 340 RESEP KC'],
            ['code' => 'A_04', 'name' => 'KF 0347 PEKAYON'],
            ['code' => 'A_05', 'name' => 'KF 0367 JATI ASIH'],
            ['code' => 'A_06', 'name' => 'KF 0390 CAKUNG GEDE'],
            ['code' => 'A_07', 'name' => 'KF 0405 DAAN'],
            ['code' => 'A_08', 'name' => 'KF 0406 KALIMALANG'],
            ['code' => 'A_09', 'name' => 'KF 0007 CIBITUNG'],
            ['code' => 'A_091I', 'name' => 'KF 007 RESEP CB'],
            ['code' => 'A_11', 'name' => 'KF 0456 GRANWIS'],
            ['code' => 'A_13', 'name' => 'KF 0503 TAMAN MINI'],
            ['code' => 'A_15', 'name' => 'KF 0586 CILEUNGSI'],
            ['code' => 'A_20', 'name' => 'KF 0591_ZAMRUD'],
            ['code' => 'A_21', 'name' => 'KF 0624 CISC'],
            ['code' => 'A_22', 'name' => 'KF 0618 KRANJI'],
            ['code' => 'A_23', 'name' => 'KF 0810 KINTAMANI'],
            ['code' => 'A_24', 'name' => 'KF 0944 PARUNGKD'],
            ['code' => 'A_25', 'name' => 'KF KALIMANGSIS'],
            ['code' => 'A_26', 'name' => 'KTIM EKA 47'],
            ['code' => 'A_29', 'name' => 'KF_WISMA ASRI'],
            ['code' => 'A_30', 'name' => 'KF CAKRA RAYA'],
            ['code' => 'A_301I', 'name' => 'KF CAKRA RAYA RESEP'],
            ['code' => 'A_31', 'name' => 'KF KALI ABANG BEKASI'],
            ['code' => 'A_311I', 'name' => 'PPO KF SUZUKI PULOGADUNG'],
            ['code' => 'A_32', 'name' => 'KF JATI RAHAYU'],
            ['code' => 'A_35', 'name' => 'KDI MITRA PERKEBUNAN'],
            ['code' => 'A_36', 'name' => 'KF RAWA LUMBU'],
            ['code' => 'A_37', 'name' => 'KF SEPAT'],
            ['code' => 'A_41', 'name' => 'KF BOULEVARD'],
            ['code' => 'A_45', 'name' => 'APOTEK KF SUMMARECON'],
            ['code' => 'A_49', 'name' => 'KF KOTA SERANG'],
        ];
    }

    /**
     * Generate random color untuk UI
     */
    private function getRandomColor(): string
    {
        $colors = [
            '#3B82F6', // Blue
            '#10B981', // Green
            '#F59E0B', // Amber
            '#EF4444', // Red
            '#8B5CF6', // Purple
            '#EC4899', // Pink
            '#06B6D4', // Cyan
            '#84CC16', // Lime
        ];

        return $colors[array_rand($colors)];
    }
}