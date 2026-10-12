<?php

namespace App\Models\Master;

use App\Models\Acl\Company;
use App\Models\ArrivalPurchaseOrder;
use App\Models\VendorCompanyBankDetail;
use App\Models\VendorOwnerBankDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'unique_no',
        'name',
        'rate',
        'account_id',
        'company_name',
        'owner_name',
        'owner_mobile_no',
        'owner_cnic_no',
        'next_to_kin',
        'next_to_kin_mobile_no',
        'owner_bank_detail',
        'company_bank_detail',
        'prefix',
        'email',
        'phone',
        'address',
        'ntn',
        'stn',
        'attachment',
        'status',
        'company_location_ids',
        'arrival_location_ids'
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'company_location_ids' => 'array',
        'arrival_location_ids' => 'array',
    ];

    public function scopeVendors($query)
    {
        return $query->where('type', 'vendor');
    }

    public function scopeClearingAgents($query)
    {
        return $query->where('type', 'clearing_agent');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function account()
    {
        return $this->belongsTo(\App\Models\Master\Account\Account::class, 'account_id');
    }

    public function companyBankDetails()
    {
        return $this->hasMany(VendorCompanyBankDetail::class,);
    }

    public function ownerBankDetails()
    {
        return $this->hasMany(VendorOwnerBankDetail::class,);
    }

    public function arrivalPurchaseOrders()
    {
        return $this->hasMany(ArrivalPurchaseOrder::class, 'vendor_id');
    }

    public function scopeForUserLocation($query, $user)
    {
        $companyLocation = $user->companyLocation;
        $locationId = $companyLocation ? $companyLocation->id : 1;

        return $query->whereJsonContains('company_location_ids', $locationId);

        return $query;
    }
}
