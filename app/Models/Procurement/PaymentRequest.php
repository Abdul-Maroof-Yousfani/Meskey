<?php

namespace App\Models\Procurement;

use App\Models\Master\Account\GoodReceiveNote;
use App\Models\Master\Supplier;
use App\Models\PaymentVoucherData;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasApproval;

class PaymentRequest extends Model
{
    use HasFactory, HasApproval;

    protected $fillable = [
        'payment_request_data_id',
        'other_deduction_value',
        'is_advance_payment',
        'payment_to_type',
        'payment_to',
        'account_id',
        'other_deduction_kg',
        'request_type',
        'request_no',
        'supplier_id',
        'purchase_order_id',
        'purchase_order_receiving_id',
        'requested_by',
        'request_date',
        'approved_by',
        'approved_at',
        'description',
        'module_type',
        'payment_type',
        'status',
        'amount',
        'am_approval_status',
        'am_change_made'
    ];

    public function paymentRequestData()
    {
        return $this->belongsTo(PaymentRequestData::class);
    }

    public function approvals()
    {
        return $this->hasMany(PaymentRequestApproval::class);
    }

    public function paymentVoucherData()
    {
        return $this->hasOne(PaymentVoucherData::class, 'payment_request_id');
    }

    public function getApprovalStatusAttribute()
    {
        if ($this->approvals->isEmpty()) {
            return 'pending';
        }
        return $this->approvals->first()->status;
    }

    public function canBeApproved()
    {
        return $this->approval_status === 'pending';
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function grn()
    {
        return $this->belongsTo(PurchaseOrderReceiving::class, 'purchase_order_receiving_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
