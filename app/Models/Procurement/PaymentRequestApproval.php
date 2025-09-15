<?php

namespace App\Models\Procurement;

use App\Models\ArrivalPurchaseOrder;
use App\Models\PurchaseTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentRequestApproval extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_request_id',
        'payment_request_data_id',
        'ticket_id',
        'store_purchase_order_id',
        'grn_id',
        'purchase_order_id',
        'approver_id',
        'status',
        'remarks',
        'amount',
        'request_type'
    ];

    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    public function paymentRequestData()
    {
        return $this->belongsTo(PaymentRequestData::class);
    }

    public function ticket()
    {
        return $this->belongsTo(PurchaseTicket::class, 'ticket_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(ArrivalPurchaseOrder::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
