<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    private $now;
    private $defaultPassword;

    /**
     * Run the database seeds.
     * 
     * Note: Seeder ini membutuhkan:
     * - Companies table sudah terisi (CompanySeeder)
     */
    public function run(): void
    {
        $this->command->info('👥 Seeding Users...');
        
        $this->now = Carbon::now();
        $this->defaultPassword = Hash::make('password123');

        $users = [];

        // Create Super Admins (company_id = NULL)
        $users = array_merge($users, $this->createSuperAdmins());

        // Get companies
        $companies = DB::table('companies')->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('⚠️  No companies found! Only Super Admins created.');
        } else {
            $this->command->info('   Creating Company Users...');
            
            // Create users for each company
            foreach ($companies as $company) {
                $companyUsers = $this->createUsersForCompany($company);
                $users = array_merge($users, $companyUsers);
            }
        }

        // Insert all users
        if (!empty($users)) {
            DB::table('users')->insert($users);
            $this->displayStats($users);
        } else {
            $this->command->warn('⚠️  No users created.');
        }
    }

    /**
     * Create Super Admin accounts
     */
    private function createSuperAdmins(): array
    {
        $this->command->info('   Creating Super Admins...');

        return [
            // System Administrator
            $this->makeUser([
                'company_id' => null,
                'name' => 'System Administrator',
                'email' => 'admin@system.local',
                'password' => Hash::make('SuperAdmin@2024!'),
                'avatar' => 'avatars/system-admin.png',
                'phone' => '+62-811-0000-0001',
                'bio' => 'System Administrator - Full System Access',
                'role' => 'super_admin',
                'permissions' => ['*', 'system.manage', 'companies.manage', 'users.manage.all', 'settings.manage.all', 'database.backup', 'logs.view.all', 'security.manage'],
                'is_active' => true,
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => $this->now->copy()->subMonths(11),
                'login_count' => 1547,
                'created_at' => $this->now->copy()->subYears(1),
                'email_verified_at' => $this->now->copy()->subYears(1),
                'last_login_at' => $this->now->copy()->subHours(1),
            ]),

            // Developer Admin
            $this->makeUser([
                'company_id' => null,
                'name' => 'Developer Admin',
                'email' => 'dev@system.local',
                'password' => Hash::make('DevAdmin@2024!'),
                'avatar' => 'avatars/dev-admin.png',
                'phone' => '+62-811-0000-0002',
                'bio' => 'Developer & Database Administrator',
                'locale' => 'en',
                'role' => 'super_admin',
                'permissions' => ['*', 'system.develop', 'database.manage', 'api.manage', 'debugging.access'],
                'is_active' => true,
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => $this->now->copy()->subMonths(5),
                'login_count' => 892,
                'created_at' => $this->now->copy()->subMonths(6),
                'email_verified_at' => $this->now->copy()->subMonths(6),
                'last_login_at' => $this->now->copy()->subMinutes(30),
                'preferences' => ['theme' => 'dark', 'sidebar_collapsed' => true, 'items_per_page' => 100],
            ]),

            // Support Admin
            $this->makeUser([
                'company_id' => null,
                'name' => 'Support Admin',
                'email' => 'support@system.local',
                'password' => Hash::make('Support@2024!'),
                'phone' => '+62-811-0000-0003',
                'bio' => 'Customer Support & User Management',
                'role' => 'super_admin',
                'permissions' => ['users.manage.all', 'companies.view.all', 'support.manage', 'tickets.manage', 'reports.view.all'],
                'is_active' => true,
                'two_factor_enabled' => false,
                'login_count' => 234,
                'created_at' => $this->now->copy()->subMonths(3),
                'email_verified_at' => $this->now->copy()->subMonths(3),
                'last_login_at' => $this->now->copy()->subDays(2),
                'preferences' => ['theme' => 'light', 'sidebar_collapsed' => false, 'items_per_page' => 50],
            ]),
        ];
    }

    /**
     * Create users for specific company
     */
    private function createUsersForCompany($company): array
    {
        $users = [];

        switch ($company->slug) {
            case 'kimia-farma-apotek':
                $users = $this->createKimiaFarmaApotekUsers($company);
                break;
            
            case 'kimia-farma-diagnostika':
                $users = $this->createKimiaFarmaDiagnostikaUsers($company);
                break;
            
            case 'albahjah':
                $users = $this->createAlbahjahUsers($company);
                break;
            
            default:
                $users = $this->createDefaultCompanyUsers($company);
                break;
        }

        return $users;
    }

    /**
     * Users for PT Kimia Farma Apotek
     */
    private function createKimiaFarmaApotekUsers($company): array
    {
        return [
            // Owner
            $this->makeUser([
                'company_id' => $company->id,
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@kimiafarma.co.id',
                'password' => $this->defaultPassword,
                'avatar' => 'avatars/budi-santoso.jpg',
                'phone' => '+62-812-3456-7890',
                'bio' => 'Owner & Founder PT Kimia Farma Apotek',
                'role' => 'owner',
                'permissions' => ['*'],
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => $this->now->copy()->subMonths(5),
                'login_count' => 342,
                'created_at' => $this->now->copy()->subMonths(6),
                'email_verified_at' => $this->now->copy()->subMonths(6),
                'last_login_at' => $this->now->copy()->subHours(2),
            ]),

            // Admin
            $this->makeUser([
                'company_id' => $company->id,
                'name' => 'Siti Nurhaliza',
                'email' => 'siti.admin@kimiafarma.co.id',
                'password' => $this->defaultPassword,
                'avatar' => 'avatars/siti-nurhaliza.jpg',
                'phone' => '+62-813-9876-5432',
                'bio' => 'System Administrator',
                'role' => 'admin',
                'permissions' => ['users.manage', 'settings.manage', 'reports.view'],
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => $this->now->copy()->subMonths(4),
                'login_count' => 256,
                'created_at' => $this->now->copy()->subMonths(5),
                'email_verified_at' => $this->now->copy()->subMonths(5),
                'last_login_at' => $this->now->copy()->subHours(5),
                'preferences' => ['theme' => 'dark', 'sidebar_collapsed' => true, 'items_per_page' => 50],
            ]),

            // Manager
            $this->makeUser([
                'company_id' => $company->id,
                'name' => 'Ahmad Fauzi',
                'email' => 'ahmad.manager@kimiafarma.co.id',
                'password' => $this->defaultPassword,
                'phone' => '+62-815-1234-5678',
                'bio' => 'Branch Manager - Bekasi',
                'role' => 'manager',
                'permissions' => ['inventory.manage', 'transactions.view', 'staff.manage'],
                'login_count' => 189,
                'created_at' => $this->now->copy()->subMonths(4),
                'email_verified_at' => $this->now->copy()->subMonths(4),
                'last_login_at' => $this->now->copy()->subDays(1),
            ]),

            // Staff
            $this->makeUser([
                'company_id' => $company->id,
                'name' => 'Dewi Lestari',
                'email' => 'dewi.staff@kimiafarma.co.id',
                'password' => $this->defaultPassword,
                'avatar' => 'avatars/dewi-lestari.jpg',
                'phone' => '+62-817-9999-8888',
                'bio' => 'Apoteker',
                'role' => 'staff',
                'permissions' => ['transactions.create', 'inventory.view'],
                'login_count' => 145,
                'created_at' => $this->now->copy()->subMonths(3),
                'email_verified_at' => $this->now->copy()->subMonths(3),
                'last_login_at' => $this->now->copy()->subHours(8),
                'preferences' => ['theme' => 'light', 'sidebar_collapsed' => true, 'items_per_page' => 10],
            ]),
        ];
    }

    /**
     * Users for PT Kimia Farma Diagnostika
     */
    private function createKimiaFarmaDiagnostikaUsers($company): array
    {
        return [
            // Owner - Google OAuth
            $this->makeUser([
                'company_id' => $company->id,
                'name' => 'Dr. Rina Wijaya',
                'email' => 'rina.wijaya@kfdiagnostik.com',
                'password' => null,
                'provider' => 'google',
                'provider_id' => 'google_' . Str::random(20),
                'provider_token' => encrypt('google_access_token_' . Str::random(40)),
                'provider_refresh_token' => encrypt('google_refresh_token_' . Str::random(40)),
                'provider_token_expires_at' => $this->now->copy()->addHour(),
                'provider_data' => [
                    'google_id' => 'google_' . Str::random(20),
                    'avatar_url' => 'https://lh3.googleusercontent.com/a/default-user',
                    'verified_email' => true,
                ],
                'avatar' => 'https://lh3.googleusercontent.com/a/default-user',
                'phone' => '+62-811-2222-3333',
                'bio' => 'Medical Director',
                'role' => 'owner',
                'permissions' => ['*'],
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => $this->now->copy()->subMonths(3),
                'login_count' => 287,
                'created_at' => $this->now->copy()->subMonths(4),
                'email_verified_at' => $this->now->copy()->subMonths(4),
                'last_login_at' => $this->now->copy()->subMinutes(30),
            ]),

            // Admin
            $this->makeUser([
                'company_id' => $company->id,
                'name' => 'Hendra Kusuma',
                'email' => 'hendra.admin@kfdiagnostik.com',
                'password' => $this->defaultPassword,
                'phone' => '+62-812-7777-6666',
                'bio' => 'Laboratory Manager',
                'role' => 'admin',
                'permissions' => ['users.manage', 'lab.manage', 'reports.manage'],
                'login_count' => 198,
                'created_at' => $this->now->copy()->subMonths(3),
                'email_verified_at' => $this->now->copy()->subMonths(3),
                'last_login_at' => $this->now->copy()->subHours(3),
                'preferences' => ['theme' => 'dark', 'sidebar_collapsed' => false, 'items_per_page' => 50],
            ]),

            // Staff - Suspended
            $this->makeUser([
                'company_id' => $company->id,
                'name' => 'Eko Prasetyo',
                'email' => 'eko.suspended@kfdiagnostik.com',
                'password' => $this->defaultPassword,
                'phone' => '+62-819-5555-4444',
                'bio' => 'Lab Technician',
                'role' => 'staff',
                'permissions' => ['lab.execute', 'samples.view'],
                'is_active' => false,
                'is_suspended' => true,
                'suspended_at' => $this->now->copy()->subDays(7),
                'suspension_reason' => 'Violation of company policy - under investigation',
                'login_count' => 78,
                'created_at' => $this->now->copy()->subMonths(2),
                'email_verified_at' => $this->now->copy()->subMonths(2),
                'last_login_at' => $this->now->copy()->subDays(8),
            ]),
        ];
    }

    /**
     * Users for ALBAHJAH
     */
    private function createAlbahjahUsers($company): array
    {
        return [
            // Owner - GitHub OAuth
            $this->makeUser([
                'company_id' => $company->id,
                'name' => 'Muhammad Albahjah',
                'email' => 'muhammad@albahjah.com',
                'password' => $this->defaultPassword,
                'provider' => 'github',
                'provider_id' => 'github_' . Str::random(15),
                'provider_token' => encrypt('github_access_token_' . Str::random(40)),
                'provider_data' => [
                    'github_id' => 'github_' . Str::random(15),
                    'avatar_url' => 'https://avatars.githubusercontent.com/u/12345',
                    'bio' => 'Entrepreneur',
                    'company' => 'ALBAHJAH',
                ],
                'avatar' => 'https://avatars.githubusercontent.com/u/12345',
                'phone' => '+62-856-1111-2222',
                'bio' => 'Founder & CEO',
                'role' => 'owner',
                'permissions' => ['*'],
                'login_count' => 23,
                'created_at' => $this->now->copy()->subDays(2),
                'email_verified_at' => $this->now->copy()->subDays(2),
                'last_login_at' => $this->now->copy()->subHours(1),
                'preferences' => ['theme' => 'dark', 'sidebar_collapsed' => false, 'items_per_page' => 25],
            ]),

            // Staff
            $this->makeUser([
                'company_id' => $company->id,
                'name' => 'Fitri Handayani',
                'email' => 'fitri@albahjah.com',
                'password' => $this->defaultPassword,
                'phone' => '+62-857-3333-4444',
                'bio' => 'Accounting Staff',
                'role' => 'staff',
                'permissions' => ['transactions.create', 'reports.view'],
                'login_count' => 12,
                'created_at' => $this->now->copy()->subDay(),
                'email_verified_at' => $this->now->copy()->subDay(),
                'last_login_at' => $this->now->copy()->subHours(4),
            ]),
        ];
    }

    /**
     * Default users for other companies
     */
    private function createDefaultCompanyUsers($company): array
    {
        return [
            // Owner
            $this->makeUser([
                'company_id' => $company->id,
                'name' => 'Owner ' . $company->name,
                'email' => 'owner@' . Str::slug($company->name) . '.com',
                'password' => $this->defaultPassword,
                'role' => 'owner',
                'permissions' => ['*'],
                'created_at' => $this->now->copy()->subMonths(1),
                'email_verified_at' => $this->now->copy()->subMonths(1),
            ]),

            // Staff
            $this->makeUser([
                'company_id' => $company->id,
                'name' => 'Staff ' . $company->name,
                'email' => 'staff@' . Str::slug($company->name) . '.com',
                'password' => $this->defaultPassword,
                'role' => 'staff',
                'permissions' => ['transactions.create', 'reports.view'],
                'created_at' => $this->now->copy()->subWeeks(2),
                'email_verified_at' => $this->now->copy()->subWeeks(2),
            ]),
        ];
    }

    /**
     * Helper to create user array with defaults
     */
    private function makeUser(array $data): array
    {
        $defaults = [
            'uuid' => Str::uuid(),
            'company_id' => null,
            'name' => 'User',
            'email' => 'user@example.com',
            'email_verified_at' => $this->now,
            'password' => $this->defaultPassword,
            'provider' => null,
            'provider_id' => null,
            'provider_token' => null,
            'provider_refresh_token' => null,
            'provider_token_expires_at' => null,
            'provider_data' => null,
            'avatar' => null,
            'phone' => null,
            'bio' => null,
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'role' => 'user',
            'permissions' => null,
            'is_active' => true,
            'is_suspended' => false,
            'suspended_at' => null,
            'suspension_reason' => null,
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'last_login_at' => null,
            'last_login_ip' => '127.0.0.1',
            'last_login_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'login_count' => 0,
            'password_changed_at' => $this->now,
            'require_password_change' => false,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'preferences' => ['theme' => 'light', 'sidebar_collapsed' => false, 'items_per_page' => 25],
            'notification_settings' => ['email_notifications' => true, 'push_notifications' => false, 'sms_notifications' => false],
            'remember_token' => Str::random(10),
            'created_at' => $this->now,
            'updated_at' => $this->now,
            'deleted_at' => null,
        ];

        // Merge with provided data
        $user = array_merge($defaults, $data);

        // Convert arrays to JSON
        if (is_array($user['permissions'])) {
            $user['permissions'] = json_encode($user['permissions']);
        }
        if (is_array($user['preferences'])) {
            $user['preferences'] = json_encode($user['preferences']);
        }
        if (is_array($user['notification_settings'])) {
            $user['notification_settings'] = json_encode($user['notification_settings']);
        }
        if (is_array($user['provider_data'])) {
            $user['provider_data'] = json_encode($user['provider_data']);
        }

        return $user;
    }

    /**
     * Display statistics
     */
    private function displayStats(array $users): void
    {
        $collection = collect($users);
        
        $totalUsers = $collection->count();
        $superAdmins = $collection->whereNull('company_id')->count();
        $companyUsers = $collection->whereNotNull('company_id')->count();
        
        $this->command->newLine();
        $this->command->info('✅ Users seeded successfully!');
        $this->command->info("   Total users: {$totalUsers}");
        $this->command->info("   - Super Admins: {$superAdmins}");
        $this->command->info("   - Company Users: {$companyUsers}");
        
        // Stats by role
        $owners = $collection->where('role', 'owner')->count();
        $admins = $collection->where('role', 'admin')->count();
        $managers = $collection->where('role', 'manager')->count();
        $staff = $collection->where('role', 'staff')->count();
        
        $this->command->newLine();
        $this->command->info('👥 Users by Role:');
        $this->command->info("   - Super Admins: {$superAdmins}");
        $this->command->info("   - Owners: {$owners}");
        $this->command->info("   - Admins: {$admins}");
        $this->command->info("   - Managers: {$managers}");
        $this->command->info("   - Staff: {$staff}");
        
        // Security features
        $oauth = $collection->whereNotNull('provider')->count();
        $twoFactor = $collection->where('two_factor_enabled', true)->count();
        $suspended = $collection->where('is_suspended', true)->count();
        
        $this->command->newLine();
        $this->command->info('🔐 Security Features:');
        $this->command->info("   - OAuth users: {$oauth}");
        $this->command->info("   - 2FA enabled: {$twoFactor}");
        $this->command->info("   - Suspended: {$suspended}");
        
        // Login credentials
        $this->command->newLine();
        $this->command->info('🔑 Login Credentials:');
        $this->command->newLine();
        $this->command->warn('SUPER ADMINS (Access All Companies):');
        $this->command->line('   System Admin : admin@system.local / SuperAdmin@2024!');
        $this->command->line('   Developer    : dev@system.local / DevAdmin@2024!');
        $this->command->line('   Support      : support@system.local / Support@2024!');
        $this->command->newLine();
        $this->command->info('COMPANY USERS:');
        $this->command->line('   Default password: password123');
        
        $this->command->newLine();
        $this->command->warn('⚠️  SECURITY REMINDER:');
        $this->command->info('   Change all default passwords in production!');
    }
}