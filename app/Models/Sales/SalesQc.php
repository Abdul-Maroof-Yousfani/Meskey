<?php

namespace App\Models\Sales;

use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesQc extends Model
{
    use HasFactory;
    use HasApproval {
        onApprovalComplete as traitOnApprovalComplete;
        onApprovalRejected as traitOnApprovalRejected;
    }

    protected $table = 'sales_qc';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'sales_qc';
        });

        static::updating(function ($model) {
            $model->type = 'sales_qc';
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

    protected function onApprovalComplete()
    {
        $this->traitOnApprovalComplete();

        if ($this->loadingProgramItem) {
            $status = ($this->status == 'reject') ? 'Sales QC Rejection Approved' : 'Sales QC Accepted';
            $this->loadingProgramItem->update(['process_status' => $status]);
        }
    }

    protected function onApprovalRejected()
    {
        $this->traitOnApprovalRejected();

        if ($this->loadingProgramItem) {
            $status = ($this->status == 'reject') ? 'Sales QC Rejection Rejected' : 'Sales QC Rejected';
            $this->loadingProgramItem->update(['process_status' => $status]);
        }
    }
}
