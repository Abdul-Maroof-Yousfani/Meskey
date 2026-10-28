<?php

namespace App\Models\Sales;

use App\Models\Master\Customer;
use App\Models\Procurement\Store\Location;
use App\Models\ReceiptVoucher;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    use HasFactory, HasApproval;

    protected $table = "delivery_order";
    protected $guarded = ["id", "created_at", "updated_at"];

    /**
     * Cached old audit snapshot of references.
     */
    protected ?array $auditOldSnapshot = null;

    protected static function booted()
    {
        static::observe(\App\Observers\UniversalAuditObserver::class);

        static::retrieved(function ($model) {
            if (!request() || !request()->isMethodSafe() || app()->runningInConsole()) {
                $model->captureInitialAuditSnapshot();
            }
        });

        static::saving(function ($model) {
            if ($model->exists && method_exists($model, 'hasReferencesChanged') && $model->hasReferencesChanged()) {
                $model->updated_at = now();
            }
        });

        static::creating(function ($model) {
            $model->type = 'sale_order';
        });

        static::updating(function ($model) {
            $model->type = 'sale_order';
            $originalStatus = strtolower($model->getOriginal('am_approval_status') ?? '');
            $newStatus = strtolower($model->am_approval_status ?? '');
            if ($model->isDirty('am_approval_status')) {
                if (in_array($originalStatus, ['approved', 'rejected'])) {
                    throw new \Exception("Delivery Order is already {$originalStatus} and status cannot be changed.");
                }
                if ($originalStatus === 'reverted' && $newStatus !== 'pending') {
                    throw new \Exception("Delivery Order is reverted and cannot be {$newStatus} directly. It must be updated to pending first.");
                }
            }
        });

        static::deleting(function ($model) {
            $status = strtolower($model->am_approval_status ?? '');
            if (in_array($status, ['approved', 'rejected'])) {
                throw new \Exception("Delivery Order is already {$status} and cannot be deleted.");
            }
        });

        static::addGlobalScope('sale_type', function ($builder) {
            $builder->where('delivery_order.type', 'sale_order');
        });
    }

    /**
     * Model display name for audit logging.
     */
    public function auditModelName(): string
    {
        return 'Delivery Order';
    }

    /**
     * Unique identifier for audit logging.
     */
    public function auditIdentifier(): string
    {
        return (string) ($this->reference_no ?? "#{$this->getKey()}");
    }

    /**
     * Action prefix for audit logs.
     */
    public function auditActionPrefix(): string
    {
        return 'delivery_order';
    }

    /**
     * Capture initial snapshot of items and receipt vouchers before any updates.
     */
    public function captureInitialAuditSnapshot(): void
    {
        if ($this->auditOldSnapshot !== null || !$this->exists) {
            return;
        }
        $this->auditOldSnapshot = $this->buildCurrentReferencesSnapshot();
    }

    /**
     * Get the cached old snapshot of references.
     */
    public function getOldReferencesSnapshot(): ?array
    {
        if ($this->auditOldSnapshot === null && $this->exists) {
            $this->auditOldSnapshot = $this->buildCurrentReferencesSnapshot();
        }
        return $this->auditOldSnapshot;
    }

    /**
     * Build references snapshot from current database state.
     */
    public function buildCurrentReferencesSnapshot(): array
    {
        $items = $this->relationLoaded('delivery_order_data')
            ? $this->delivery_order_data
            : $this->delivery_order_data()->with(['item', 'brand'])->get();

        $itemProducts = [];
        $itemDescriptions = [];
        $itemQtys = [];
        $itemRates = [];
        $itemBags = [];
        $itemBagTypes = [];
        $itemBagSizes = [];
        $itemBrands = [];
        $totalAmount = 0;
        $totalQty = 0;

        foreach ($items as $row) {
            $pName = $row->item ? $row->item->name : "Item #{$row->item_id}";
            $q = (float) $row->qty;
            $r = (float) $row->rate;
            $lineAmt = round($q * $r, 2);
            $totalAmount += $lineAmt;
            $totalQty += $q;

            $itemProducts[] = $pName;
            $descText = trim($row->description ?? '');
            $itemDescriptions[] = (!empty($descText) && $descText !== '-') ? $descText : '-';
            $itemQtys[] = number_format($q, 2);
            $itemRates[] = number_format($r, 2);
            $itemBags[] = $row->no_of_bags ?? '-';
            $itemBagTypes[] = $row->bag_type ?? '-';
            $itemBagSizes[] = $row->bag_size ?? '-';
            $itemBrands[] = $row->brand?->name ?? '-';
        }

        $rvs = $this->relationLoaded('receipt_vouchers')
            ? $this->receipt_vouchers
            : $this->receipt_vouchers()->get();

        $rvList = [];
        foreach ($rvs as $rv) {
            $amt = $rv->pivot->amount ?? 0;
            $rvList[] = "{$rv->unique_no} (Rs. " . number_format((float) $amt, 2) . ")";
        }

        return [
            'items_product'     => !empty($itemProducts) ? implode(', ', $itemProducts) : null,
            'items_description' => !empty(array_filter($itemDescriptions, fn($d) => $d !== '-')) ? implode(', ', $itemDescriptions) : null,
            'items_qty'         => !empty($itemQtys) ? implode(', ', $itemQtys) : null,
            'items_rate'        => !empty($itemRates) ? implode(', ', $itemRates) : null,
            'items_bags'        => !empty($itemBags) ? implode(', ', $itemBags) : null,
            'items_bag_type'    => !empty($itemBagTypes) ? implode(', ', $itemBagTypes) : null,
            'items_bag_size'    => !empty($itemBagSizes) ? implode(', ', $itemBagSizes) : null,
            'items_brand'       => !empty($itemBrands) ? implode(', ', $itemBrands) : null,
            'total_qty'         => (float) $totalQty,
            'total_amount'      => (float) $totalAmount,
            'receipt_vouchers'  => !empty($rvList) ? implode(', ', $rvList) : null,
            'items_count'       => count($items),
        ];
    }

    /**
     * Build references snapshot from incoming request payload.
     */
    public function buildNewReferencesSnapshot(): array
    {
        $req = request();
        if (!$req || !$req->has('item_id') || !is_array($req->item_id)) {
            return $this->buildCurrentReferencesSnapshot();
        }

        $itemProducts = [];
        $itemDescriptions = [];
        $itemQtys = [];
        $itemRates = [];
        $itemBags = [];
        $itemBagTypes = [];
        $itemBagSizes = [];
        $itemBrands = [];
        $totalAmount = 0;
        $totalQty = 0;

        foreach ($req->item_id as $key => $itemId) {
            $item = \App\Models\Product::find($itemId);
            $pName = $item ? $item->name : "Item #{$itemId}";
            $q = (float) ($req->qty[$key] ?? 0);
            $r = (float) ($req->rate[$key] ?? 0);
            $lineAmt = round($q * $r, 2);
            $totalAmount += $lineAmt;
            $totalQty += $q;

            $itemProducts[] = $pName;
            $descText = trim($req->desc[$key] ?? '');
            $itemDescriptions[] = (!empty($descText) && $descText !== '-') ? $descText : '-';
            $itemQtys[] = number_format($q, 2);
            $itemRates[] = number_format($r, 2);
            $itemBags[] = $req->no_of_bags[$key] ?? '-';
            $itemBagTypes[] = $req->bag_type[$key] ?? '-';
            $itemBagSizes[] = $req->bag_size[$key] ?? '-';

            $brandId = $req->brand_id[$key] ?? null;
            $brand = $brandId ? \App\Models\Master\Brands::find($brandId) : null;
            $itemBrands[] = $brand?->name ?? '-';
        }

        $rvList = [];
        if ($req->has('receipt_vouchers') && is_array($req->receipt_vouchers)) {
            foreach ($req->receipt_vouchers as $rvId) {
                $rv = \App\Models\ReceiptVoucher::find($rvId);
                if ($rv) {
                    $rvList[] = $rv->unique_no;
                }
            }
        }

        return [
            'items_product'     => !empty($itemProducts) ? implode(', ', $itemProducts) : null,
            'items_description' => !empty(array_filter($itemDescriptions, fn($d) => $d !== '-')) ? implode(', ', $itemDescriptions) : null,
            'items_qty'         => !empty($itemQtys) ? implode(', ', $itemQtys) : null,
            'items_rate'        => !empty($itemRates) ? implode(', ', $itemRates) : null,
            'items_bags'        => !empty($itemBags) ? implode(', ', $itemBags) : null,
            'items_bag_type'    => !empty($itemBagTypes) ? implode(', ', $itemBagTypes) : null,
            'items_bag_size'    => !empty($itemBagSizes) ? implode(', ', $itemBagSizes) : null,
            'items_brand'       => !empty($itemBrands) ? implode(', ', $itemBrands) : null,
            'total_qty'         => (float) $totalQty,
            'total_amount'      => (float) $totalAmount,
            'receipt_vouchers'  => !empty($rvList) ? implode(', ', $rvList) : null,
            'items_count'       => count($req->item_id),
        ];
    }

    /**
     * Check if child references changed between old and new state.
     */
    public function hasReferencesChanged(): bool
    {
        $old = $this->getOldReferencesSnapshot() ?? [];
        $new = $this->buildNewReferencesSnapshot();

        if (empty($old) && empty($new)) {
            return false;
        }

        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
        foreach ($allKeys as $k) {
            $o = $old[$k] ?? null;
            $n = $new[$k] ?? null;
            if ($o != $n) {
                return true;
            }
        }
        return false;
    }

    public function delivery_order_data()
    {
        return $this->hasMany(DeliveryOrderData::class, 'delivery_order_id');
    }

    public function receipt_vouchers()
    {
        return $this->belongsToMany(ReceiptVoucher::class, "delivery_order_receipt_voucher", "delivery_order_id", "receipt_voucher_id")->withPivot("amount", "receipt_voucher_id", "receipt_voucher_advance_id", "withhold_amount");
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function withheld_receipt_voucher()
    {
        return $this->belongsTo(ReceiptVoucher::class, "withhold_for_rv_id");
    }

    public function locations()
    {
        return $this->morphMany(Location::class, 'locationable');
    }

    public function delivery_challans()
    {
        return $this->belongsToMany(DeliveryChallan::class, "delivery_challan_delivery_order", "delivery_order_id", "delivery_challan_id")->withPivot("qty");
    }
    public function firstWeighbridge()
    {
        return $this->hasOne(FirstWeighbridge::class, "delivery_order_id");
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, "so_id");
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalLocation::class, "arrival_location_id");
    }

    public function subArrivalLocation()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalSubLocation::class, "sub_arrival_location_id");
    }

    public function secondWeighbridge()
    {
        return $this->hasOneThrough(
            LoadingProgram::class,    // The original model (replace with correct class name)
            LoadingSlip::class,
            'delivery_order_id',      // First foreign key: loading_slips.delivery_order_id references delivery_orders.id
            'loading_slip_id',        // Second foreign key: ??? Wait — this needs fixing based on your logic
            'id',                     // Local key on DeliveryOrder (delivery_orders.id)
            'id'                      // Local key on the far model? No — this doesn't match
        );
    }

    public function saleSecondWeighbridge()
    {
        return $this->hasMany(SecondWeighbridgeItem::class, "delivery_order_id");
    }

    public function loadingProgram()
    {
        return $this->hasOne(LoadingProgram::class, "delivery_order_id");
    }

    public function loadingPrograms()
    {
        return $this->hasMany(LoadingProgram::class, "delivery_order_id");
    }

    public function loadingSlips()
    {
        return $this->hasMany(LoadingSlip::class, "delivery_order_id");
    }

    public function loadingProgramItems()
    {
        return $this->hasMany(LoadingProgramItem::class, "delivery_order_id");
    }

    public function exportOrder()
    {
        return $this->belongsTo(\App\Models\Export\ExportOrder::class, 'export_order_id');
    }

    public function exportFormE()
    {
        return $this->belongsTo(\App\Models\Export\ExportFormE::class, 'export_form_e_id');
    }

    public function exportPackingItems()
    {
        return $this->hasMany(\App\Models\Export\ExportDeliveryOrderPackingItem::class, 'delivery_order_id');
    }

    /**
     * Override createApprovalRows from HasApproval trait to handle duplicates safely
     */
    public function createApprovalRows()
    {
        $module = $this->getApprovalModule();
        if (!$module) {
            return;
        }

        $currentCycle = $this->getCurrentApprovalCycle();

        foreach ($module->roles as $moduleRole) {
            \App\Models\ApprovalsModule\ApprovalRow::updateOrCreate(
                [
                    'module_id' => $module->id,
                    'record_id' => $this->id,
                    'role_id' => $moduleRole->role_id,
                    'approval_cycle' => $currentCycle,
                ],
                [
                    'required_count' => $moduleRole->approval_count,
                    'current_count' => 0,
                    'status' => 'pending'
                ]
            );
        }
    }

    public function isClosed(): bool
    {
        if ($this->do_status === 'closed') {
            return true;
        }
        if ($this->salesOrder && $this->salesOrder->isClosed()) {
            return true;
        }
        return false;
    }

    public function scopeWhereDoesntHaveClosedSaleOrder($query)
    {
        return $query->whereDoesntHave('salesOrder', function ($q) {
            $q->whereIn('contract_status', [
                'close-contract-due-to-market-down',
                'close-with-market-rate-penalty',
                'closed',
                'close',
            ])->orWhereIn('status', ['cancelled', 'closed']);
        });
    }

    /**
     * Universal configuration for Delivery Order audit logs modal.
     */
    public static function auditLogConfig(): array
    {
        return [
            'title' => function ($model) {
                return 'Audit Logs: Delivery Order #' . ($model->reference_no ?? $model->id);
            },
            'subtitle' => function ($model) {
                $parts = ['DO #' . ($model->reference_no ?? $model->id)];
                if ($model->customer) {
                    $parts[] = 'Customer: ' . ($model->customer->name ?? 'N/A');
                }
                if ($model->salesOrder) {
                    $parts[] = 'SO #' . ($model->salesOrder->reference_no ?? 'N/A');
                }
                if ($model->dispatch_date) {
                    $parts[] = 'Date: ' . \Carbon\Carbon::parse($model->dispatch_date)->format('d M Y');
                }
                if ($model->do_status || $model->am_approval_status) {
                    $parts[] = 'Status: ' . ucfirst($model->do_status ?? $model->am_approval_status);
                }
                return implode(' | ', $parts);
            },
            'empty_message' => 'No edits or updates have been recorded for this delivery order yet.',
            'field_labels' => [
                'reference_no'           => 'DO Reference No',
                'ref_no'                 => 'Ref / Manual No',
                'dispatch_date'          => 'DO Date',
                'customer'               => 'Customer',
                'customer_id'            => 'Customer',
                'sales_order'            => 'Sale Order',
                'so_id'                  => 'Sale Order',
                'location'               => 'Company Location',
                'location_id'            => 'Company Location',
                'factory'                => 'Factory / Arrival Location',
                'arrival_location_id'    => 'Factory / Arrival Location',
                'section'                => 'Section / Storage Location',
                'sub_arrival_location_id'=> 'Section / Storage Location',
                'advance_amount'         => 'Advance Consumed',
                'jv_amount'              => 'JV Amount',
                'withhold_amount'        => 'Withhold Amount',
                'withhold_for_rv'        => 'Withhold For RV',
                'withhold_for_rv_id'     => 'Withhold For RV',
                'so_withhold_percentage' => 'SO Withhold %',
                'so_held_amount'         => 'SO Held Amount',
                'sauda_type'             => 'Sauda Type',
                'payment_term'           => 'Payment Terms',
                'payment_term_id'        => 'Payment Terms',
                'payment_terms'          => 'Payment Terms',
                'am_approval_status'     => 'Approval Status',
                'do_status'              => 'DO Status',
                'remarks'                => 'Remarks',
                'receipt_vouchers'       => 'Linked Receipt Vouchers',
                'items_product'          => 'Products',
                'items_description'      => 'Item Description',
                'items_qty'              => 'Quantities (Kgs)',
                'items_rate'             => 'Rates',
                'items_bags'             => 'No of Bags',
                'items_bag_type'         => 'Bag Types',
                'items_bag_size'         => 'Bag Sizes',
                'items_brand'            => 'Brands',
                'total_qty'              => 'Total Qty (Kgs)',
                'total_amount'           => 'Total Amount',
                'items_count'            => 'Items Count',
            ],
            'relation_resolvers' => [
                'customer_id'            => function ($val) {
                    if (is_numeric($val)) {
                        $c = \App\Models\Master\Customer::find($val);
                        return $c ? $c->name : "Customer #{$val}";
                    }
                    return $val;
                },
                'so_id'                  => function ($val) {
                    if (is_numeric($val)) {
                        $so = \App\Models\Sales\SalesOrder::find($val);
                        return $so ? ($so->reference_no ?? "SO #{$val}") : "SO #{$val}";
                    }
                    return $val;
                },
                'location_id'            => function ($val) {
                    if (is_numeric($val)) {
                        $loc = \App\Models\Master\CompanyLocation::find($val);
                        return $loc ? $loc->name : "Location #{$val}";
                    }
                    return $val;
                },
                'payment_term_id'        => function ($val) {
                    if (is_numeric($val)) {
                        $pt = \App\Models\PaymentTerm::find($val);
                        return $pt ? ($pt->desc ?? $pt->title ?? $pt->name) : "Term #{$val}";
                    }
                    return $val;
                },
                'withhold_for_rv_id'     => function ($val) {
                    if (is_numeric($val)) {
                        $rv = \App\Models\ReceiptVoucher::find($val);
                        return $rv ? $rv->unique_no : "RV #{$val}";
                    }
                    return $val;
                },
                'arrival_location_id'    => function ($val) {
                    if (!empty($val)) {
                        $fIds = explode(',', (string)$val);
                        $names = \App\Models\Master\ArrivalLocation::whereIn('id', $fIds)->pluck('name')->toArray();
                        return !empty($names) ? implode(', ', $names) : $val;
                    }
                    return $val;
                },
                'sub_arrival_location_id'=> function ($val) {
                    if (!empty($val)) {
                        $sIds = explode(',', (string)$val);
                        $names = \App\Models\Master\ArrivalSubLocation::whereIn('id', $sIds)->pluck('name')->toArray();
                        return !empty($names) ? implode(', ', $names) : $val;
                    }
                    return $val;
                },
                'advance_amount'         => fn($val) => is_numeric($val) ? 'Rs. ' . number_format((float) $val, 2) : $val,
                'jv_amount'              => fn($val) => is_numeric($val) ? 'Rs. ' . number_format((float) $val, 2) : $val,
                'withhold_amount'        => fn($val) => is_numeric($val) ? 'Rs. ' . number_format((float) $val, 2) : $val,
                'so_held_amount'         => fn($val) => is_numeric($val) ? 'Rs. ' . number_format((float) $val, 2) : $val,
                'total_amount'           => fn($val) => is_numeric($val) ? 'Rs. ' . number_format((float) $val, 2) : $val,
                'total_qty'              => fn($val) => is_numeric($val) ? number_format((float) $val, 2) . ' Kgs' : $val,
                'so_withhold_percentage' => fn($val) => is_numeric($val) ? $val . '%' : $val,
                'do_status'              => fn($val) => ucfirst((string) $val),
                'am_approval_status'     => fn($val) => ucfirst((string) $val),
            ],
        ];
    }
}
