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
        'current_phase',
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
        'attention_to' => 'array',
        'active_phases' => 'array',
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

    public function productionPhase2()
    {
        return $this->phase2();
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
