<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        if ($user->isSuperAdmin()) {
            abort(403, 'Super Admin should use admin panel routes.');
        }

        if (!$user->company_id) {
            abort(403, 'You must belong to a company to access this resource.');
        }

        if (!$user->isOwner()) {
            Log::warning('Unauthorized owner-only access attempt', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'company_id' => $user->company_id,
                'route' => $request->path(),
            ]);

            abort(403, 'Access denied. This action requires Company Owner privileges.');
        }

        if (!$user->company) {
            abort(500, 'System error: Company not found. Please contact support.');
        }

        $company = $user->company;

        // FIXED: Allow owner to access even if company is suspended/cancelled
        // Owner perlu akses untuk manage inactive companies
        if (in_array($company->status, ['suspended', 'cancelled'])) {
            Log::info('Owner accessing inactive company (allowed for management)', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_status' => $company->status,
                'route' => $request->path(),
            ]);

            session()->flash('warning', 'Your company account is currently ' . $company->status . '. Some features may be limited.');
        }

        if (!$user->is_active) {
            abort(403, 'Access denied. Your account is currently inactive. Please contact support.');
        }

        if ($user->is_suspended) {
            abort(403, 'Access denied. Your account has been suspended. Please contact support.');
        }

        return $next($request);
    }
}