<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\CompanyLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_request_no',
        'company_id',
        'purchase_date',
        'location_id',
        'reference_no',
        'description',
        'purchase_request_status',
        'approved_user_name',
        'status',
        'po_status'
    ];

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function PurchaseData()
    {
        return $this->hasMany(PurchaseRequestData::class);
    }

    public function quotation()
    {
        return $this->hasOne(PurchaseQuotation::class, 'purchase_request_id');
    }
}
