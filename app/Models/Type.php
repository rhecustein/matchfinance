<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\BelongsToTenant;

// ====================================
// TYPE MODEL
// ====================================
class Type extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'description',
        'sort_order'
    ];

    protected $casts = [
        'sort_order' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
    }

    public function getRouteKeyName() { return 'uuid'; }

    // Relationships
    public function company() { return $this->belongsTo(Company::class); }
    public function categories() { return $this->hasMany(Category::class)->orderBy('sort_order'); }
    public function transactions() { return $this->hasMany(StatementTransaction::class); }
    
    public function scopeOrdered($query) { return $query->orderBy('sort_order'); }
}