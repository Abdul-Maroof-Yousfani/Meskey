<?php

namespace App\Models\Sales;

use App\Models\Master\Customer;
use App\Models\Master\PayType;
use App\Models\Procurement\Store\FactoryLocation;
use App\Models\Procurement\Store\Location;
use App\Models\Procurement\Store\SectionLocation;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory, HasApproval;

    protected $fillable = [
        "delivery_date",
        "order_date",
        "reference_no",
        "so_reference_no",
        "customer_id",
        "inquiry_id",
        "sauda_type",
        "payment_term_id",
        "company_id",
        "am_approval_status",
        "pay_type_id",
        "token_money",
        "remarks",
        "contact_person",
        "arrival_location_id",
        "arrival_sub_location_id",
        "created_by",
        "am_change_made",
        "transporter_used",
        "broker_id",
        "parent_user_id",
        "commission_per_kg",
        "receipt_voucher_item_ids",
        "payment_on_kaanta"
    ];

    protected $casts = [
        'receipt_voucher_item_ids' => 'array',
    ];

    public function parent_user() {
        return $this->belongsTo(\App\Models\User::class, "parent_user_id");
    }

    protected function paymentTermId(): Attribute{
        return Attribute::make(
            get: function($value) {
                if($this->pay_type_id == 8) {
                    return $value;
                }
                return null;
            }
        );
    }

    public function sales_order_data() {
        return $this->hasMany(SalesOrderData::class, "sale_order_id");
    }


    public function sale_inquiry() {
        return $this->belongsTo(SalesInquiry::class, "inquiry_id", "id");
    }

     public function locations() {
        return $this->morphMany(Location::class, 'locationable');
    }

    public function factories() {
        return $this->morphMany(FactoryLocation::class, 'factoryable');
    }

    public function sections() {
        return $this->morphMany(SectionLocation::class, 'sectionable');
    }

    public function delivery_orders() {
        return $this->hasMany(DeliveryOrder::class, "so_id");
    }

    public function delivery_order_transactions() {
        return $this->hasMany(DeliveryOrderTransaction::class, "sale_order_id");
    }

    public function pay_type() {
        return $this->belongsTo(PayType::class, "pay_type_id");
    }
    public function delivery_order_data() {
        return $this->hasMany(DeliveryOrderData::class, "so_data_id");
    }

    public function customer() {
        return $this->belongsTo(Customer::class, "customer_id");
    }
    public function saleSecondWeighbridge() {
        return $this->hasMany(SecondWeighbridge::class, "sale_order_id");
    }

    public function logistics() {
        return $this->hasMany(Logistics::class, "sale_order_id");
    }

    public function broker() {
        return $this->belongsTo(\App\Models\Master\Broker::class, "broker_id");
    }

    protected static function booted()
    {
        static::updated(function ($salesOrder) {
            if ($salesOrder->isDirty('am_approval_status') && $salesOrder->am_approval_status === 'approved') {
                if ($salesOrder->payment_on_kaanta) {
                    self::autoCreateDeliveryOrder($salesOrder);
                }
            }
        });
    }

    private static function autoCreateDeliveryOrder(SalesOrder $salesOrder)
    {
        $exists = \App\Models\Sales\DeliveryOrder::where('so_id', $salesOrder->id)
            ->where('is_auto_created_from_so', true)
            ->exists();

        if ($exists) {
            return;
        }

        $companyLocation = $salesOrder->locations->first();
        
        $deliveryOrder = \App\Models\Sales\DeliveryOrder::create([
            'customer_id' => $salesOrder->customer_id,
            'so_id' => $salesOrder->id,
            'advance_amount' => 0,
            'withhold_amount' => 0,
            'withhold_for_rv_id' => null,
            'dispatch_date' => $salesOrder->order_date,
            'reference_no' => app(\App\Http\Controllers\Sales\DeliveryOrderController::class)->getNumber(new \Illuminate\Http\Request(), null, $salesOrder->order_date),
            'ref_no' => null,
            'payment_term_id' => $salesOrder->payment_term_id ?? (\App\Models\PaymentTerm::first())->id,
            'sauda_type' => $salesOrder->sauda_type,
            'location_id' => $companyLocation ? $companyLocation->location_id : null,
            'arrival_location_id' => $salesOrder->arrival_location_id,
            'sub_arrival_location_id' => $salesOrder->arrival_sub_location_id,
            'delivery_date' => $salesOrder->delivery_date,
            'line_desc' => "Auto-generated from Payment on Kaanta SO",
            'remarks' => "Auto-generated from Payment on Kaanta SO",
            'company_id' => $salesOrder->company_id,
            'created_by' => $salesOrder->created_by,
            'am_approval_status' => 'approved',
            'so_withhold_percentage' => 0,
            'so_held_amount' => 0,
            'is_auto_created_from_so' => true
        ]);

        foreach ($salesOrder->sales_order_data as $soData) {
            $deliveryOrder->delivery_order_data()->create([
                'item_id' => $soData->item_id,
                'qty' => $soData->qty,
                'rate' => $soData->rate,
                'brand_id' => $soData->brand_id,
                'bag_type' => $soData->bag_type,
                'bag_size' => $soData->bag_size,
                'no_of_bags' => $soData->no_of_bags,
                'pack_size' => $soData->pack_size,
                'so_data_id' => $soData->id,
                "description" => $soData->description ?? ""
            ]);
        }
    }
}
