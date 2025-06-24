<?php

namespace App\Models;

use App\Models\Procurement\PaymentRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentVoucherData extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_voucher_id',
        'payment_request_id',
        'amount',
        'description'
    ];

    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class, 'payment_voucher_id');
    }

    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class, 'payment_request_id');
    }
}
