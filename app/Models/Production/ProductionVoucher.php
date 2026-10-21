<?php

namespace App\Models\Production;

use App\Models\Production\JobOrder\JobOrder;
use App\Models\Master\CompanyLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasApproval;

class ProductionVoucher extends Model
{
    use HasFactory, SoftDeletes, HasApproval;

    protected $fillable = [
        'company_id',
        'prod_no',
        'prod_date',
        'job_order_id',
        'location_id',
        'sub_location_id',
        'net_total_input',
        'net_total_output',
        'labour_charges_per_kg',
        'total_labour_charges',
        'labour_deduction',
        'labour_deduction_remarks',
        'labour_net_amount',
        'product_id',
        'plant_id',
        'by_product_id',
        'produced_qty_kg',
        'supervisor_id',
        'user_id',
        'labor_cost_per_kg',
        'overhead_cost_per_kg',
        'raw_material_cost',
        'milling_cost',
        'by_product_cost',
        'net_finished_good_cost',
        'finished_good_rate',
        'milling_rate_id',
        'by_product_rate_id',
        'status',
        'remarks',
        'qc_remarks',
        'am_approval_status',
        'am_change_made',
    ];

    protected $casts = [
        'prod_date' => 'date',
        'produced_qty_kg' => 'decimal:2',
        'labor_cost_per_kg' => 'decimal:4',
        'overhead_cost_per_kg' => 'decimal:4',
        'raw_material_cost' => 'decimal:2',
        'milling_cost' => 'decimal:2',
        'by_product_cost' => 'decimal:2',
        'net_finished_good_cost' => 'decimal:2',
        'finished_good_rate' => 'decimal:4',
    ];

    public function getProducedQtyKgAttribute($value)
    {
        if (!empty($value) && (float)$value > 0) {
            return (float)$value;
        }

        if ($this->relationLoaded('outputs') && $this->outputs->isNotEmpty()) {
            return (float)$this->outputs->sum('qty');
        }

        if (!empty($this->net_total_output) && (float)$this->net_total_output > 0) {
            return (float)$this->net_total_output;
        }

        return (float)($this->outputs()->sum('qty') ?: 0);
    }

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function jobOrders()
    {
        return $this->belongsToMany(JobOrder::class, 'production_voucher_job_orders');
    }

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function headProduct()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function plant()
    {
        return $this->belongsTo(\App\Models\Master\Plant::class, 'plant_id');
    }

    public function byProducts()
    {
        return $this->belongsTo(\App\Models\Product::class, 'by_product_id');
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

    public function slots()
    {
        return $this->hasMany(ProductionSlot::class);
    }

    public function productionMachines()
    {
        return $this->belongsToMany(\App\Models\Master\ProductionMachine::class, 'production_voucher_machines', 'production_voucher_id', 'production_machine_id')->withTimestamps();
    }

    public function productionVoucherMachineTimes()
    {
        return $this->hasMany(ProductionVoucherMachineTime::class, 'production_voucher_id');
    }

    public function analyses()
    {
        return $this->hasMany(ProductionAnalysis::class, 'production_voucher_id');
    }

    public function machineAnalyses()
    {
        return $this->hasMany(ProductionMachineAnalysis::class, 'production_voucher_id');
    }

    public function analysisRequests()
    {
        return $this->hasMany(ProductionAnalysisRequest::class, 'production_voucher_id');
    }

    public function millingRate()
    {
        return $this->belongsTo(\App\Models\Master\MillingRate::class, 'milling_rate_id');
    }

    public function byProductRate()
    {
        return $this->belongsTo(\App\Models\Master\ByProductRate::class, 'by_product_rate_id');
    }

    public function voucherVariables()
    {
        return $this->hasMany(\App\Models\Production\ProductionVoucherVariable::class, 'production_voucher_id');
    }

    public function transactions()
    {
        return $this->hasMany(\App\Models\Master\Account\Transaction::class, 'against_reference_no', 'prod_no');
    }

    public function stocks()
    {
        return $this->hasMany(\App\Models\Master\Account\Stock::class, 'production_voucher_id', 'id');
    }
}
