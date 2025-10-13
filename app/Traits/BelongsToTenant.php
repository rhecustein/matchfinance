<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait BelongsToTenant
 * 
 * Automatically scope all queries to the current authenticated user's company (tenant)
 * and prevent accidental data leakage between tenants.
 * 
 * Usage:
 * - Add this trait to any model that should be tenant-scoped
 * - Ensure the model has a 'company_id' column
 * 
 * Features:
 * - Auto-scope all queries to current tenant
 * - Auto-assign company_id on create
 * - Prevent cross-tenant data access
 * - Helper methods for tenant management
 */
trait BelongsToTenant
{
    /**
     * Boot the trait - adds global scope and model events
     */
    protected static function bootBelongsToTenant()
    {
        // Add global scope to automatically filter by company_id
        static::addGlobalScope('company', function (Builder $builder) {
            if (static::shouldApplyTenantScope()) {
                $builder->where($builder->getModel()->getTable() . '.company_id', static::getTenantId());
            }
        });

        // Auto-assign company_id when creating new records
        static::creating(function (Model $model) {
            if (static::shouldApplyTenantScope() && !$model->company_id) {
                $model->company_id = static::getTenantId();
            }
        });

        // Prevent updating company_id to avoid security issues
        static::updating(function (Model $model) {
            if (static::shouldApplyTenantScope() && $model->isDirty('company_id')) {
                // Check if user is trying to change company_id
                $originalCompanyId = $model->getOriginal('company_id');
                $newCompanyId = $model->company_id;
                
                if ($originalCompanyId != $newCompanyId && !static::canChangeCompany()) {
                    // Revert company_id to original value
                    $model->company_id = $originalCompanyId;
                    
                    \Log::warning('Attempted to change company_id', [
                        'model' => get_class($model),
                        'id' => $model->id,
                        'original' => $originalCompanyId,
                        'attempted' => $newCompanyId,
                        'user_id' => auth()->id()
                    ]);
                }
            }
        });
    }

    /**
     * Relationship to Company
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Get the current tenant/company ID
     * 
     * @return int|null
     */
    protected static function getTenantId()
    {
        // Priority order for getting tenant ID:
        
        // 1. From config (set by middleware)
        if ($companyId = config('app.current_company.id')) {
            return $companyId;
        }
        
        // 2. From authenticated user
        if (auth()->check() && auth()->user()->company_id) {
            return auth()->user()->company_id;
        }
        
        // 3. From request (set by middleware)
        if (request()->has('current_company') && request()->current_company) {
            return request()->current_company->id;
        }
        
        return null;
    }

    /**
     * Check if tenant scope should be applied
     * 
     * @return bool
     */
    protected static function shouldApplyTenantScope()
    {
        // Don't apply scope if:
        // 1. No tenant ID available
        // 2. Running in console (migrations, seeders) and not in queue
        // 3. Explicitly disabled for this request
        
        if (!static::getTenantId()) {
            return false;
        }

        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            // Allow scope in queue jobs but not in migrations/seeders
            return app()->bound('queue.connection');
        }

        // Check if scope is disabled for current request
        if (request()->has('_disable_tenant_scope')) {
            return false;
        }

        return true;
    }

    /**
     * Check if current user can change company_id
     * Only super admins or specific roles should be able to do this
     * 
     * @return bool
     */
    protected static function canChangeCompany()
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Add your logic here - who can transfer data between companies?
        // Example: Only super admins
        return $user->hasRole('super_admin') ?? false;
    }

    /**
     * Scope: Get all records for a specific tenant
     * 
     * @param Builder $query
     * @param int $companyId
     * @return Builder
     */
    public function scopeForTenant(Builder $query, $companyId)
    {
        return $query->withoutGlobalScope('company')
                     ->where($this->getTable() . '.company_id', $companyId);
    }

    /**
     * Scope: Get all records from all tenants (removes global scope)
     * Use with EXTREME caution!
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeAllTenants(Builder $query)
    {
        return $query->withoutGlobalScope('company');
    }

    /**
     * Get all records for the current tenant without any other constraints
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function allForCurrentTenant()
    {
        return static::withoutGlobalScopes()->where('company_id', static::getTenantId())->get();
    }

    /**
     * Check if this model belongs to the current tenant
     * 
     * @return bool
     */
    public function belongsToCurrentTenant()
    {
        return $this->company_id === static::getTenantId();
    }

    /**
     * Check if this model belongs to a specific tenant
     * 
     * @param int $companyId
     * @return bool
     */
    public function belongsToTenant($companyId)
    {
        return $this->company_id == $companyId;
    }

    /**
     * Scope: Only get records created by current user in current tenant
     * Useful for "my items" functionality
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeOwnedByCurrentUser(Builder $query)
    {
        if (!auth()->check()) {
            return $query->whereRaw('1 = 0'); // Return empty result
        }

        // Check if model has created_by or user_id column
        if (schema()->hasColumn($this->getTable(), 'created_by')) {
            return $query->where('created_by', auth()->id());
        }

        if (schema()->hasColumn($this->getTable(), 'user_id')) {
            return $query->where('user_id', auth()->id());
        }

        return $query;
    }

    /**
     * Create a new model for a specific tenant
     * Useful for seeding or admin operations
     * 
     * @param int $companyId
     * @param array $attributes
     * @return static
     */
    public static function createForTenant($companyId, array $attributes = [])
    {
        $attributes['company_id'] = $companyId;
        return static::create($attributes);
    }

    /**
     * Move this model to another tenant
     * DANGEROUS - Use with extreme caution!
     * 
     * @param int $newCompanyId
     * @return bool
     */
    public function moveToTenant($newCompanyId)
    {
        if (!static::canChangeCompany()) {
            throw new \Exception('You do not have permission to move records between tenants');
        }

        \Log::info('Moving record to different tenant', [
            'model' => get_class($this),
            'id' => $this->id,
            'from_company' => $this->company_id,
            'to_company' => $newCompanyId,
            'user_id' => auth()->id()
        ]);

        return $this->withoutGlobalScope('company')
                    ->where('id', $this->id)
                    ->update(['company_id' => $newCompanyId]);
    }

    /**
     * Get the name of the tenant column
     * Override this method if your column name is different
     * 
     * @return string
     */
    public function getTenantColumn()
    {
        return 'company_id';
    }

    /**
     * Ensure model has company_id column
     * Throws exception if not found
     */
    protected static function ensureTenantColumnExists()
    {
        $instance = new static;
        $table = $instance->getTable();
        $column = $instance->getTenantColumn();

        if (!schema()->hasColumn($table, $column)) {
            throw new \Exception(
                "Table '{$table}' does not have a '{$column}' column. " .
                "Please add the column or remove the BelongsToTenant trait."
            );
        }
    }
}