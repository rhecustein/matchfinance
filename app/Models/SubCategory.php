<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubCategory extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'category_id',
        'name',
        'description',
        'priority',
        'sort_order'
    ];

    protected $casts = [
        'priority' => 'integer',
        'sort_order' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($subCategory) {
            if (empty($subCategory->uuid)) {
                $subCategory->uuid = (string) Str::uuid();
            }
            
            // Auto-assign company_id from category
            if (empty($subCategory->company_id) && $subCategory->category) {
                $subCategory->company_id = $subCategory->category->company_id;
            }
        });
    }

    public function getRouteKeyName() { return 'uuid'; }

    // Relationships
    public function company() { return $this->belongsTo(Company::class); }
    public function category() { return $this->belongsTo(Category::class); }
    
    public function keywords() { 
        return $this->hasMany(Keyword::class)->orderBy('priority', 'desc'); 
    }
    
    public function activeKeywords() {
        return $this->hasMany(Keyword::class)
                    ->where('is_active', true)
                    ->orderBy('priority', 'desc');
    }
    
    public function transactions() { 
        return $this->hasMany(StatementTransaction::class); 
    }
    
    public function transactionCategories() { 
        return $this->hasMany(TransactionCategory::class); 
    }
    
    // Helper: Get full path Type > Category > SubCategory
    public function getFullPath()
    {
        return $this->category->type->name . ' > ' . 
               $this->category->name . ' > ' . 
               $this->name;
    }
    
    public function scopeHighPriority($query) {
        return $query->where('priority', '>=', 7)->orderBy('priority', 'desc');
    }
}