<?php

namespace App\Models;

use App\Models\Arrival\ArrivalTicket;
use App\Models\Master\Broker;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Division;
use App\Models\Master\Supplier;
use App\Models\Procurement\PaymentRequestData;
use App\Models\Procurement\PurchaseFreight;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ArrivalPurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'contract_no',
        'contract_date',
        'company_location_id',
        'sauda_type_id',
        'truck_size_range_id',
        'freight_status',
        'account_of',
        'supplier_id',
        'supplier_commission',
        'broker_one_name',
        'broker_two_name',
        'broker_three_name',
        'broker_one_id',
        'division_id',
        'decision_making',
        'decision_making_time',
        'lumpsum_deduction',
        'lumpsum_deduction_kgs',
        'is_lumpsum_deduction',
        'broker_one_commission',
        'broker_two_id',
        'broker_two_commission',
        'broker_three_id',
        'broker_three_commission',
        'cnic_no',
        'product_id',
        'qc_product',
        'line_type',
        'bag_weight',
        'bag_rate',
        'delivery_date',
        'credit_days',
        'purchase_type',
        'delivery_address',
        'rate_per_kg',
        'rate_per_mound',
        'rate_per_100kg',
        'calculation_type',
        'no_of_trucks',
        'total_quantity',
        'min_quantity',
        'max_quantity',
        'min_bags',
        'max_bags',
        'is_replacement',
        'weighbridge_from',
        'remarks',
        'status',
        'ref_no',
        'supplier_name',
        'purchaser_name',
        'contact_person_name',
        'mobile_no',
        'moisture',
        'chalky',
        'mixing',
        'red_rice',
        'other_params',
        'payment_term',
        'truck_no',
        'created_by',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'delivery_date' => 'date',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function purchaseFreight()
    {
        return $this->hasOne(PurchaseFreight::class, 'arrival_purchase_order_id');
    }

    public function arrivalTickets()
    {
        return $this->hasMany(ArrivalTicket::class, 'arrival_purchase_order_id');
    }

    public function paymentRequestData()
    {
        return $this->hasMany(PaymentRequestData::class, 'purchase_order_id');
    }

    public function broker()
    {
        return $this->belongsTo(Broker::class, 'broker_one_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function qcProduct()
    {
        return $this->belongsTo(Product::class, 'qc_product');
    }

    public function brokerTwo()
    {
        return $this->belongsTo(Broker::class, 'broker_two_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function brokerThree()
    {
        return $this->belongsTo(Broker::class, 'broker_three_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function saudaType()
    {
        return $this->belongsTo(SaudaType::class, 'sauda_type_id');
    }

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function purchaseSamplingRequests()
    {
        return $this->hasMany(PurchaseSamplingRequest::class, 'arrival_purchase_order_id');
    }

    public function purchaseFreights()
    {
        return $this->hasOne(PurchaseFreight::class, 'arrival_purchase_order_id');
    }

    public function totalLoadingWeight()
    {
        return $this->hasOne(PurchaseFreight::class, 'arrival_purchase_order_id')
            ->selectRaw('arrival_purchase_order_id, SUM(loading_weight) as total_loading_weight')
            ->groupBy('arrival_purchase_order_id');
    }

    public function totalArrivedNetWeight()
    {
        return $this->hasOne(ArrivalTicket::class, 'arrival_purchase_order_id')
            ->selectRaw('arrival_purchase_order_id, SUM(arrived_net_weight) as total_arrived_net_weight')
            ->groupBy('arrival_purchase_order_id');
    }

    public function totalClosingTrucksQty()
    {
        return $this->hasOne(ArrivalTicket::class, 'arrival_purchase_order_id')
            ->selectRaw('arrival_purchase_order_id, SUM(closing_trucks_qty) as total_closing_trucks_qty')
            ->groupBy('arrival_purchase_order_id');
    }

    public function ticketsWithArrivalSlipsCount()
    {
        return $this->hasMany(ArrivalTicket::class, 'arrival_purchase_order_id')
            ->selectRaw('arrival_purchase_order_id, count(*) as count')
            ->whereHas('arrivalSlip')
            ->groupBy('arrival_purchase_order_id');
    }

    public function stockInTransitTickets()
    {
        return $this->hasMany(PurchaseTicket::class, 'purchase_order_id')
            ->where('freight_status', 'completed')
            ->whereHas('purchaseFreight', function ($query) {
                $query->whereNotNull('truck_no')
                    ->whereNotNull('bilty_no');
            })
            ->where(function ($query) {
                $query->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('arrival_tickets')
                        ->join('purchase_freights', function ($join) {
                            $join->on('arrival_tickets.truck_no', '=', 'purchase_freights.truck_no')
                                ->on('arrival_tickets.bilty_no', '=', 'purchase_freights.bilty_no');
                        })
                        ->whereColumn('purchase_freights.purchase_ticket_id', 'purchase_tickets.id');
                })
                    ->orWhereExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('arrival_tickets')
                            ->join('purchase_freights', function ($join) {
                                $join->on('arrival_tickets.truck_no', '=', 'purchase_freights.truck_no')
                                    ->on('arrival_tickets.bilty_no', '=', 'purchase_freights.bilty_no');
                            })
                            ->whereColumn('purchase_freights.purchase_ticket_id', 'purchase_tickets.id')
                            ->where(function ($statusQuery) {
                                $statusQuery->whereNull('arrival_tickets.arrival_slip_status')
                                    ->orWhere('arrival_tickets.arrival_slip_status', '!=', 'generated');
                            });
                    });
            });
    }

    public function rejectedTickets()
    {
        return $this->hasMany(PurchaseTicket::class, 'purchase_order_id')
            ->where('first_qc_status', 'rejected');
    }
}
