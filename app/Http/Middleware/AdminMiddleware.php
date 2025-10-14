<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        if (!$user->isSuperAdmin() && !$user->company_id) {
            abort(403, 'You must belong to a company to access this resource.');
        }

        // FIXED: Check company status for non-super-admin
        if (!$user->isSuperAdmin() && $user->company && in_array($user->company->status, ['suspended', 'cancelled'])) {
            Log::warning('Admin access attempt from inactive company', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'company_status' => $user->company->status,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. Your company account is currently ' . $user->company->status . '. Please contact support.');
        }

        if (!$user->is_active || $user->is_suspended) {
            abort(403, 'Access denied. Your account is currently ' . 
                ($user->is_suspended ? 'suspended' : 'inactive') . '.');
        }

        return $next($request);
    }
}