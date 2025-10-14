<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /**
         * ================================================================
         * MIDDLEWARE ALIASES REGISTRATION
         * ================================================================
         * 
         * These aliases allow you to use short names in routes
         * instead of full class names.
         * 
         * Example: ->middleware('company.admin') instead of 
         * ->middleware(\App\Http\Middleware\EnsureCompanyAdmin::class)
         */
        $middleware->alias([
            /*
            |--------------------------------------------------------------------------
            | 🎭 Role-Based Middleware
            |--------------------------------------------------------------------------
            | For checking user roles (super_admin, owner, admin, manager, staff, user)
            */
            
            // Check specific role(s) - Most flexible
            // Usage: ->middleware('role:admin'), ->middleware('role:owner,admin')
            // Allows: Any role you specify (comma-separated for multiple)
            // Super Admin: Auto-bypass (has access to everything)
            'role' => \App\Http\Middleware\CheckRole::class,
            
            // Admin middleware - Company admin access
            // Usage: ->middleware('admin')
            // Allows: super_admin, owner, admin
            // Use for: Company-level admin operations
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            
            // Super Admin ONLY - System-wide access
            // Usage: ->middleware('super_admin')
            // Allows: super_admin ONLY
            // Use for: System management, company management, global settings
            'super_admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            
            /*
            |--------------------------------------------------------------------------
            | 🏢 Company/Tenant-Based Middleware
            |--------------------------------------------------------------------------
            | For company-specific access control (multi-tenant)
            */
            
            // Company Admin - Owner OR Admin (excludes super_admin)
            // Usage: ->middleware('company.admin')
            // Allows: owner, admin (NOT super_admin)
            // Use for: Master data, user management, company settings
            'company.admin' => \App\Http\Middleware\EnsureCompanyAdmin::class,
            
            // Company Owner ONLY - Highest company privilege
            // Usage: ->middleware('company.owner')
            // Allows: owner ONLY
            // Use for: Billing, subscription, ownership transfer, company deletion
            'company.owner' => \App\Http\Middleware\EnsureCompanyOwner::class,
            
            // Company Manager - Management level access
            // Usage: ->middleware('company.manager')
            // Allows: owner, admin, manager
            // Use for: Reports, data export, transaction verification
            'company.manager' => \App\Http\Middleware\EnsureCompanyManager::class,
            
            // Company Member - Any company user
            // Usage: ->middleware('company.member')
            // Allows: ALL company roles (owner, admin, manager, staff, user)
            // Use for: Basic company access, viewing data
            'company.member' => \App\Http\Middleware\EnsureCompanyMember::class,
            
            /*
            |--------------------------------------------------------------------------
            | 🔐 Permission-Based Middleware
            |--------------------------------------------------------------------------
            | For granular, permission-level access control
            */
            
            // Check specific permission(s) - Most granular control
            // Usage: ->middleware('permission:users.create')
            // Usage: ->middleware('permission:reports.view,reports.export')
            // Allows: Users with specific permission(s)
            // Super Admin: Auto-bypass (has all permissions)
            'permission' => \App\Http\Middleware\CheckPermission::class,
            
            /*
            |--------------------------------------------------------------------------
            | 💳 Subscription & Plan Middleware
            |--------------------------------------------------------------------------
            | For subscription-based access control
            */
            
            // Check if company has active subscription
            // Usage: ->middleware('subscription')
            // Blocks: Expired subscriptions, cancelled companies, expired trials
            // Super Admin: Auto-bypass
            'subscription' => \App\Http\Middleware\CheckActiveSubscription::class,
            
            // Check if plan has specific feature
            // Usage: ->middleware('feature:advanced_reports')
            // Usage: ->middleware('feature:ai_chat')
            // Blocks: Features not included in current plan
            // Super Admin: Auto-bypass (has all features)
            'feature' => \App\Http\Middleware\CheckPlanFeature::class,
            
            // Check if plan limit not exceeded
            // Usage: ->middleware('limit:max_users')
            // Usage: ->middleware('limit:max_transactions')
            // Blocks: Actions that would exceed plan limits
            // Super Admin: Auto-bypass (unlimited)
            'limit' => \App\Http\Middleware\CheckPlanLimit::class,
            
            /*
            |--------------------------------------------------------------------------
            | 🔧 Utility Middleware
            |--------------------------------------------------------------------------
            | For context, logging, and other utilities
            */
            
            // Set company context for multi-tenant operations
            // Usage: Applied globally via middleware group
            // Sets: config('app.current_company'), view()->share('currentCompany')
            'tenant' => \App\Http\Middleware\SetCompanyContext::class,
            
            // Log important user activities for audit trail
            // Usage: Applied globally or on specific routes
            // Logs: All write operations, critical actions
            'activity' => \App\Http\Middleware\LogUserActivity::class,
            
            // Prevent impersonation loops
            // Usage: Applied on impersonation routes
            // Prevents: Impersonating while already impersonating
            'prevent-impersonation-loop' => \App\Http\Middleware\PreventImpersonationLoop::class,
        ]);

        /**
         * ================================================================
         * GLOBAL MIDDLEWARE (Applied to ALL requests)
         * ================================================================
         * 
         * These middleware run on EVERY request to the application
         */
        $middleware->use([
            // Set company context for all authenticated requests
            \App\Http\Middleware\SetCompanyContext::class,
        ]);

        /**
         * ================================================================
         * WEB MIDDLEWARE GROUP
         * ================================================================
         * 
         * Applied to all web routes (routes/web.php)
         */
        $middleware->appendToGroup('web', [
            // Log user activities for audit trail
            \App\Http\Middleware\LogUserActivity::class,
        ]);

        /**
         * ================================================================
         * AUTHENTICATED MIDDLEWARE GROUP
         * ================================================================
         * 
         * Applied to routes that require authentication
         */
        $middleware->appendToGroup('auth', [
            // You can add middleware here that should run for all authenticated routes
        ]);

        /**
         * ================================================================
         * MIDDLEWARE PRIORITY (Execution Order)
         * ================================================================
         * 
         * Defines the order in which middleware should be executed.
         * Earlier in the list = executes first
         */
        $middleware->priority([
            // Laravel default middleware (must be first)
            \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            
            // Authentication (must come before authorization)
            \Illuminate\Auth\Middleware\Authenticate::class,
            \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            \Illuminate\Auth\Middleware\Authorize::class,
            
            // Context & Setup
            \App\Http\Middleware\SetCompanyContext::class,
            
            // Authorization (role-based, most restrictive first)
            \App\Http\Middleware\SuperAdminMiddleware::class,
            \App\Http\Middleware\EnsureCompanyOwner::class,
            \App\Http\Middleware\EnsureCompanyAdmin::class,
            \App\Http\Middleware\EnsureCompanyManager::class,
            \App\Http\Middleware\EnsureCompanyMember::class,
            \App\Http\Middleware\AdminMiddleware::class,
            \App\Http\Middleware\CheckRole::class,
            \App\Http\Middleware\CheckPermission::class,
            
            // Subscription & Features
            \App\Http\Middleware\CheckActiveSubscription::class,
            \App\Http\Middleware\CheckPlanFeature::class,
            \App\Http\Middleware\CheckPlanLimit::class,
            
            // Utilities (should run last)
            \App\Http\Middleware\LogUserActivity::class,
            \App\Http\Middleware\PreventImpersonationLoop::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /**
         * ================================================================
         * CUSTOM EXCEPTION HANDLING
         * ================================================================
         */
        
        // Handle 403 Forbidden (Access Denied)
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied.',
                    'error' => $e->getMessage(),
                ], 403);
            }
            
            // For web requests, show custom error page
            return response()->view('errors.403', [
                'exception' => $e,
                'message' => $e->getMessage(),
            ], 403);
        });

        // Handle 500 Internal Server Error
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($e instanceof \Illuminate\Database\QueryException) {
                \Log::error('Database error occurred', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'sql' => $e->getSql() ?? 'N/A',
                    'bindings' => $e->getBindings() ?? [],
                    'user_id' => auth()->id(),
                    'company_id' => auth()->user()->company_id ?? null,
                ]);
            }
        });

        // Log all exceptions for debugging
        $exceptions->report(function (\Throwable $e) {
            // Skip logging for specific exceptions
            $skipExceptions = [
                \Illuminate\Auth\AuthenticationException::class,
                \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            ];

            if (!in_array(get_class($e), $skipExceptions)) {
                \Log::error('Exception occurred', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'user_id' => auth()->id() ?? null,
                    'company_id' => auth()->user()->company_id ?? null,
                    'route' => request()->path() ?? 'N/A',
                    'method' => request()->method() ?? 'N/A',
                ]);
            }
        });
    })->create();