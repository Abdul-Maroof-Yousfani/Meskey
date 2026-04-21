<?php

namespace App\Models\Procurement\Store;

use Illuminate\Database\Eloquent\Model;
use App\Models\Production\BagRequest;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\CompanyLocation;
use App\Models\Acl\Company;
use App\Models\User;

class BagIssuance extends Model
{
    protected $fillable = [
        'issuance_number',
        'issuance_date',
        'bag_request_id',
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
        'issuance_date' => 'date',
        'job_order_ids' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(BagIssuanceItem::class);
    }

    public function bagRequest()
    {
        return $this->belongsTo(BagRequest::class);
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(ArrivalLocation::class);
    }

    public function gala()
    {
        return $this->belongsTo(ArrivalSubLocation::class, 'gala_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class);
    }
}
