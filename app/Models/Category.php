<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'type_id',
        'slug',
        'name',
        'description',
        'color',
        'sort_order'
    ];

    protected $casts = [
        'sort_order' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (empty($category->uuid)) {
                $category->uuid = (string) Str::uuid();
            }
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function getRouteKeyName() { return 'uuid'; }

    // Relationships
    public function company() { return $this->belongsTo(Company::class); }
    public function type() { return $this->belongsTo(Type::class); }
    public function subCategories() { return $this->hasMany(SubCategory::class)->orderBy('sort_order'); }
    public function transactions() { return $this->hasMany(StatementTransaction::class); }
    public function transactionCategories() { return $this->hasMany(TransactionCategory::class); }
    
    // For products (if you use category_product pivot)
    public function products() {
        return $this->belongsToMany(Product::class, 'category_product');
    }
    
    public function scopeForType($query, $typeId) { 
        return $query->where('type_id', $typeId); 
    }
    
    public function scopeOrdered($query) { 
        return $query->orderBy('sort_order'); 
    }
}