<?php

namespace App\Models\Production;

use App\Models\Production\JobOrder\JobOrder;
use App\Models\Master\CompanyLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionVoucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'prod_no',
        'prod_date',
        'job_order_id',
        'location_id',
        'produced_qty_kg',
        'supervisor_id',
        'labor_cost_per_kg',
        'overhead_cost_per_kg',
        'status',
        'remarks'
    ];

    protected $casts = [
        'prod_date' => 'date',
        'produced_qty_kg' => 'decimal:2',
        'labor_cost_per_kg' => 'decimal:4',
        'overhead_cost_per_kg' => 'decimal:4',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Acl\Company::class);
    }

    public function inputs()
    {
        return $this->hasMany(ProductionInput::class);
    }

    public function outputs()
    {
        return $this->hasMany(ProductionOutput::class);
    }
}
