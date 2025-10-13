<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CompanyInvitationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     * - Users table sudah terisi minimal 1 user per company sebagai invited_by
     */
    public function run(): void
    {
        // Ambil companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('⚠️  No companies found! Please run CompanySeeder first.');
            return;
        }

        // Ambil users untuk invited_by
        $users = DB::table('users')->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('⚠️  No users found! Please run UserSeeder first.');
            return;
        }

        $invitations = [];

        // Company 1: PT Kimia Farma Apotek
        $company1 = $companies->firstWhere('slug', 'kimia-farma-apotek');
        if ($company1) {
            $inviter1 = $users->where('company_id', $company1->id)->first();
            
            if ($inviter1) {
                // Pending invitation
                $invitations[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company1->id,
                    'invited_by' => $inviter1->id,
                    'email' => 'manager.apotek@kimiafarma.co.id',
                    'role' => 'manager',
                    'token' => Str::random(64),
                    'accepted_at' => null,
                    'expires_at' => Carbon::now()->addDays(7),
                    'created_at' => Carbon::now()->subDays(2),
                    'updated_at' => Carbon::now()->subDays(2),
                ];

                // Accepted invitation
                $invitations[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company1->id,
                    'invited_by' => $inviter1->id,
                    'email' => 'staff.apotek@kimiafarma.co.id',
                    'role' => 'staff',
                    'token' => Str::random(64),
                    'accepted_at' => Carbon::now()->subDays(5),
                    'expires_at' => Carbon::now()->addDays(2),
                    'created_at' => Carbon::now()->subDays(7),
                    'updated_at' => Carbon::now()->subDays(5),
                ];

                // Expired invitation
                $invitations[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company1->id,
                    'invited_by' => $inviter1->id,
                    'email' => 'expired.user@example.com',
                    'role' => 'staff',
                    'token' => Str::random(64),
                    'accepted_at' => null,
                    'expires_at' => Carbon::now()->subDays(3),
                    'created_at' => Carbon::now()->subDays(10),
                    'updated_at' => Carbon::now()->subDays(10),
                ];
            }
        }

        // Company 2: PT Kimia Farma Diagnostika
        $company2 = $companies->firstWhere('slug', 'kimia-farma-diagnostika');
        if ($company2) {
            $inviter2 = $users->where('company_id', $company2->id)->first();
            
            if ($inviter2) {
                // Admin invitation - Pending
                $invitations[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company2->id,
                    'invited_by' => $inviter2->id,
                    'email' => 'admin.lab@kfdiagnostik.com',
                    'role' => 'admin',
                    'token' => Str::random(64),
                    'accepted_at' => null,
                    'expires_at' => Carbon::now()->addDays(5),
                    'created_at' => Carbon::now()->subDays(1),
                    'updated_at' => Carbon::now()->subDays(1),
                ];

                // Manager invitation - Accepted
                $invitations[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company2->id,
                    'invited_by' => $inviter2->id,
                    'email' => 'manager.lab@kfdiagnostik.com',
                    'role' => 'manager',
                    'token' => Str::random(64),
                    'accepted_at' => Carbon::now()->subDays(10),
                    'expires_at' => Carbon::now()->addDays(4),
                    'created_at' => Carbon::now()->subDays(14),
                    'updated_at' => Carbon::now()->subDays(10),
                ];
            }
        }

        // Company 3: ALBAHJAH
        $company3 = $companies->firstWhere('slug', 'albahjah');
        if ($company3) {
            $inviter3 = $users->where('company_id', $company3->id)->first();
            
            if ($inviter3) {
                // Fresh invitation
                $invitations[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company3->id,
                    'invited_by' => $inviter3->id,
                    'email' => 'team@albahjah.com',
                    'role' => 'staff',
                    'token' => Str::random(64),
                    'accepted_at' => null,
                    'expires_at' => Carbon::now()->addDays(14),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                // Multiple staff invitations
                $invitations[] = [
                    'uuid' => Str::uuid(),
                    'company_id' => $company3->id,
                    'invited_by' => $inviter3->id,
                    'email' => 'accounting@albahjah.com',
                    'role' => 'staff',
                    'token' => Str::random(64),
                    'accepted_at' => null,
                    'expires_at' => Carbon::now()->addDays(14),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
        }

        if (!empty($invitations)) {
            DB::table('company_invitations')->insert($invitations);
            
            $this->command->info('✅ Company invitations seeded successfully!');
            $this->command->info("   Total invitations: " . count($invitations));
            
            $pending = collect($invitations)->whereNull('accepted_at')->where('expires_at', '>', Carbon::now())->count();
            $accepted = collect($invitations)->whereNotNull('accepted_at')->count();
            $expired = collect($invitations)->whereNull('accepted_at')->where('expires_at', '<=', Carbon::now())->count();
            
            $this->command->info("   - Pending: {$pending}");
            $this->command->info("   - Accepted: {$accepted}");
            $this->command->info("   - Expired: {$expired}");
        } else {
            $this->command->warn('⚠️  No invitations created. Check if users exist for each company.');
        }
    }
}