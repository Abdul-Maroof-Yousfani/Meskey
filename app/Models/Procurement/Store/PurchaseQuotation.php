<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\CompanyLocation;
use App\Models\Master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Traits\HasApproval;

class PurchaseQuotation extends Model
{
    use HasFactory, HasApproval;

    protected $table = "purchase_quotations";
    protected $guarded = [];

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

     public function purchase_request()
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }


    public function quotation_data()
    {
        return $this->hasMany(PurchaseQuotationData::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
