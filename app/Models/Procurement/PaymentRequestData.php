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
        'freight_payment_id',
        'request_type',
        'supplier_name',
        'contract_rate',
        'account_id',
        'min_contract_range',
        'max_contract_range',
        'supplier_id',
        'store_purchase_order_id',
        'grn_id',
        'description',
        'payment_type',
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
        'exempt',
        'freight_rs',
        'freight_per_ton',
        'loading_kanta',
        'arrived_kanta',
        'other_plus_labour',
        'dehari_plus_extra',
        'market_comm',
        'over_weight_ded',
        'godown_penalty',
        'other_minus_labour',
        'extra_minus_ded',
        'commission_percent_ded',
        'commission_amount',
        'paid_amount',
        'remaining_amount',
        'advance_freight',
        'notes',
        'attachment',
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
