<?php

namespace App\Models\Master;

use App\Models\SupplierCompanyBankDetail;
use App\Models\SupplierOwnerBankDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    // Define fillable attributes
    protected $fillable = [
        'company_id',
        'unique_no',
        'name',
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

    // Define the relationship with Company (assuming the 'Company' model exists)
    public function company()
    {
        return $this->belongsTo(Company::class); // Adjust the model name if needed
    }

    public function companyBankDetails()
    {
        return $this->hasMany(SupplierCompanyBankDetail::class,);
    }

    public function ownerBankDetails()
    {
        return $this->hasMany(SupplierOwnerBankDetail::class,);
    }

    public function scopeForUserLocation($query, $user)
    {
        $companyLocation = $user->companyLocation;
        $locationId = $companyLocation ? $companyLocation->id : 1;

        return $query->whereJsonContains('company_location_ids', $locationId);

        return $query;
    }

    // Add casts for company_locations
    protected $casts = [
        'company_location_ids' => 'array',
    ];
}
