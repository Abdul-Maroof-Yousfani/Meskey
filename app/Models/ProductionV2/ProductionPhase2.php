<?php

namespace App\Models\ProductionV2;

use App\Models\Acl\Company;
use App\Models\Master\CompanyLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ProductionPhase2 extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_phase2';

    protected $fillable = [
        'company_id',
        'job_order_id',
        'company_location_id',
        'phase1_id',
        'process_type',
        'steam_type',
        'parboil_soak_hours',
        'parboil_cook_minutes',
        'parboiled_grade',
        'status',
        'remarks',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'parboil_soak_hours' => 'decimal:2',
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

    public function phase1()
    {
        return $this->belongsTo(ProductionPhase1::class, 'phase1_id');
    }

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
