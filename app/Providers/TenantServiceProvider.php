<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use App\Models\Company;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register tenant instance as singleton
        $this->app->singleton('tenant', function ($app) {
            return tenant();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Ensure tenant scope is applied when needed
        $this->bootTenantScope();

        // Register view composers
        $this->bootViewComposers();

        // Register validation rules
        $this->bootValidationRules();

        // Register macros
        $this->bootMacros();
    }

    /**
     * Boot tenant scope globally
     */
    protected function bootTenantScope(): void
    {
        // This will be handled by the BelongsToTenant trait
        // No additional work needed here
    }

    /**
     * Register view composers for tenant data
     */
    protected function bootViewComposers(): void
    {
        // Share tenant data with all views
        view()->composer('*', function ($view) {
            $view->with('currentTenant', tenant());
            $view->with('tenantId', tenantId());
            $view->with('tenantName', tenantName());
            $view->with('isOwner', isOwner());
            $view->with('isAdmin', isAdmin());
            $view->with('isManager', isManager());
        });
    }

    /**
     * Register custom validation rules
     */
    protected function bootValidationRules(): void
    {
        // Validate that a record belongs to current tenant
        Validator::extend('belongs_to_tenant', function ($attribute, $value, $parameters, $validator) {
            if (empty($parameters[0])) {
                return false;
            }

            $modelClass = $parameters[0];
            
            if (!class_exists($modelClass)) {
                return false;
            }

            $model = $modelClass::find($value);
            
            if (!$model) {
                return false;
            }

            return belongsToCurrentTenant($model);
        }, 'The selected :attribute does not belong to your company.');

        // Validate tenant feature limit
        Validator::extend('within_tenant_limit', function ($attribute, $value, $parameters, $validator) {
            if (empty($parameters[0])) {
                return false;
            }

            $feature = $parameters[0];
            $limit = tenantFeatureLimit($feature, -1);

            // -1 means unlimited
            if ($limit === -1) {
                return true;
            }

            return $value <= $limit;
        }, 'The :attribute exceeds your plan limit.');

        // Validate tenant has feature
        Validator::extend('tenant_has_feature', function ($attribute, $value, $parameters, $validator) {
            if (empty($parameters[0])) {
                return false;
            }

            $feature = $parameters[0];
            return tenantHasFeature($feature);
        }, 'This feature is not available in your current plan.');
    }

    /**
     * Register helpful macros
     */
    protected function bootMacros(): void
    {
        // Add macro to Builder for easy tenant filtering
        \Illuminate\Database\Query\Builder::macro('forTenant', function ($tenantId = null) {
            $id = $tenantId ?? tenantId();
            return $this->where('company_id', $id);
        });

        // Add macro to Collection for tenant filtering
        \Illuminate\Support\Collection::macro('forTenant', function ($tenantId = null) {
            $id = $tenantId ?? tenantId();
            return $this->filter(function ($item) use ($id) {
                return isset($item->company_id) && $item->company_id == $id;
            });
        });

        // Add macro to check if route belongs to tenant
        \Illuminate\Routing\Route::macro('belongsToTenant', function () {
            return $this->hasParameter('tenant') || 
                   $this->hasParameter('company') ||
                   request()->has('current_company');
        });
    }
}