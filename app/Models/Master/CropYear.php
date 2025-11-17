<?php

namespace App\Models\Master;

use App\Models\Acl\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CropYear extends Model
{
    use HasFactory, SoftDeletes;

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
