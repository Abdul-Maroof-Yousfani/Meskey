<?php

namespace App\Models\Production\JobOrderRequest;

use App\Models\Master\CropYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Master\{InspectionCompany, FumigationCompany, ArrivalLocation, CompanyLocation};
use App\Models\Product;
use App\Models\Acl\Company;
use App\Models\User;
use App\Models\Production\JobOrder\JobOrder;

class JobOrderRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'job_order_requests';

    protected $fillable = [
        'request_no',
        'request_date',
        'company_id',
        'company_location_id',
        'export_order_id',
        'ref_no',
        'attention_to',
        'product_id',
        'crop_year_id',
        'remarks',
        'order_description',
        'other_specifications',
        'inspection_company_id',
        'loading_date',
        'arrival_locations',
        'fumigation_type',
        'packing_description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'inspection_company_id' => 'array',
        'arrival_locations' => 'array',
        'attention_to' => 'array',
        'request_date' => 'date',
        'loading_date' => 'date',
    ];

    public function getCompanyLocationIdAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }
        if ($this->relationLoaded('packingItems')) {
            return $this->packingItems->first()?->company_location_id ?? null;
        }
        return null;
    }

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function cropYear()
    {
        return $this->belongsTo(CropYear::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function exportOrder()
    {
        return $this->belongsTo(\App\Models\Export\ExportOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attentionUsers()
    {
        return User::whereIn('id', $this->attention_to ?? []);
    }

    public function inspectionCompanies()
    {
        return InspectionCompany::whereIn('id', $this->inspection_company_id ?? []);
    }

    public function arrivalLocationRecords()
    {
        return ArrivalLocation::whereIn('id', $this->arrival_locations ?? []);
    }

    public function packingItems()
    {
        return $this->hasMany(JobOrderRequestPackingItem::class);
    }

    public function specifications()
    {
        return $this->hasMany(JobOrderRequestSpecification::class);
    }

    public function containerProtectionItems()
    {
        return $this->belongsToMany(Product::class, 'job_order_request_container_protection_items', 'job_order_request_id', 'product_id')
            ->withPivot('quantity_per_container')
            ->withTimestamps();
    }

    public function jobOrders()
    {
        return $this->hasMany(JobOrder::class, 'job_order_request_id');
    }

    // Auto calculate totals
    public function getTotalBagsAttribute()
    {
        return $this->packingItems->sum('total_bags');
    }

    public function getTotalKgsAttribute()
    {
        return (float) $this->packingItems->sum(function ($item) {
            return (float) ($item->total_kgs ?: ((float) $item->bag_size * (int) $item->no_of_bags));
        });
    }

    public function getTotalMetricTonsAttribute()
    {
        return round($this->total_kgs / 1000, 3);
    }

    public function getTotalContainersAttribute()
    {
        return $this->packingItems->sum('no_of_containers');
    }

    public function getConsumedKgsAttribute()
    {
        return (float) $this->jobOrders()
            ->with('packingItems')
            ->get()
            ->sum(function ($jo) {
                return $jo->packingItems->sum('total_kgs');
            });
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
    public function getRemainingKgsAttribute()
    {
        return max(0, (float) $this->total_kgs - $this->consumed_kgs);
    }
}
