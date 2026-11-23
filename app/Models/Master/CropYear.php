<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use App\Models\Acl\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CropYear extends Model
{
    use HasFactory, SoftDeletes, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $table = 'crop_years';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'status',
    ];

    // Optional: scope for active crop years
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Relation to company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
