<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            
            // PERBAIKAN: company_id nullable untuk Super Admin
            // Super Admin: company_id = NULL (akses ke semua companies)
            // Regular Users: company_id = ID company tertentu
            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade')
                ->comment('NULL for super admins, specific ID for company users');
            
            // Basic Info
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            
            // Password (nullable for OAuth users)
            $table->string('password')->nullable()->comment('Nullable for OAuth-only users');
            
            // OAuth Fields
            $table->string('provider')->nullable()->comment('OAuth provider: google, github, etc');
            $table->string('provider_id')->nullable()->comment('OAuth provider user ID');
            $table->text('provider_token')->nullable()->comment('OAuth access token (encrypted)');
            $table->text('provider_refresh_token')->nullable()->comment('OAuth refresh token (encrypted)');
            $table->timestamp('provider_token_expires_at')->nullable();
            $table->json('provider_data')->nullable()->comment('Additional provider data');
            
            // Profile
            $table->string('avatar')->nullable()->comment('Profile picture URL or path');
            $table->string('phone', 20)->nullable();
            $table->text('bio')->nullable();
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->string('locale', 10)->default('id');
            
            // Role & Permissions
            // PERBAIKAN: Tambahkan 'super_admin' ke enum role
            $table->enum('role', ['super_admin', 'owner', 'admin', 'manager', 'staff', 'user'])
                ->default('user')
                ->comment('super_admin = system-wide access, others = company-specific');
            $table->json('permissions')->nullable()->comment('Custom permissions per user');
            
            // Account Status
            $table->boolean('is_active')->default(true)->comment('Account active status');
            $table->boolean('is_suspended')->default(false)->comment('Account suspended by admin');
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            
            // Security
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            
            // Login Tracking
            $table->timestamp('last_login_at')->nullable();
            $table->ipAddress('last_login_ip')->nullable();
            $table->string('last_login_user_agent')->nullable();
            $table->integer('login_count')->default(0);
            
            // Password Security
            $table->timestamp('password_changed_at')->nullable();
            $table->boolean('require_password_change')->default(false);
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable()->comment('Account lockout until this time');
            
            // Preferences
            $table->json('preferences')->nullable()->comment('User UI preferences');
            $table->json('notification_settings')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'role']);
            $table->index(['company_id', 'is_active']);
            $table->index(['email', 'company_id']);
            $table->index(['provider', 'provider_id']);
            $table->index('last_login_at');
            $table->index('role'); // Index untuk filtering by role
            
            // Unique constraint untuk OAuth
            $table->unique(['provider', 'provider_id'], 'unique_provider_user');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
            
            $table->index(['user_id', 'last_activity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};