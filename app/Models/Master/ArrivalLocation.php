<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ArrivalLocation extends Model
{
    use SoftDeletes;

    
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'status',
    ];

    // Relationship with the Company model (if you have one)
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
