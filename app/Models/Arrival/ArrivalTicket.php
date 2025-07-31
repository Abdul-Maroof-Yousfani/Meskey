<?php

namespace App\Models\Arrival;

use App\Models\{
    ArrivalApprove,
    ArrivalPurchaseOrder,
    Product,
    SaudaType,
    User
};
use App\Models\ACL\Company;
use App\Models\FirstWeighbridge;
use App\Models\Master\{
    ArrivalTruckType,
    CompanyLocation,
    Station,
    Supplier,
    Miller
};
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArrivalTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unique_no',
        'company_id',
        'product_id',
        'qc_product',
        'location_id',
        // 'supplier_name',
        'broker_name',
        'bag_weight',
        'decision_id',
        'accounts_of_id',
        'accounts_of_name',
        'broker_id',
        'arrival_purchase_order_id',
        'sauda_type_id',
        'decision_making',
        'decision_making_time',
        'truck_type_id',
        'sample_money',
        'sample_money_type',
        'truck_no',
        'closing_trucks_qty',
        'bilty_no',
        'bags',
        'station_id',
        'station_name',
        'loading_date',
        'is_ticket_verified',
        'ticket_verified_by',
        'loading_weight',
        'remarks',
        'status',
        'first_weight',
        'second_weight',
        'net_weight',
        'arrived_net_weight',
        'lumpsum_deduction',
        'lumpsum_deduction_kgs',
        'is_lumpsum_deduction',
        'first_qc_status',
        'bilty_return_confirmation',
        'location_transfer_status',
        'second_qc_status',
        'document_approval_status',
        'first_weighbridge_status',
        'second_weighbridge_status',
        'freight_status',
        'arrival_slip_status',
        'bilty_return_reason',
        'bilty_return_attachment',
        'miller_id',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'loading_date'];

    protected $casts = [
        'loading_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function paymentRequestData()
    {
        return $this->hasMany(PaymentRequestData::class, 'ticket_id');
    }

    public function paymentRequests()
    {
        return $this->hasManyThrough(
            PaymentRequest::class,
            PaymentRequestData::class,
            'arrival_ticket_id',
            'payment_request_data_id',
            'id',
            'id'
        );
    }

    public function approvedPaymentRequests()
    {
        return $this->paymentRequests()
            ->whereHas('approvals', function ($query) {
                $query->where('status', 'approved');
            });
    }

    public function pendingPaymentRequests()
    {
        return $this->paymentRequests()
            ->whereDoesntHave('approvals')
            ->orWhereHas('approvals', function ($query) {
                $query->where('status', 'pending');
            });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function qcProduct()
    {
        return $this->belongsTo(Product::class, 'qc_product');
    }

    public function decisionBy()
    {
        return $this->belongsTo(User::class, 'decision_id');
    }

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function truckType()
    {
        return $this->belongsTo(ArrivalTruckType::class, 'truck_type_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(ArrivalPurchaseOrder::class, 'arrival_purchase_order_id');
    }

    public function saudaType()
    {
        return $this->belongsTo(SaudaType::class, 'sauda_type_id');
    }

    public function accountsOf()
    {
        return $this->belongsTo(Supplier::class, 'accounts_of_id');
    }

    public function broker()
    {
        return $this->belongsTo(Supplier::class, 'broker_id');
    }

    public function miller()
    {
        return $this->belongsTo(Miller::class, 'miller_id');
    }

    public function approvals()
    {
        return $this->hasOne(ArrivalApprove::class, 'arrival_ticket_id');
    }

    public function arrivalSamplingRequests()
    {
        return $this->hasMany(ArrivalSamplingRequest::class, 'arrival_ticket_id');
    }

    public function unloadingLocation()
    {
        return $this->hasOne(ArrivalLocationTransfer::class, 'arrival_ticket_id');
    }

    public function arrivalSlip()
    {
        return $this->hasOne(ArrivalSlip::class, 'arrival_ticket_id');
    }

    public function firstWeighbridge()
    {
        return $this->hasOne(FirstWeighbridge::class, 'arrival_ticket_id');
    }

    public function secondWeighbridge()
    {
        return $this->hasOne(SecondWeighbridge::class, 'arrival_ticket_id');
    }

    public function freight()
    {
        return $this->hasOne(Freight::class, 'arrival_ticket_id');
    }
}
