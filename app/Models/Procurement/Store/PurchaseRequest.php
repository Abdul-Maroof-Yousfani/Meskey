<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\CompanyLocation;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class PurchaseRequest extends Model
{
    use HasFactory, HasApproval;

    protected $fillable = [
        'purchase_request_no',
        'company_id',
        'purchase_date',
        'location_id',
        'reference_no',
        'description',
        'purchase_request_status',
        'approved_user_name',
        'am_approval_status',
        'am_change_made',
        'status',
        'po_status',
        'created_by',
        'job_orders'
    ];

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function locations() {
        return $this->morphMany(Location::class, 'locationable');
    }

    public function PurchaseData()
    {
        return $this->hasMany(PurchaseRequestData::class);
    }

    public function quotation()
    {
        return $this->hasOne(PurchaseQuotation::class, 'purchase_request_id');
    }

    public function purchase_quotation()
    {
        return $this->belongsTo(PurchaseQuotation::class, 'purchase_request_id');
    }

    public function purchase_order() {
        return $this->belongsTo(PurchaseOrderData::class, "purchase_order_id", "id");
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
