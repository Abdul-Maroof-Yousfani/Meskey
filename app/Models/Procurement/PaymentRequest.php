<?php

namespace App\Models\Procurement;

use App\Models\ArrivalPurchaseOrder;
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
}
