<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Acl\Company;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\CompanyLocation;

class ArrivalSubLocation extends Model
{
    use SoftDeletes;


    protected $fillable = [
        'company_id',
        'company_location_id',
        'arrival_location_id',
        'name',
        'description',
        'status',
    ];

    // Relationship with the Company model (if you have one)
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class);
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(ArrivalLocation::class);
    }
}
