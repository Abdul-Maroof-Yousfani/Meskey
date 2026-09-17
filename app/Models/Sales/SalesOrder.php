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
    use HasFactory;
    use HasApproval {
        onApprovalComplete as traitOnApprovalComplete;
        onApprovalRejected as traitOnApprovalRejected;
        onApprovalReverted as traitOnApprovalReverted;
        getApprovalStatus as traitGetApprovalStatus;
    }

    protected $fillable = [
        "contract_status",
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
        "seller_commission_per_kg",
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
        static::updating(function ($salesOrder) {
            $originalStatus = strtolower($salesOrder->getOriginal('am_approval_status') ?? '');
            $newStatus = strtolower($salesOrder->am_approval_status ?? '');
            if ($salesOrder->isDirty('am_approval_status')) {
                // Allow approved -> pending transition if a delivery date amendment is in progress
                $isAmendment = \Illuminate\Support\Facades\Cache::store('database')->has("so_amendment_{$salesOrder->id}");
                if ($originalStatus === 'approved' && $newStatus === 'pending' && $isAmendment) {
                    // Allowed for delivery date amendment re-approval cycle
                } elseif (in_array($originalStatus, ['approved', 'rejected'])) {
                    throw new \Exception("Sale Order is already {$originalStatus} and status cannot be changed.");
                }
                if ($originalStatus === 'reverted' && $newStatus !== 'pending') {
                    throw new \Exception("Sale Order is reverted and cannot be {$newStatus} directly. It must be updated to pending first.");
                }
            }
        });

        static::deleting(function ($salesOrder) {
            $status = strtolower($salesOrder->am_approval_status ?? '');
            if (in_array($status, ['approved', 'rejected'])) {
                throw new \Exception("Sale Order is already {$status} and cannot be deleted.");
            }
        });

        static::updated(function ($salesOrder) {
            if ($salesOrder->isDirty('am_approval_status') && $salesOrder->am_approval_status === 'approved') {
                if ($salesOrder->payment_on_kaanta) {
                    self::autoCreateDeliveryOrder($salesOrder);
                }
            }
        });
    }

    public function isClosed(): bool
    {
        return in_array($this->contract_status, [
            'close-contract-due-to-market-down',
            'close-with-market-rate-penalty',
            'closed',
            'close'
        ]) || in_array($this->status, ['cancelled', 'closed']);
    }

    public function hasPendingDeliveryDateAmendment(): bool
    {
        return \Illuminate\Support\Facades\Cache::store('database')->has("so_amendment_{$this->id}");
    }

    public function getPendingDeliveryDateAmendment(): ?array
    {
        return \Illuminate\Support\Facades\Cache::store('database')->get("so_amendment_{$this->id}");
    }

    public function scopeActiveContract($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('contract_status')
              ->orWhereNotIn('contract_status', [
                  'close-contract-due-to-market-down',
                  'close-with-market-rate-penalty',
                  'closed',
                  'close',
              ]);
        })->where(function ($q) {
            $q->whereNull('status')
              ->orWhereNotIn('status', ['cancelled', 'closed']);
        });
    }

    public function scopeClosedContract($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('contract_status', [
                'close-contract-due-to-market-down',
                'close-with-market-rate-penalty',
                'closed',
                'close',
            ])->orWhereIn('status', ['cancelled', 'closed']);
        });
    }

    protected function onApprovalComplete()
    {
        $this->traitOnApprovalComplete();

        // Check if there is an amended delivery date stored in database cache
        $cacheKey = "so_amendment_{$this->id}";
        $amendment = \Illuminate\Support\Facades\Cache::store('database')->get($cacheKey);
        if ($amendment) {
            $oldDate = $amendment['old_delivery_date'] ?? null;
            $newDate = $amendment['delivery_date'] ?? $this->delivery_date;
            
            $this->delivery_date = $newDate;
            $this->saveQuietly();

            // Audit log
            \App\Services\AuditLogService::log(
                $this,
                'delivery_date_amended_approved',
                "Delivery date amended and approved from {$oldDate} to {$newDate}",
                ['delivery_date' => $oldDate],
                ['delivery_date' => $newDate],
                $amendment['requested_by'] ?? null
            );

            // Clear cache
            \Illuminate\Support\Facades\Cache::store('database')->forget($cacheKey);
        }
    }

    public function getApprovalStatus()
    {
        if ($this->am_approval_status === 'approved') {
            return 'approved';
        }
        return $this->traitGetApprovalStatus();
    }

    protected function onApprovalRejected()
    {
        $cacheKey = "so_amendment_{$this->id}";
        $amendment = \Illuminate\Support\Facades\Cache::store('database')->get($cacheKey);
        if ($amendment) {
            // An amendment on an already-approved SO was rejected/declined
            // 1. Restore original delivery date
            if (!empty($amendment['old_delivery_date'])) {
                $this->delivery_date = $amendment['old_delivery_date'];
            }
            // 2. SO itself remains Approved!
            $this->am_approval_status = 'approved';
            $this->am_change_made = 1;
            $this->saveQuietly();

            // 3. Remove the extra pending cycle rows created by reject()
            $module = $this->getApprovalModule();
            if ($module) {
                $maxCycle = $this->approvalRows()->where('module_id', $module->id)->max('approval_cycle');
                $this->approvalRows()
                    ->where('module_id', $module->id)
                    ->where('approval_cycle', $maxCycle)
                    ->where('status', 'pending')
                    ->delete();
            }

            \App\Services\AuditLogService::log(
                $this,
                'delivery_date_amendment_rejected',
                "Delivery date amendment declined. Retained original delivery date {$this->delivery_date} and restored Approved status.",
                ['delivery_date' => $amendment['delivery_date'] ?? null],
                ['delivery_date' => $this->delivery_date],
                auth()->id() ?? 1
            );

            \Illuminate\Support\Facades\Cache::store('database')->forget($cacheKey);
            return;
        }

        $this->traitOnApprovalRejected();
    }

    protected function onApprovalReverted()
    {
        $cacheKey = "so_amendment_{$this->id}";
        $amendment = \Illuminate\Support\Facades\Cache::store('database')->get($cacheKey);
        if ($amendment) {
            // An amendment on an already-approved SO was reverted/declined
            if (!empty($amendment['old_delivery_date'])) {
                $this->delivery_date = $amendment['old_delivery_date'];
            }
            $this->am_approval_status = 'approved';
            $this->am_change_made = 1;
            $this->saveQuietly();

            $module = $this->getApprovalModule();
            if ($module) {
                $maxCycle = $this->approvalRows()->where('module_id', $module->id)->max('approval_cycle');
                $this->approvalRows()
                    ->where('module_id', $module->id)
                    ->where('approval_cycle', $maxCycle)
                    ->where('status', 'pending')
                    ->delete();
            }

            \App\Services\AuditLogService::log(
                $this,
                'delivery_date_amendment_reverted',
                "Delivery date amendment reverted. Retained original delivery date {$this->delivery_date} and restored Approved status.",
                ['delivery_date' => $amendment['delivery_date'] ?? null],
                ['delivery_date' => $this->delivery_date],
                auth()->id() ?? 1
            );

            \Illuminate\Support\Facades\Cache::store('database')->forget($cacheKey);
            return;
        }

        $this->traitOnApprovalReverted();
    }

    public static function autoCreateDeliveryOrder(SalesOrder $salesOrder)
    {
        $existingDo = \App\Models\Sales\DeliveryOrder::where('so_id', $salesOrder->id)
            ->where('is_auto_created_from_so', true)
            ->first();

        $companyLocation = $salesOrder->locations->first();
        
        $factoryIds = $salesOrder->factories()->pluck('arrival_location_id')->filter()->unique()->values();
        $arrivalLocationId = $factoryIds->isNotEmpty() 
            ? $factoryIds->implode(',') 
            : ($salesOrder->arrival_location_id ? (string)$salesOrder->arrival_location_id : null);

        $sectionIds = $salesOrder->sections()->pluck('arrival_sub_location_id')->filter()->unique()->values();
        $subArrivalLocationId = $sectionIds->isNotEmpty() 
            ? $sectionIds->implode(',') 
            : ($salesOrder->arrival_sub_location_id ? (string)$salesOrder->arrival_sub_location_id : null);

        if ($existingDo) {
            $existingDo->update([
                'location_id' => $companyLocation ? $companyLocation->location_id : $existingDo->location_id,
                'arrival_location_id' => $arrivalLocationId,
                'sub_arrival_location_id' => $subArrivalLocationId,
            ]);
            return;
        }

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
            'arrival_location_id' => $arrivalLocationId,
            'sub_arrival_location_id' => $subArrivalLocationId,
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
