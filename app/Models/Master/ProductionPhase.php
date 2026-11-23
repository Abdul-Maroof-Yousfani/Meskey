<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionPhase extends Model
{
    use HasFactory, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $table = 'production_phases';

    protected $fillable = [
        'key',
        'name',
        'category',
        'description',
        'status',
    ];

    /**
     * Scope active phases
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Find a phase by its unique key
     */
    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }
}
