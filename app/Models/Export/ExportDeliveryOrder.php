<?php

namespace App\Models\Export;

use App\Models\Sales\DeliveryOrder;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExportDeliveryOrder extends DeliveryOrder
{
    use HasFactory, HasApproval;
    protected $table = "delivery_order";
    protected $guarded = ["id", "created_at", "updated_at"];

    /**
     * Scope for export type records
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'export_order';
        });

        static::addGlobalScope('export_type', function ($builder) {
            $builder->where('type', 'export_order');
        });
    }

    public function buyer()
    {
        return $this->belongsTo(\App\Models\Master\Customer::class, 'buyer_id');
    }

    public function exportFormE()
    {
        return $this->belongsTo(\App\Models\Export\ExportFormE::class, 'export_form_e_id');
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
}
