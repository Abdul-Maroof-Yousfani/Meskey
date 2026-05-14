<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\CompanyLocation;
use App\Models\Master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Traits\HasApproval;

class PurchaseQuotation extends Model
{
    use HasFactory, HasApproval;

    protected $table = "purchase_quotations";
    protected $guarded = [];

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

     public function purchase_request()
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }


    public function quotation_data()
    {
        return $this->hasMany(PurchaseQuotationData::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
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
