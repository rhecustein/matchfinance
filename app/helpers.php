<?php

use App\Models\Company;
use App\Models\User;

if (!function_exists('tenant')) {
    /**
     * Get the current tenant (company) instance
     *
     * @return Company|null
     */
    function tenant(): ?Company
    {
        // Priority 1: From config (set by middleware)
        if ($company = config('app.current_company')) {
            return $company instanceof Company ? $company : null;
        }

        // Priority 2: From authenticated user
        if (auth()->check() && auth()->user()->company_id) {
            return Company::find(auth()->user()->company_id);
        }

        // Priority 3: From request (set by middleware)
        if (request()->has('current_company') && request()->current_company instanceof Company) {
            return request()->current_company;
        }

        return null;
    }
}

if (!function_exists('tenantId')) {
    /**
     * Get the current tenant (company) ID
     *
     * @return int|null
     */
    function tenantId(): ?int
    {
        // Priority 1: From config
        if ($companyId = config('app.current_company.id')) {
            return $companyId;
        }

        // Priority 2: From authenticated user
        if (auth()->check() && auth()->user()->company_id) {
            return auth()->user()->company_id;
        }

        // Priority 3: From request
        if (request()->has('current_company') && request()->current_company) {
            $company = request()->current_company;
            return $company instanceof Company ? $company->id : null;
        }

        return null;
    }
}

if (!function_exists('tenantName')) {
    /**
     * Get the current tenant (company) name
     *
     * @return string|null
     */
    function tenantName(): ?string
    {
        $tenant = tenant();
        return $tenant?->name;
    }
}

if (!function_exists('tenantSlug')) {
    /**
     * Get the current tenant (company) slug
     *
     * @return string|null
     */
    function tenantSlug(): ?string
    {
        $tenant = tenant();
        return $tenant?->slug;
    }
}

if (!function_exists('hasTenant')) {
    /**
     * Check if there is a current tenant
     *
     * @return bool
     */
    function hasTenant(): bool
    {
        return tenantId() !== null;
    }
}

if (!function_exists('belongsToCurrentTenant')) {
    /**
     * Check if a model belongs to the current tenant
     *
     * @param mixed $model
     * @return bool
     */
    function belongsToCurrentTenant($model): bool
    {
        if (!$model || !hasTenant()) {
            return false;
        }

        if (!property_exists($model, 'company_id') && !isset($model->company_id)) {
            return false;
        }

        return $model->company_id === tenantId();
    }
}

if (!function_exists('isOwner')) {
    /**
     * Check if the current user is the owner of the current tenant
     *
     * @return bool
     */
    function isOwner(): bool
    {
        if (!auth()->check() || !hasTenant()) {
            return false;
        }

        $user = auth()->user();
        $tenant = tenant();

        // Check if user has owner role or is the company owner
        return $user->hasRole('owner') || 
               ($tenant && $tenant->user_id === $user->id);
    }
}

if (!function_exists('isAdmin')) {
    /**
     * Check if the current user is an admin of the current tenant
     *
     * @return bool
     */
    function isAdmin(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        return $user->hasRole('admin') || $user->hasRole('owner');
    }
}

if (!function_exists('isManager')) {
    /**
     * Check if the current user is a manager of the current tenant
     *
     * @return bool
     */
    function isManager(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        return $user->hasRole('manager') || 
               $user->hasRole('admin') || 
               $user->hasRole('owner');
    }
}

if (!function_exists('isStaff')) {
    /**
     * Check if the current user is staff of the current tenant
     *
     * @return bool
     */
    function isStaff(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return auth()->user()->hasRole('staff');
    }
}

if (!function_exists('tenantSubscription')) {
    /**
     * Get the current tenant's active subscription
     *
     * @return \App\Models\CompanySubscription|null
     */
    function tenantSubscription()
    {
        $tenant = tenant();
        
        if (!$tenant) {
            return null;
        }

        return $tenant->activeSubscription;
    }
}

if (!function_exists('tenantPlan')) {
    /**
     * Get the current tenant's plan
     *
     * @return \App\Models\Plan|null
     */
    function tenantPlan()
    {
        $subscription = tenantSubscription();
        
        if (!$subscription) {
            return null;
        }

        return $subscription->plan;
    }
}

if (!function_exists('tenantHasFeature')) {
    /**
     * Check if the current tenant has a specific feature
     *
     * @param string $feature
     * @return bool
     */
    function tenantHasFeature(string $feature): bool
    {
        $plan = tenantPlan();
        
        if (!$plan) {
            return false;
        }

        $features = $plan->features ?? [];
        
        return isset($features[$feature]) && $features[$feature] !== false;
    }
}

