<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use App\Models\Master\ArrivalSubLocation;
use App\Models\User;
use App\Models\Acl\Company;
use App\Models\Master\CompanyLocation;

class BagRequest extends Model
{
    protected $fillable = [
        'request_number',
        'request_date',
        'arrival_location_id',
        'gala_id',
        'job_order_ids',
        'remarks',
        'company_id',
        'company_location_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'request_date' => 'date',
        'job_order_ids' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(BagRequestItem::class);
    }

    public function gala()
    {
        return $this->belongsTo(ArrivalSubLocation::class, 'gala_id');
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalLocation::class, 'arrival_location_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class);
    }

    public function issuances()
    {
        return $this->hasMany(\App\Models\Procurement\Store\BagIssuance::class);
    }
}
