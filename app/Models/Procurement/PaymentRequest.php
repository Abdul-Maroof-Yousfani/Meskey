<?php

namespace App\Models\Procurement;

use App\Models\PaymentVoucherData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_request_data_id',
        'request_type',
        'amount'
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
}
