<?php

namespace App\Models\ProductionV2;

use App\Models\Acl\Company;
use App\Models\Master\CompanyLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ProductionPhase1 extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_phase1';

    protected $fillable = [
        'company_id',
        'job_order_id',
        'company_location_id',
        'drying_mode',
        'temperature',
        'cycle_start_time',
        'cycle_end_time',
        'moisture_level',
        'parameters',
        'status',
        'remarks',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'cycle_start_time' => 'datetime',
        'cycle_end_time' => 'datetime',
        'parameters' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $userId = Auth::user()->id;
                $model->created_by = $userId;
                $model->updated_by = $userId;
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

    public function jobOrder()
    {
        return $this->belongsTo(JobOrderV2::class, 'job_order_id');
    }

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function parameters()
    {
        return $this->hasMany(ProductionJobOrderPhaseParameter::class, 'job_order_id', 'job_order_id')->where('phase_id', 1);
    }
}
