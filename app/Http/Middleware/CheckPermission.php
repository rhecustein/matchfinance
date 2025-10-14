<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * Check if user has specific permission(s) - Granular access control
     * 
     * This middleware provides fine-grained access control based on 
     * specific permissions rather than just roles. Allows for more 
     * flexible and scalable permission management.
     * 
     * Permission Format:
     * - resource.action (e.g., 'users.create', 'banks.edit', 'reports.export')
     * - Stored in users.permissions JSON column
     * 
     * Examples:
     * - users.view, users.create, users.edit, users.delete
     * - banks.manage, types.manage, categories.manage
     * - reports.view, reports.export
     * - statements.upload, statements.verify
     * - transactions.categorize, transactions.verify
     * 
     * Bypass Rules:
     * - Super Admin: Has ALL permissions (automatic bypass)
     * - Owner/Admin: Can be granted specific permissions
     * 
     * Usage Examples:
     * 
     * Single permission:
     * Route::middleware('permission:users.create')->post('/users');
     * 
     * Multiple permissions (user needs ANY of them):
     * Route::middleware('permission:reports.view,reports.export')->get('/reports');
     * 
     * Multiple permissions (user needs ALL of them):
     * Route::middleware('permission:users.create')->middleware('permission:users.edit');
     * 
     * In controller:
     * public function __construct()
     * {
     *     $this->middleware('permission:users.manage');
     * }
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions One or more permission strings
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        // Check authentication
        if (!$request->user()) {
            Log::warning('Unauthenticated access attempt to permission-protected route', [
                'route' => $request->path(),
                'required_permissions' => $permissions,
                'ip' => $request->ip(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        // Validate permissions parameter
        if (empty($permissions)) {
            Log::error('CheckPermission middleware called without permissions parameter', [
                'route' => $request->path(),
                'user_id' => $user->id,
            ]);

            abort(500, 'Permission middleware misconfigured: no permissions specified.');
        }

        // Super admin has ALL permissions (automatic bypass)
        if ($user->isSuperAdmin()) {
            Log::debug('Super Admin bypassed permission check', [
                'user_id' => $user->id,
                'route' => $request->path(),
                'required_permissions' => $permissions,
            ]);

            return $next($request);
        }

        // Check if user's account is active
        if (!$user->is_active || $user->is_suspended) {
            Log::warning('Inactive/suspended user attempted to access permission-protected route', [
                'user_id' => $user->id,
                'is_active' => $user->is_active,
                'is_suspended' => $user->is_suspended,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. Your account is currently ' . 
                ($user->is_suspended ? 'suspended' : 'inactive') . '.');
        }

        // Check if user belongs to a company (for non-super-admin users)
        if (!$user->company_id) {
            Log::error('User without company trying to access permission-protected resource', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. You must belong to a company to access this resource.');
        }

        // Check if company is active
        if ($user->company && !$user->company->is_active) {
            Log::warning('User from inactive company attempted access', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. Your company account is currently inactive. Please contact support.');
        }

        // Check if user has ANY of the specified permissions
        $hasPermission = false;
        $grantedPermission = null;

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                $hasPermission = true;
                $grantedPermission = $permission;
                break;
            }
        }

        // Access denied - log detailed information
        if (!$hasPermission) {
            Log::warning('Permission denied for user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'company_id' => $user->company_id,
                'required_permissions' => $permissions,
                'user_permissions' => $user->permissions,
                'route' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);

            // User-friendly error message
            $permissionList = count($permissions) > 1 
                ? 'one of these permissions: ' . implode(', ', $permissions)
                : 'the permission: ' . $permissions[0];

            abort(403, "Access denied. You do not have {$permissionList}.");
        }

        // Log successful permission check for audit
        Log::info('Permission check passed', [
            'user_id' => $user->id,
            'granted_permission' => $grantedPermission,
            'required_permissions' => $permissions,
            'route' => $request->path(),
            'method' => $request->method(),
        ]);

        return $next($request);
    }
}