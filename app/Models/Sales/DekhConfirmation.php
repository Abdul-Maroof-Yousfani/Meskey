<?php

namespace App\Models\Sales;

use App\Models\User;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DekhConfirmation extends Model
{
    use HasFactory, HasApproval;

    protected $table = 'dekh_confirmations';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'vehicle_assigned_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function ($model) {
            $inspection = $model->inspection;
            if ($inspection) {
                $inspection->update([
                    'is_completed' => $model->is_completed ? 1 : 0,
                    'completed_at' => $model->completed_at,
                    'completed_by' => $model->completed_by,
                    'confirmation_approval_status' => $model->am_approval_status,
                    'confirmation_am_change_made' => $model->am_change_made ?? 1,
                    'vehicle_no' => $model->vehicle_no,
                    'vehicle_assigned_at' => $model->vehicle_assigned_at,
                    'vehicle_assigned_by' => $model->vehicle_assigned_by,
                ]);
            }
        });

        static::updated(function ($model) {
            $inspection = $model->inspection;
            if ($inspection) {
                $inspection->update([
                    'is_completed' => $model->is_completed ? 1 : 0,
                    'completed_at' => $model->completed_at,
                    'completed_by' => $model->completed_by,
                    'confirmation_approval_status' => $model->am_approval_status,
                    'confirmation_am_change_made' => $model->am_change_made ?? 1,
                    'vehicle_no' => $model->vehicle_no,
                    'vehicle_assigned_at' => $model->vehicle_assigned_at,
                    'vehicle_assigned_by' => $model->vehicle_assigned_by,
                ]);
            }
        });
    }

    public function inspection()
    {
        return $this->belongsTo(PreSaleInspection::class, 'pre_sale_inspection_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function vehicleAssignedBy()
    {
        return $this->belongsTo(User::class, 'vehicle_assigned_by');
    }
}
