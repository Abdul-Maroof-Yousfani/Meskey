<?php

namespace App\Models;

use App\Models\Master\Vendor;
use Illuminate\Database\Eloquent\Model;

class VendorCompanyBankDetail extends Model
{
    protected $fillable = [
        'vendor_id',
        'bank_name',
        'branch_name',
        'branch_code',
        'account_title',
        'account_number'
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