if (!function_exists('tenantFeatureLimit')) {
    /**
     * Get the current tenant's feature limit
     *
     * @param string $feature
     * @param int $default
     * @return int
     */
    function tenantFeatureLimit(string $feature, int $default = 0): int
    {
        $plan = tenantPlan();
        
        if (!$plan) {
            return $default;
        }

        $features = $plan->features ?? [];
        
        return $features[$feature] ?? $default;
    }
}

if (!function_exists('tenantCanUseFeature')) {
    /**
     * Check if tenant can use a feature based on current usage
     *
     * @param string $feature
     * @param int $currentUsage
     * @return bool
     */
    function tenantCanUseFeature(string $feature, int $currentUsage = 0): bool
    {
        $limit = tenantFeatureLimit($feature, -1);
        
        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        return $currentUsage < $limit;
    }
}

if (!function_exists('tenantStatus')) {
    /**
     * Get the current tenant status
     *
     * @return string|null
     */
    function tenantStatus(): ?string
    {
        $tenant = tenant();
        return $tenant?->status;
    }
}

if (!function_exists('tenantIsActive')) {
    /**
     * Check if the current tenant is active
     *
     * @return bool
     */
    function tenantIsActive(): bool
    {
        return tenantStatus() === 'active';
    }
}

if (!function_exists('tenantIsTrial')) {
    /**
     * Check if the current tenant is on trial
     *
     * @return bool
     */
    function tenantIsTrial(): bool
    {
        return tenantStatus() === 'trial';
    }
}

if (!function_exists('tenantIsSuspended')) {
    /**
     * Check if the current tenant is suspended
     *
     * @return bool
     */
    function tenantIsSuspended(): bool
    {
        return tenantStatus() === 'suspended';
    }
}

if (!function_exists('tenantTrialDaysRemaining')) {
    /**
     * Get the number of trial days remaining for the current tenant
     *
     * @return int|null
     */
    function tenantTrialDaysRemaining(): ?int
    {
        $tenant = tenant();
        
        if (!$tenant || !$tenant->trial_ends_at) {
            return null;
        }

        $daysRemaining = now()->diffInDays($tenant->trial_ends_at, false);
        
        return max(0, (int) $daysRemaining);
    }
}

if (!function_exists('setCurrentTenant')) {
    /**
     * Set the current tenant (for middleware or testing)
     *
     * @param Company|int|null $tenant
     * @return void
     */
    function setCurrentTenant($tenant): void
    {
        if (is_int($tenant)) {
            $tenant = Company::find($tenant);
        }

        if ($tenant instanceof Company) {
            config(['app.current_company' => $tenant]);
            config(['app.current_company.id' => $tenant->id]);
            request()->merge(['current_company' => $tenant]);
        }
    }
}

if (!function_exists('clearCurrentTenant')) {
    /**
     * Clear the current tenant
     *
     * @return void
     */
    function clearCurrentTenant(): void
    {
        config(['app.current_company' => null]);
        config(['app.current_company.id' => null]);
    }
}

if (!function_exists('switchTenant')) {
    /**
     * Switch to a different tenant (if user has access)
     *
     * @param int $companyId
     * @return bool
     */
    function switchTenant(int $companyId): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();
        
        // Check if user has access to this company
        if ($user->company_id !== $companyId && !$user->hasRole('super_admin')) {
            return false;
        }

        $company = Company::find($companyId);
        
        if (!$company) {
            return false;
        }

        setCurrentTenant($company);
        
        return true;
    }
}

if (!function_exists('tenantUrl')) {
    /**
     * Generate a URL with tenant context
     *
     * @param string $path
     * @param array $parameters
     * @return string
     */
    function tenantUrl(string $path = '', array $parameters = []): string
    {
        $tenant = tenant();
        
        if (!$tenant) {
            return url($path, $parameters);
        }

        // If using subdomains
        if ($tenant->subdomain) {
            $domain = $tenant->subdomain . '.' . config('app.domain');
            return url($path, $parameters)->setHost($domain);
        }

        return url($path, $parameters);
    }
}

if (!function_exists('tenantRoute')) {
    /**
     * Generate a route with tenant context
     *
     * @param string $name
     * @param array $parameters
     * @param bool $absolute
     * @return string
     */
    function tenantRoute(string $name, array $parameters = [], bool $absolute = true): string
    {
        return route($name, $parameters, $absolute);
    }
}