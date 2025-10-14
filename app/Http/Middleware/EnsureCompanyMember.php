<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyMember
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user->company_id) {
            abort(403, 'You must belong to a company to access this resource.');
        }

        if (!$user->relationLoaded('company')) {
            $user->load('company');
        }

        if (!$user->company) {
            abort(500, 'System error: Company not found. Please contact support.');
        }

        $company = $user->company;

        // FIXED: Check company status
        if (in_array($company->status, ['suspended', 'cancelled'])) {
            Log::warning('Access attempt from inactive company', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'company_name' => $company->name,
                'company_status' => $company->status,
                'route' => $request->path(),
            ]);

            abort(403, 'Your company account has been ' . $company->status . '. Please contact support.');
        }

        if (!$user->is_active || $user->is_suspended) {
            abort(403, 'Your account is currently ' . 
                ($user->is_suspended ? 'suspended' : 'inactive') . '.');
        }

        return $next($request);
    }
}
