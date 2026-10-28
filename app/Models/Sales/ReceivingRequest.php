<?php

namespace App\Models\Sales;

use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Transporter;
use App\Models\User;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReceivingRequest extends Model
{
    use HasFactory;
    use HasApproval {
        onApprovalComplete as traitOnApprovalComplete;
        onApprovalRejected as traitOnApprovalRejected;
    }

    protected $fillable = [
        'delivery_challan_id',
        'dc_no',
        'dc_date',
        'truck_number',
        'bilty',
        'labour',
        'transporter',
        'inhouse_weighbridge',
        'labour_amount',
        'transporter_amount',
        'transporter_deduction',
        'weighbridge_amount',
        'inhouse_weighbridge_amount',
        'company_id',
        'created_by_id',
        'am_approval_status',
        'am_change_made',
        'arrived_date',
        'arrived_weight',
        'exempted_weight',
        'payment_weight',
        'unloading_paid_by',
        'weighbridge_paid_by',
        'transporter_other_amount',
        'demurrage_detention_amount',
        'sales_return_id',
        'sales_return_qty',
        'sales_return_transporter_amount'
    ];

    protected $casts = [
        'dc_date' => 'date',
        'labour_amount' => 'decimal:2',
        'transporter_amount' => 'decimal:2',
        'transporter_deduction' => 'decimal:2',
        'transporter_other_amount' => 'decimal:2',
        'demurrage_detention_amount' => 'decimal:2',
        'sales_return_qty' => 'decimal:2',
        'sales_return_transporter_amount' => 'decimal:2',
        'weighbridge_amount' => 'decimal:2',
        'inhouse_weighbridge_amount' => 'decimal:2',
    ];

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

        static::updating(function ($model) {
            $originalStatus = strtolower($model->getOriginal('am_approval_status') ?? '');
            $newStatus = strtolower($model->am_approval_status ?? '');
            if ($model->isDirty('am_approval_status')) {
                if (in_array($originalStatus, ['approved', 'rejected'])) {
                    throw new \Exception("Receiving Request is already {$originalStatus} and status cannot be changed.");
                }
                if ($originalStatus === 'reverted' && $newStatus !== 'pending') {
                    throw new \Exception("Receiving Request is reverted and cannot be {$newStatus} directly. It must be updated to pending first.");
                }
            }
        });

        static::deleting(function ($model) {
            $status = strtolower($model->am_approval_status ?? '');
            if (in_array($status, ['approved', 'rejected'])) {
                throw new \Exception("Receiving Request is already {$status} and cannot be deleted.");
            }
        });
    }

    /**
     * Model display name for audit logging.
     */
    public function auditModelName(): string
    {
        return 'Receiving Request';
    }

    /**
     * Unique identifier for audit logging.
     */
    public function auditIdentifier(): string
    {
        return (string) ($this->dc_no ?? "#{$this->getKey()}");
    }

    /**
     * Action prefix for audit logs.
     */
    public function auditActionPrefix(): string
    {
        return 'receiving_request';
    }

    /**
     * Capture initial snapshot of items and weighbridges before any updates.
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
        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('product')->get();

        $unloadingRates = [];
        foreach ($items as $item) {
            $unloadingRates[] = (float) $item->unloading_labour_rate;
        }

        $wbs = $this->relationLoaded('weighbridges')
            ? $this->weighbridges
            : $this->weighbridges()->get();

        $wbList = [];
        foreach ($wbs as $wb) {
            $wbList[] = $wb->name . ' (Rs. ' . number_format((float)$wb->amount, 2) . ')';
        }

        return [
            'items_unloading_rate' => !empty($unloadingRates) ? implode(', ', $unloadingRates) : null,
            'weighbridges'         => !empty($wbList) ? implode(', ', $wbList) : null,
        ];
    }

    /**
     * Build references snapshot from incoming request payload.
     */
    public function buildNewReferencesSnapshot(): array
    {
        $req = request();
        if (!$req) {
            return $this->buildCurrentReferencesSnapshot();
        }

        $unloadingRates = [];
        if ($req->has('items') && is_array($req->items)) {
            foreach ($req->items as $itemId => $itemData) {
                if (isset($itemData['unloading_labour_rate'])) {
                    $unloadingRates[] = (float) $itemData['unloading_labour_rate'];
                }
            }
        }

        $wbList = [];
        if ($req->has('weighbridges') && is_array($req->weighbridges)) {
            foreach ($req->weighbridges as $wb) {
                if (!empty($wb['name'])) {
                    $amt = floatval($wb['amount'] ?? 0);
                    $wbList[] = $wb['name'] . ' (Rs. ' . number_format($amt, 2) . ')';
                }
            }
        }

        $current = $this->buildCurrentReferencesSnapshot();

        return [
            'items_unloading_rate' => !empty($unloadingRates) ? implode(', ', $unloadingRates) : ($current['items_unloading_rate'] ?? null),
            'weighbridges'         => !empty($wbList) ? implode(', ', $wbList) : ($req->has('weighbridges') ? null : ($current['weighbridges'] ?? null)),
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


    public function deliveryChallan()
    {
        return $this->belongsTo(DeliveryChallan::class, 'delivery_challan_id');
    }

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id');
    }

    public function items()
    {
        return $this->hasMany(ReceivingRequestItem::class, 'receiving_request_id');
    }

    public function weighbridges()
    {
        return $this->hasMany(ReceivingRequestWeighbridge::class, 'receiving_request_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function getPenaltyWeightAttribute(): float
    {
        $dispatchWeight = floatval($this->items->sum('dispatch_weight'));
        $arrivedWeight = floatval($this->arrived_weight ?? 0);
        $shortWeight = max(0, $dispatchWeight - $arrivedWeight);
        $exemptedWeight = floatval($this->exempted_weight ?? 0);
        return max(0, $shortWeight - $exemptedWeight);
    }

    protected function onApprovalComplete()
    {
        $this->traitOnApprovalComplete();
        app(\App\Services\SalesLedgerService::class)->handleReceivingRequestApproval($this);
    }

    protected function onApprovalRejected()
    {
        $this->traitOnApprovalRejected();
    }

    /**
     * Configuration for universal audit logs modal rendering.
     */
    public static function auditLogConfig(): array
    {
        return [
            'title' => function ($model) {
                $dcNo = $model->dc_no ?? $model->id;
                return "Audit Logs: Receiving Request #{$dcNo}";
            },
            'subtitle' => function ($model) {
                $parts = ['DC #' . ($model->dc_no ?? $model->id)];
                if ($model->deliveryChallan?->customer) {
                    $parts[] = 'Customer: ' . ($model->deliveryChallan->customer->name ?? 'N/A');
                }
                if ($model->truck_number) {
                    $parts[] = 'Truck: ' . $model->truck_number;
                }
                if ($model->dc_date) {
                    $parts[] = 'Date: ' . \Carbon\Carbon::parse($model->dc_date)->format('d M Y');
                }
                if ($model->am_approval_status) {
                    $parts[] = 'Status: ' . ucfirst($model->am_approval_status);
                }
                return implode(' | ', $parts);
            },
            'empty_message' => 'No edits or updates have been recorded for this receiving request yet.',
            'field_labels' => [
                'dc_no'                      => 'DC Number',
                'dc_date'                    => 'DC Date',
                'customer'                   => 'Customer',
                'truck_number'               => 'Truck Number',
                'bilty'                      => 'Bilty / LR Number',
                'arrived_date'               => 'Arrived Date',
                'arrived_weight'             => 'Receiving Weight (Kgs)',
                'exempted_weight'            => 'Exempted Weight (Kgs)',
                'payment_weight'             => 'Payment Weight (Kgs)',
                'labour'                     => 'Labour Contractor',
                'labour_amount'              => 'Labour Amount',
                'unloading_paid_by'          => 'Unloading Paid By',
                'transporter'                => 'Transporter',
                'transporter_amount'         => 'Transporter Amount',
                'transporter_deduction'      => 'Transporter Deduction',
                'demurrage_detention_amount' => 'Demurrage / Detention Amount',
                'weighbridge_amount'         => 'Weighbridge Amount',
                'weighbridge_paid_by'        => 'Weighbridge Paid By',
                'weighbridges'               => 'Weighbridges',
                'am_approval_status'         => 'Approval Status',
                'items_product'              => 'Products',
                'items_dispatch_weight'      => 'Dispatch Weights (Kgs)',
                'items_receiving_weight'     => 'Receiving Weights (Kgs)',
                'items_difference_weight'    => 'Difference Weights (Kgs)',
                'items_unloading_rate'       => 'Unloading Labour Rates',
                'items_bags'                 => 'No of Bags',
                'total_dispatch_weight'      => 'Total Dispatch Weight (Kgs)',
                'total_receiving_weight'     => 'Total Receiving Weight (Kgs)',
                'total_bags'                 => 'Total Bags',
                'items_count'                => 'Items Count',
            ],
            'skip_keys' => [
                'company_id',
                'created_by_id',
                'am_change_made',
                'deleted_at',
            ],
            'relation_resolvers' => [
                'transporter' => function ($val) {
                    if (is_numeric($val)) {
                        $t = \App\Models\Master\Transporter::find($val);
                        return $t ? ($t->name ?: $t->company_name) : "Transporter #{$val}";
                    }
                    return $val;
                },
                'labour' => function ($val) {
                    if (is_numeric($val)) {
                        $v = \App\Models\Master\Vendor::find($val);
                        return $v ? $v->name : "Vendor #{$val}";
                    }
                    return $val;
                },
                'labour_amount'              => fn($val) => is_numeric($val) ? 'Rs. ' . number_format((float) $val, 2) : $val,
                'transporter_amount'         => fn($val) => is_numeric($val) ? 'Rs. ' . number_format((float) $val, 2) : $val,
                'transporter_deduction'      => fn($val) => is_numeric($val) ? 'Rs. ' . number_format((float) $val, 2) : $val,
                'demurrage_detention_amount' => fn($val) => is_numeric($val) ? 'Rs. ' . number_format((float) $val, 2) : $val,
                'weighbridge_amount'         => fn($val) => is_numeric($val) ? 'Rs. ' . number_format((float) $val, 2) : $val,
                'arrived_weight'             => fn($val) => is_numeric($val) ? number_format((float) $val, 2) . ' Kgs' : $val,
                'exempted_weight'            => fn($val) => is_numeric($val) ? number_format((float) $val, 2) . ' Kgs' : $val,
                'payment_weight'             => fn($val) => is_numeric($val) ? number_format((float) $val, 2) . ' Kgs' : $val,
                'total_dispatch_weight'      => fn($val) => is_numeric($val) ? number_format((float) $val, 2) . ' Kgs' : $val,
                'total_receiving_weight'     => fn($val) => is_numeric($val) ? number_format((float) $val, 2) . ' Kgs' : $val,
                'am_approval_status'         => fn($val) => ucfirst((string) $val),
            ],
        ];
    }
}

