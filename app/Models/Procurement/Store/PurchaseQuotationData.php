<?php

namespace App\Models\Procurement\Store;

use App\Models\Category;
use App\Models\Master\Supplier;
use App\Models\Product;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseQuotationData extends Model
{
    use HasFactory, HasApproval;

    protected $table = "purchase_quotation_data";
    protected $fillable = [
        'purchase_quotation_id',
        'purchase_request_data_id',
        'category_id',
        'item_id',
        'supplier_id',
        'qty',
        'rate',
        'am_approval_status',
        'total',
        'remarks',
        'quotation_status',
        'po_status',
        'status',
        'delivery_date',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'rate' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::updating(
            function ($model) {
                if ($model->getOriginal('am_approval_status') === 'approved') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'am_approval_status' => ['This item has already been approved and cannot be modified.'],
                    ]);
                }

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

    public function purchase_quotation()
    {
        return $this->belongsTo(PurchaseQuotation::class, 'purchase_quotation_id', 'id');
    }

    public function purchase_order_data() {
        return $this->hasMany(PurchaseOrderData::class, "purchase_quotation_data_id");
    }
    
    public function purchase_request() {
        return $this->belongsTo(PurchaseRequestData::class, "purchase_request_data_id", 'id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function item()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function approval()
    {
        return $this->hasMany(PurchaseItemApprove::class, 'purchase_request_data_id');
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
