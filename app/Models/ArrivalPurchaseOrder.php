<?php

namespace App\Models;

use App\Models\Arrival\ArrivalTicket;
use App\Models\Master\Broker;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Division;
use App\Models\Master\Supplier;
use App\Models\Master\GrnNumber;
use App\Models\Procurement\PaymentRequestData;
use App\Models\Procurement\PurchaseFreight;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
// use App\Traits\HasApproval;
use App\Traits\HasApprovalPurchaseOrder;
use App\Models\ApprovalsModule\ApprovalModule;
use App\Models\ApprovalsModule\ApprovalRow;


class ArrivalPurchaseOrder extends Model
{
    use HasFactory, HasApprovalPurchaseOrder;

    protected $fillable = [
        'company_id',
        'contract_no',
        'contract_date',
        'defaulter',
        'decision_of_id',
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
        'broker_one_calculation_type',
        'broker_two_id',
        'broker_two_commission',
        'broker_two_calculation_type',
        'broker_three_id',
        'broker_three_commission',
        'broker_three_calculation_type',
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
        'contract_status',
        'created_by',
        'am_approval_status',
        'am_change_made'
    ];

    protected $casts = [
        'contract_date' => 'date',
        'delivery_date' => 'date',
    ];

    public function hasExpired()
    {
        return $this->status == 'draft' && $this->delivery_date < now();
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function purchaseFreight()
    {
        return $this->hasOne(PurchaseFreight::class, 'arrival_purchase_order_id');
    }
    public function truckSizeRange()
    {
        return $this->belongsTo(TruckSizeRange::class, 'truck_size_range_id');
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
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function decisionOfUser()
    {
        return $this->belongsTo(User::class, 'decision_of_id');
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

    // public function totalClosingTrucksQty()
    // {
    //     return $this->hasOne(ArrivalTicket::class, 'arrival_purchase_order_id')
    //         ->selectRaw('arrival_purchase_order_id, SUM(closing_trucks_qty) as total_closing_trucks_qty')
    //         ->groupBy('arrival_purchase_order_id');
    // }

    public function totalClosingTrucksQty()
    {
        return $this->arrivalTickets()
            ->selectRaw('arrival_purchase_order_id, SUM(closing_trucks_qty) as total_closing_trucks_qty')
            ->groupBy('arrival_purchase_order_id');
    }

    public function totalClosingTrucksQtyWithoutOwnTicket()
    {
        return $this->arrivalTickets()
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

    public function rejectedArrivalTickets()
    {
        return $this->hasMany(ArrivalTicket::class, 'arrival_purchase_order_id')
            ->where('first_qc_status', 'rejected');
    }

    public function rejectedHalfArrivalTickets()
    {
        return $this->hasMany(ArrivalTicket::class, 'arrival_purchase_order_id')
            ->where('document_approval_status', 'half_approved')
            ->where('arrival_slip_status', 'generated');
    }

    public function approvedArrivalTickets()
    {
        return $this->hasMany(ArrivalTicket::class, 'arrival_purchase_order_id')
            ->where('arrival_slip_status', 'generated');
    }
    public function grns()
    {
        return $this->hasMany(GrnNumber::class, 'purchase_order_id');
    }
    // public function rejectedTickets()
    // {
    //     return $this->hasMany(PurchaseTicket::class, 'purchase_order_id')
    //         ->where('first_qc_status', 'rejected');
    // }

    protected static function booted()
    {
        static::observe(\App\Observers\UniversalAuditObserver::class);
    }

    public function auditIdentifier(): string
    {
        return (string) ($this->contract_no ?: "#{$this->id}");
    }

    public function auditModelName(): string
    {
        return 'Purchase Order';
    }

    public function auditActionPrefix(): string
    {
        return 'purchase_order';
    }

    public static function auditLogConfig(): array
    {
        return [
            'title' => 'Purchase Order Audit Logs',
            'subtitle' => function ($model) {
                $parts = ["PO #{$model->contract_no}"];
                if ($model->supplier) {
                    $parts[] = 'Supplier: ' . ($model->supplier->name ?? $model->supplier->company_name ?? 'N/A');
                }
                if ($model->product) {
                    $parts[] = 'Product: ' . ($model->product->name ?? 'N/A');
                }
                $rate = $model->rate_per_mound ?? $model->rate_per_kg ?? $model->rate_per_100kg ?? $model->bag_rate;
                if ($rate) {
                    $parts[] = 'Rate: Rs. ' . number_format((float) $rate, 2);
                }
                if ($model->total_quantity) {
                    $parts[] = 'Qty: ' . number_format((float) $model->total_quantity, 2);
                } elseif ($model->no_of_trucks) {
                    $parts[] = 'Trucks: ' . $model->no_of_trucks;
                }
                if ($model->contract_date) {
                    $parts[] = 'Date: ' . (\Carbon\Carbon::parse($model->contract_date)->format('d M Y'));
                }
                if ($model->status) {
                    $parts[] = 'Status: ' . ucfirst($model->status);
                }
                return implode(' | ', $parts);
            },
            'empty_message' => 'No changes or updates have been recorded for this purchase order yet.',
            'skip_keys' => [
                'company_id',
                'created_by',
                'decision_of_id',
                'ref_no',
                'am_change_made',
                'deleted_at',
                'broker_one_name',
                'broker_two_name',
                'broker_three_name',
            ],
            'field_labels' => [
                'contract_no'                   => 'Contract #',
                'contract_date'                 => 'Contract Date',
                'delivery_date'                 => 'Delivery Date',
                'company_location_id'           => 'Location',
                'supplier_id'                   => 'Supplier',
                'product_id'                    => 'Product',
                'qc_product'                    => 'QC Product',
                'broker_id'                     => 'Broker',
                'broker_one_id'                 => 'Broker 1',
                'broker_one_commission'         => 'Broker 1 Commission',
                'broker_one_calculation_type'   => 'Broker 1 Calc Type',
                'broker_two_id'                 => 'Broker 2',
                'broker_two_commission'         => 'Broker 2 Commission',
                'broker_two_calculation_type'   => 'Broker 2 Calc Type',
                'broker_three_id'               => 'Broker 3',
                'broker_three_commission'       => 'Broker 3 Commission',
                'broker_three_calculation_type' => 'Broker 3 Calc Type',
                'supplier_commission'           => 'Supplier Commission',
                'division_id'                   => 'Division',
                'sauda_type_id'                 => 'Sauda Type',
                'truck_size_range_id'           => 'Truck Size Range',
                'calculation_type'              => 'PO Calculation Type',
                'purchase_type'                 => 'Purchase Type',
                'rate_per_kg'                   => 'Rate (per Kg)',
                'rate_per_mound'                => 'Rate (per Maund)',
                'rate_per_100kg'                => 'Rate (per 100Kg)',
                'line_type'                     => 'Line Type',
                'bag_weight'                    => 'Bag Weight',
                'bag_rate'                      => 'Bag Rate',
                'credit_days'                   => 'Credit Days',
                'no_of_trucks'                  => 'No of Trucks',
                'total_quantity'                => 'Total Quantity',
                'min_quantity'                  => 'Min Quantity',
                'max_quantity'                  => 'Max Quantity',
                'min_bags'                      => 'Min Bags',
                'max_bags'                      => 'Max Bags',
                'delivery_address'              => 'Delivery Address',
                'weighbridge_from'              => 'Weighbridge From',
                'is_replacement'                => 'Replacement',
                'defaulter'                     => 'Defaulter',
                'payment_term'                  => 'Payment Term',
                'truck_no'                      => 'Truck No',
                'remarks'                       => 'Remarks',
                'status'                        => 'Status',
                'contract_status'               => 'Contract Status',
                'am_approval_status'            => 'Approval Status',
            ],
            'relation_resolvers' => [
                'company_location_id'           => fn($val) => \App\Models\Master\CompanyLocation::find($val)?->name ?? $val,
                'supplier_id'                   => fn($val) => \App\Models\Master\Supplier::find($val)?->name ?? $val,
                'product_id'                    => fn($val) => \App\Models\Product::find($val)?->name ?? $val,
                'qc_product'                    => fn($val) => \App\Models\Product::find($val)?->name ?? $val,
                'division_id'                   => fn($val) => \App\Models\Master\Division::find($val)?->name ?? $val,
                'sauda_type_id'                 => fn($val) => \App\Models\SaudaType::find($val)?->name ?? $val,
                'truck_size_range_id'           => fn($val) => \App\Models\TruckSizeRange::find($val)?->name ?? $val,
                'broker_id'                     => fn($val) => \App\Models\Master\Broker::find($val)?->name ?? $val,
                'broker_one_id'                 => fn($val) => \App\Models\Master\Broker::find($val)?->name ?? $val,
                'broker_two_id'                 => fn($val) => \App\Models\Master\Broker::find($val)?->name ?? $val,
                'broker_three_id'               => fn($val) => \App\Models\Master\Broker::find($val)?->name ?? $val,
                'broker_one_calculation_type'   => fn($val) => $val ? ucfirst($val) . ' Wise' : 'N/A',
                'broker_two_calculation_type'   => fn($val) => $val ? ucfirst($val) . ' Wise' : 'N/A',
                'broker_three_calculation_type' => fn($val) => $val ? ucfirst($val) . ' Wise' : 'N/A',
                'is_replacement'                => fn($val) => ($val == 1 || $val === true || $val === '1') ? 'Yes' : 'No',
                'defaulter'                     => fn($val) => ($val == 1 || $val === true || $val === '1') ? 'Yes' : 'No',
                'am_approval_status'            => fn($val) => ucwords(str_replace(['_', '-'], ' ', (string) $val)),
                'status'                        => fn($val) => ucwords(str_replace(['_', '-'], ' ', (string) $val)),
                'contract_status'               => fn($val) => ucwords(str_replace(['_', '-'], ' ', (string) $val)),
                'calculation_type'              => fn($val) => ucwords(str_replace(['_', '-'], ' ', (string) $val)),
                'purchase_type'                 => fn($val) => ucwords(str_replace(['_', '-'], ' ', (string) $val)),
            ]
        ];
    }
}
