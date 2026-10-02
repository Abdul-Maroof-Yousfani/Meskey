<?php

namespace App\Models\ProductionV2;

use App\Models\Acl\Company;
use App\Models\Export\ExportOrder;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ProductionPhase;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class JobOrderV2 extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_job_orders';

    protected $fillable = [
        'company_id',
        'company_location_id',
        'product_id',
        'export_order_id',
        'job_order_no',
        'job_order_date',
        'ref_no',
        'attention_to',
        'order_description',
        'remarks',
        'active_phases',
        'current_stage',
        'current_phase',
        'crop_year_id',
        'other_specifications',
        'inspection_company_id',
        'arrival_locations',
        'loading_date',
        'packing_description',
        'status',
        'approval_status',
        'maker_id',
        'poster_id',
        'agreeor_id',
        'agreed_at',
        'rejection_reason',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'job_order_date' => 'date',
        'loading_date' => 'date',
        'attention_to' => 'array',
        'inspection_company_id' => 'array',
        'arrival_locations' => 'array',
        'active_phases' => 'array',
        'current_stage' => 'integer',
        'agreed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $userId = Auth::user()->id;
                $model->created_by = $userId;
                $model->updated_by = $userId;
                $model->maker_id = $model->maker_id ?: $userId;
                if (empty($model->company_id) && Auth::user()->company_id) {
                    $model->company_id = Auth::user()->company_id;
                }
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::user()->id;
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::user()->id;
                $model->save();
            }
        });
    }

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function location()
    {
        return $this->companyLocation();
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function exportOrder()
    {
        return $this->belongsTo(ExportOrder::class, 'export_order_id');
    }

    public function maker()
    {
        return $this->belongsTo(User::class, 'maker_id');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'poster_id');
    }

    public function agreeor()
    {
        return $this->belongsTo(User::class, 'agreeor_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attentionUsers()
    {
        $ids = $this->attention_to ?? [];
        return User::whereIn('id', (array)$ids)->get();
    }

    public function phase1()
    {
        return $this->hasMany(ProductionPhase1::class, 'job_order_id');
    }

    public function phase2()
    {
        return $this->hasMany(ProductionPhase2::class, 'job_order_id');
    }

    public function phase3()
    {
        return $this->hasMany(ProductionPhase3::class, 'job_order_id');
    }

    // Model name aliases
    public function productionPhase1()
    {
        return $this->phase1();
    }

    public function phaseParameters()
    {
        return $this->hasMany(ProductionJobOrderPhaseParameter::class, 'job_order_id');
    }

    public function phase1Parameters()
    {
        return $this->hasMany(ProductionJobOrderPhaseParameter::class, 'job_order_id')->where('phase_id', 1);
    }

    public function phase2Parameters()
    {
        return $this->hasMany(ProductionJobOrderPhaseParameter::class, 'job_order_id')->where('phase_id', 2);
    }

    public function productionPhase2()
    {
        return $this->phase2();
    }

    public function currentStagePhase()
    {
        return $this->belongsTo(ProductionPhase::class, 'current_stage');
    }

    public function productionPhase3()
    {
        return $this->phase3();
    }

    // Legacy Aliases
    public function phase1Dryings()
    {
        return $this->phase1();
    }

    public function phase2Cookings()
    {
        return $this->phase2();
    }

    public function phase3Millings()
    {
        return $this->phase3();
    }

    // Phase 3 (Old Production Flow) Relationships
    public function cropYear()
    {
        return $this->belongsTo(\App\Models\Master\CropYear::class, 'crop_year_id');
    }

    public function inspectionCompanies()
    {
        return \App\Models\Master\InspectionCompany::whereIn('id', (array)($this->inspection_company_id ?? []))->get();
    }

    public function arrivalLocationRecords()
    {
        return \App\Models\Master\ArrivalLocation::whereIn('id', (array)($this->arrival_locations ?? []))->get();
    }

    public function packingItems()
    {
        return $this->hasMany(ProductionJobOrderPackingItem::class, 'job_order_id');
    }

    public function specifications()
    {
        return $this->hasMany(ProductionJobOrderSpecification::class, 'job_order_id');
    }

    public function containerProtectionItems()
    {
        return $this->belongsToMany(Product::class, 'production_job_order_container_protection_items', 'job_order_id', 'product_id')
            ->withPivot('quantity_per_container')
            ->withTimestamps();
    }

    public function getTotalBagsAttribute()
    {
        return $this->packingItems->sum('total_bags');
    }

    public function getTotalKgsAttribute()
    {
        return $this->packingItems->sum('total_kgs');
    }

    public function getTotalMetricTonsAttribute()
    {
        return $this->packingItems->sum('metric_tons');
    }

    public function getTotalContainersAttribute()
    {
        return $this->packingItems->sum('no_of_containers');
    }

    /**
     * Get active production phases based on location or cached snapshot
     */
    public function getActivePhaseModels()
    {
        $phaseIds = $this->active_phases;
        if (empty($phaseIds) && $this->companyLocation) {
            $phaseIds = $this->companyLocation->production_phases;
        }

        if (empty($phaseIds)) {
            return collect();
        }

        return ProductionPhase::whereIn('id', (array)$phaseIds)->where('status', 'active')->get();
    }

    /**
     * Check if a specific phase is enabled for this job order
     */
    public function hasPhase($phaseIdOrKey): bool
    {
        $models = $this->getActivePhaseModels();
        if (is_numeric($phaseIdOrKey)) {
            return $models->pluck('id')->contains((int)$phaseIdOrKey);
        }
        return $models->pluck('key')->contains((string)$phaseIdOrKey);
    }
}
