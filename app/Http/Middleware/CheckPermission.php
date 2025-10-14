<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        if (!$request->user()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        if (empty($permissions)) {
            abort(500, 'Permission middleware misconfigured: no permissions specified.');
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user->is_active || $user->is_suspended) {
            abort(403, 'Access denied. Your account is currently ' . 
                ($user->is_suspended ? 'suspended' : 'inactive') . '.');
        }

        if (!$user->company_id) {
            abort(403, 'Access denied. You must belong to a company to access this resource.');
        }

        // FIXED: Check company status
        if ($user->company && in_array($user->company->status, ['suspended', 'cancelled'])) {
            Log::warning('User from inactive company attempted access', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'company_status' => $user->company->status,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. Your company account is currently ' . $user->company->status . '. Please contact support.');
        }

        // Check permissions...
        $requiredPermissions = array_map('trim', explode('|', $permissions));
        
        if (!$user->hasAnyPermission($requiredPermissions)) {
            abort(403, 'Access denied. You do not have the required permissions.');
        }

        return $next($request);
    }
}