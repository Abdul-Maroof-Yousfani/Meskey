<?php

namespace App\Models\Procurement\Store;

use App\Models\Category;
use App\Models\Product;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestData extends Model
{
    use HasFactory, HasApproval;

    protected $fillable = [
        'purchase_request_id',
        'category_id',
        'item_id',
        'qty',
        'tolerance',
        'approved_qty',
        'min_weight',
        'color',
        'construction_per_square_inch',
        'size',
        'stitching',
        'printing_sample',
        'remarks',
        'quotation_status',
        'am_approval_status',
        'po_status',
        'status',
        'brand_id',
        'micron',
        'is_single_job_order',
        'net_amount',
        'packing_id',
        'module_type',
        'tolerance_percentage',
        'size_id'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'quotation_status' => 'integer',
        'po_status' => 'integer',
        'status' => 'boolean',
        'printing_sample' => 'array',
    ];

    protected $guarded = [];

    protected static function booted()
    {
        static::updating(
            function ($model) {
                


                $changes = $model->getDirty();
                $changedColumns = [];

                foreach ($changes as $key => $newValue) {
                    if ($key !== "am_change_made") {
                        $oldValue = $model->getOriginal($key);
                        $changedColumns[$key] = [
                            'old' => $oldValue,
                            'new' => $newValue,
                        ];
                    }
                }

                if (!empty($changedColumns)) {
                    if ($model->getAttribute('am_change_made') !== null) {
                        $model->am_change_made = 1;
                    }
                }
            }
        );
    }

    public function JobOrder()
    {
        return $this->hasMany(PurchaseAgainstJobOrder::class);
    }

    public function purchase_request()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function item()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function purchase_quotation_data()
    {
        return $this->hasMany(PurchaseQuotationData::class, 'purchase_request_data_id');
    }

    public function purchase_order_data()
    {
        return $this->hasMany(PurchaseOrderData::class, 'purchase_request_data_id');
    }

    public function approved_purchase_quotation()
    {
        return $this->hasOne(PurchaseQuotationData::class, 'purchase_request_data_id')
            ->where('am_approval_status', 'approved');
    }

    public function approval()
    {
        return $this->hasMany(PurchaseItemApprove::class, 'purchase_request_data_id');
    }

    public function size_model()
    {
        return $this->belongsTo(\App\Models\Master\Size::class, 'size_id');
    }

    protected function onApprovalComplete()
    {
        $module = $this->getApprovalModule();
        if (isset($module->approval_column)) {
            $this->update([$module->approval_column => 'approved']);
        }

        $parent = $this->purchase_request;
        if ($parent) {
            $parent->syncStatusFromItems('System: Item approved');
        }
    }

    protected function onApprovalRejected()
    {
        $module = $this->getApprovalModule();
        if (isset($module->approval_column)) {
            $this->update([$module->approval_column => 'rejected']);
        }

        $parent = $this->purchase_request;
        if ($parent) {
            $parent->syncStatusFromItems('System: Item rejected');
        }
    }

    protected function onApprovalReverted()
    {
        $module = $this->getApprovalModule();
        if (isset($module->approval_column)) {
            $this->update([$module->approval_column => 'reverted']);
        }

        $parent = $this->purchase_request;
        if ($parent) {
            $parent->syncStatusFromItems('System: Item reverted');
        }
    }

    public function canApprove()
    {
        $status = $this->getApprovalStatus();
        if ($status === 'approved' || $status === 'rejected') {
            return false;
        }

        $user = auth()->user();
        if (!$user) return false;

        $module = $this->getApprovalModule();
        if (!$module) return false;

        if (isset($this->am_change_made) && $this->am_change_made == 0) return false;

        $userRoleIds = $user->roles->pluck('id')->toArray();
        $requiredRoles = $module->roles->pluck('role_id')->toArray();

        if (empty(array_intersect($userRoleIds, $requiredRoles))) return false;

        $currentCycle = $this->getCurrentApprovalCycle();

        $userAlreadyApproved = $this->approvalLogs()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->where('user_id', $user->id)
            ->where('action', 'approved')
            ->where('status', 'active')
            ->exists();

        if ($userAlreadyApproved) return false;

        if ($module->requires_sequential_approval) {
            $approvalRows = $this->approvalRows()
                ->where('module_id', $module->id)
                ->where('approval_cycle', $currentCycle)
                ->orderBy('id')
                ->get();

            foreach ($approvalRows as $row) {
                if ($row->current_count < $row->required_count) {
                    return in_array($row->role_id, $userRoleIds);
                }
            }
        }

        $userApprovalRows = $this->approvalRows()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->whereIn('role_id', $userRoleIds)
            ->where('status', 'pending')
            ->get();

        foreach ($userApprovalRows as $row) {
            if ($row->current_count < $row->required_count) {
                return true;
            }
        }

        return false;
    }
}
