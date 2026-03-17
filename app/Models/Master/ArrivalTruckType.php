<?php

namespace App\Models\Master;

use App\Models\Acl\Company;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ArrivalTruckType extends Model
{
    use SoftDeletes;


    protected $fillable = [
        'company_id',
        'name',
        'sample_money',
        'weighbridge_amount',
        'description',
        'status',
    ];

    // Relationship with the Company model (if you have one)
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function locationAmounts()
    {
        return $this->belongsToMany(CompanyLocation::class, 'truck_type_location_amounts', 'truck_type_id', 'company_location_id')
            ->withPivot('amount')
            ->withTimestamps();
    }
}
