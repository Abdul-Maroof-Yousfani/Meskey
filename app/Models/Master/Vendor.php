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
        'unique_no',
        'name',
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
        'company_location_ids'
    ];

    protected $casts = [
        'company_location_ids' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
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
