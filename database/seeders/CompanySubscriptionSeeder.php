<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CompanySubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     * - Plans table sudah terisi (PlanSeeder)
     */
    public function run(): void
    {
        // Ambil companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('⚠️  No companies found! Please run CompanySeeder first.');
            return;
        }

        // Ambil plans
        $plans = DB::table('plans')->get();
        
        if ($plans->isEmpty()) {
            $this->command->warn('⚠️  No plans found! Please run PlanSeeder first.');
            return;
        }

        $subscriptions = [];

        // ============================================
        // Company 1: PT Kimia Farma Apotek
        // Status: Active - Professional Yearly Plan
        // ============================================
        $company1 = $companies->firstWhere('slug', 'kimia-farma-apotek');
        $professionalYearly = $plans->firstWhere('slug', 'professional-yearly');
        
        if ($company1 && $professionalYearly) {
            $subscriptions[] = [
                'uuid' => Str::uuid(),
                'company_id' => $company1->id,
                'plan_id' => $professionalYearly->id,
                'status' => 'active',
                'starts_at' => Carbon::now()->subMonths(6),
                'ends_at' => Carbon::now()->addMonths(6),
                'cancelled_at' => null,
                'created_at' => Carbon::now()->subMonths(6),
                'updated_at' => Carbon::now(),
            ];
        }

        // ============================================
        // Company 2: PT Kimia Farma Diagnostika
        // Status: Active - Business Monthly Plan
        // ============================================
        $company2 = $companies->firstWhere('slug', 'kimia-farma-diagnostika');
        $businessMonthly = $plans->firstWhere('slug', 'business');
        
        if ($company2 && $businessMonthly) {
            $subscriptions[] = [
                'uuid' => Str::uuid(),
                'company_id' => $company2->id,
                'plan_id' => $businessMonthly->id,
                'status' => 'active',
                'starts_at' => Carbon::now()->subMonths(4),
                'ends_at' => Carbon::now()->addMonth(),
                'cancelled_at' => null,
                'created_at' => Carbon::now()->subMonths(4),
                'updated_at' => Carbon::now(),
            ];
        }

        // ============================================
        // Company 3: ALBAHJAH
        // Status: Active - Free Plan (Trial)
        // ============================================
        $company3 = $companies->firstWhere('slug', 'albahjah');
        $freePlan = $plans->firstWhere('slug', 'free');
        
        if ($company3 && $freePlan) {
            $subscriptions[] = [
                'uuid' => Str::uuid(),
                'company_id' => $company3->id,
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'starts_at' => Carbon::now()->subDays(2),
                'ends_at' => Carbon::now()->addDays(14), // Trial 14 hari
                'cancelled_at' => null,
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now(),
            ];
        }

        // ============================================
        // HISTORY SUBSCRIPTIONS (Optional)
        // Untuk menunjukkan riwayat upgrade/downgrade
        // ============================================
        
        // Kimia Farma Apotek: History - Started with Starter
        if ($company1) {
            $starterMonthly = $plans->firstWhere('slug', 'starter');
            if ($starterMonthly) {
                $subscriptions[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company1->id,
                    'plan_id' => $starterMonthly->id,
                    'status' => 'expired',
                    'starts_at' => Carbon::now()->subMonths(9),
                    'ends_at' => Carbon::now()->subMonths(6),
                    'cancelled_at' => Carbon::now()->subMonths(6),
                    'created_at' => Carbon::now()->subMonths(9),
                    'updated_at' => Carbon::now()->subMonths(6),
                ];
            }
        }

        // Kimia Farma Diagnostika: History - Had Professional before Business
        if ($company2) {
            $professionalMonthly = $plans->firstWhere('slug', 'professional');
            if ($professionalMonthly) {
                $subscriptions[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company2->id,
                    'plan_id' => $professionalMonthly->id,
                    'status' => 'expired',
                    'starts_at' => Carbon::now()->subMonths(8),
                    'ends_at' => Carbon::now()->subMonths(4),
                    'cancelled_at' => Carbon::now()->subMonths(4),
                    'created_at' => Carbon::now()->subMonths(8),
                    'updated_at' => Carbon::now()->subMonths(4),
                ];
            }
        }

        // ============================================
        // EXAMPLE: Cancelled & Past Due (Optional)
        // ============================================
        
        // Example: Cancelled subscription
        if ($company3) {
            $starterMonthly = $plans->firstWhere('slug', 'starter');
            if ($starterMonthly) {
                $subscriptions[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company3->id,
                    'plan_id' => $starterMonthly->id,
                    'status' => 'cancelled',
                    'starts_at' => Carbon::now()->subMonths(2),
                    'ends_at' => Carbon::now()->subMonth(),
                    'cancelled_at' => Carbon::now()->subDays(20),
                    'created_at' => Carbon::now()->subMonths(2),
                    'updated_at' => Carbon::now()->subDays(20),
                ];
            }
        }

        if (!empty($subscriptions)) {
            DB::table('company_subscriptions')->insert($subscriptions);
            
            $this->command->info('✅ Company subscriptions seeded successfully!');
            $this->command->info("   Total subscriptions: " . count($subscriptions));
            
            $active = collect($subscriptions)->where('status', 'active')->count();
            $expired = collect($subscriptions)->where('status', 'expired')->count();
            $cancelled = collect($subscriptions)->where('status', 'cancelled')->count();
            
            $this->command->info("   - Active: {$active}");
            $this->command->info("   - Expired: {$expired}");
            $this->command->info("   - Cancelled: {$cancelled}");
            
            // Detail per company
            $this->command->newLine();
            $this->command->info('📊 Subscription Details:');
            
            if ($company1 && $professionalYearly) {
                $this->command->info("   • PT Kimia Farma Apotek → Professional Yearly (Active)");
            }
            
            if ($company2 && $businessMonthly) {
                $this->command->info("   • PT Kimia Farma Diagnostika → Business Monthly (Active)");
            }
            
            if ($company3 && $freePlan) {
                $this->command->info("   • ALBAHJAH → Free Plan (Trial - 14 days)");
            }
        } else {
            $this->command->warn('⚠️  No subscriptions created. Check if companies and plans exist.');
        }
    }
}