<?php

namespace App\Models\ProductionV2;

use App\Models\Acl\Company;
use App\Models\ProdctionAttribute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ProductionJobOrderPhaseParameter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_job_order_phase_parameters';

    protected $fillable = [
        'company_id',
        'job_order_id',
        'phase_id',
        'production_attribute_id',
        'key',
        'type',
        'value',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $userId = Auth::user()->id;
                $model->created_by = $userId;
                $model->updated_by = $userId;
                if (empty($model->company_id)) {
                    $model->company_id = Auth::user()->current_company_id ?? Auth::user()->company_id ?? null;
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

    public function attribute()
    {
        return $this->belongsTo(ProdctionAttribute::class, 'production_attribute_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
