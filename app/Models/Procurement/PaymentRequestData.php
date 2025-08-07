<?php

namespace App\Models\Procurement;

use App\Models\Arrival\ArrivalTicket;
use App\Models\ArrivalPurchaseOrder;
use App\Models\PurchaseTicket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequestData extends Model
{
    use HasFactory;

    protected $table = "payment_request_datas";

    protected $fillable = [
        'purchase_order_id',
        'ticket_id',
        'request_type',
        'supplier_name',
        'contract_rate',
        'min_contract_range',
        'max_contract_range',
        'is_advance_payment',
        'is_loading',
        'truck_no',
        'loading_date',
        'bilty_no',
        'brokery_amount',
        'broker_id',
        'station',
        'no_of_bags',
        'loading_weight',
        'avg_rate',
        'bag_weight',
        'bag_weight_total',
        'bag_weight_amount',
        'bag_rate',
        'bag_rate_amount',
        'loading_weighbridge_amount',
        'total_amount',
        'module_type',
        'paid_amount',
        'remaining_amount',
        'advance_freight',
        'notes'
    ];

    protected $casts = [
        'loading_date' => 'date',
        'is_loading' => 'boolean'
    ];

    public function paymentRequests()
    {
        return $this->hasMany(PaymentRequest::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(ArrivalPurchaseOrder::class, 'purchase_order_id');
    }

    public function purchaseTicket()
    {
        return $this->belongsTo(PurchaseTicket::class, 'ticket_id');
    }

    public function arrivalTicket()
    {
        return $this->belongsTo(ArrivalTicket::class, 'ticket_id');
    }

    public function samplingResults()
    {
        return $this->hasMany(PaymentRequestSamplingResult::class);
    }
}
