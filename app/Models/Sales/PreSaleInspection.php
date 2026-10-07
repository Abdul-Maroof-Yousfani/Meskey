<?php

namespace App\Models\Sales;

use App\Models\Acl\Company;
use App\Models\Master\CompanyLocation;
use App\Models\User;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreSaleInspection extends Model
{
    use HasFactory, HasApproval;

    protected $table = 'pre_sale_inspections';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'date' => 'date',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'vehicle_assigned_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::updating(function ($model) {
            $originalStatus = strtolower($model->getOriginal('am_approval_status') ?? '');
            $newStatus = strtolower($model->am_approval_status ?? '');
            if ($model->isDirty('am_approval_status')) {
                if (in_array($originalStatus, ['approved', 'rejected'])) {
                    throw new \Exception("Pre Sale Dekh is already {$originalStatus} and status cannot be changed.");
                }
                if ($originalStatus === 'reverted' && $newStatus !== 'pending') {
                    throw new \Exception("Pre Sale Dekh is reverted and cannot be {$newStatus} directly. It must be updated to pending first.");
                }
            }
        });

        static::deleting(function ($model) {
            $status = strtolower($model->am_approval_status ?? '');
            if (in_array($status, ['approved', 'rejected'])) {
                throw new \Exception("Pre Sale Dekh is already {$status} and cannot be deleted.");
            }
        });
    }

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function items()
    {
        return $this->hasMany(PreSaleInspectionItem::class, 'pre_sale_inspection_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function vehicleAssignedBy()
    {
        return $this->belongsTo(User::class, 'vehicle_assigned_by');
    }

    public function confirmation()
    {
        return $this->hasOne(DekhConfirmation::class, 'pre_sale_inspection_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function salesInquiries()
    {
        return $this->hasMany(SalesInquiry::class, 'pre_sale_inspection_id');
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'pre_sale_inspection_id');
    }
}
