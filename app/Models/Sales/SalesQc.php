<?php

namespace App\Models\Sales;

use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesQc extends Model
{
    use HasFactory;
    use HasApproval {
        approve as traitApprove;
        reject as traitReject;
        createNewApprovalCycle as traitCreateNewApprovalCycle;
    }

    protected $table = 'sales_qc';

    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    public $swap_approval = false;

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'sales_qc';
        });

        static::updating(function ($model) {
            $model->type = 'sales_qc';

            if ($model->swap_approval === 'approve_clicked') {
                $model->am_approval_status = 'rejected';
                $model->status = 'reject';
            } elseif ($model->swap_approval === 'reject_clicked') {
                $model->am_approval_status = 'approved';
                $model->status = 'accept';
            }
        });

        static::updated(function ($model) {
            if ($model->wasChanged('status') || $model->wasChanged('am_approval_status')) {
                if ($model->loadingProgramItem) {
                    if ($model->status === 'accept') {
                        $model->loadingProgramItem->update(['process_status' => 'Sales QC Rejection Rejected (Accepted)']);
                    } else if ($model->status === 'reject' && $model->am_approval_status === 'rejected') {
                        $model->loadingProgramItem->update(['process_status' => 'Sales QC Rejection Approved']);
                    }
                }
            }
        });

        static::addGlobalScope('sales_type', function ($builder) {
            $builder->where('type', 'sales_qc');
        });
    }

    public function loadingProgramItem()
    {
        return $this->belongsTo(LoadingProgramItem::class);
    }


    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->hasMany(SalesQcAttachment::class);
    }

    public function approve($comments = null)
    {
        if ($this->status === 'reject') {
            $this->swap_approval = 'approve_clicked';
            return $this->traitReject($comments);
        }
        return $this->traitApprove($comments);
    }

    public function reject($comments = null)
    {
        if ($this->status === 'reject') {
            $this->swap_approval = 'reject_clicked';
            
            // ApprovalController incorrectly sets am_change_made = 0 right before calling this.
            // Restore it so traitApprove() passes canApprove() checks.
            if (isset($this->am_change_made)) {
                $this->am_change_made = 1;
                $this->save();
            }
            
            return $this->traitApprove($comments);
        }
        return $this->traitReject($comments);
    }

    protected function createNewApprovalCycle()
    {
        if ($this->swap_approval === 'approve_clicked') {
            return;
        }
        $this->traitCreateNewApprovalCycle();
    }
}
