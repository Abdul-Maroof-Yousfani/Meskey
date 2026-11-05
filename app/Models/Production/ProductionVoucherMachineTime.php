<?php

namespace App\Models\Production;

use App\Models\Master\ProductionMachine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionVoucherMachineTime extends Model
{
    use HasFactory;

    protected $table = 'production_voucher_machine_times';

    protected $fillable = [
        'company_id',
        'machine_plan_setting_id',
        'production_voucher_id',
        'production_machine_id',
        'job_order_ids',
        'start_time',
        'end_time',
        'duration_minutes',
        'hours',
        'is_enabled',
        'remarks',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'job_order_ids' => 'array',
        'hours' => 'decimal:2',
    ];

    public function machinePlanSetting()
    {
        return $this->belongsTo(MachinePlanSetting::class);
    }

    /** All job orders this machine row is created against (stored in job_order_ids). */
    public function getJobOrderIdListAttribute(): array
    {
        $ids = (array) ($this->job_order_ids ?: []);
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    /** Job order models for job_order_ids. */
    public function getJobOrdersAttribute()
    {
        return \App\Models\Production\JobOrder\JobOrder::whereIn('id', $this->job_order_id_list)->get();
    }

    public function productionVoucher()
    {
        return $this->belongsTo(ProductionVoucher::class);
    }

    public function productionMachine()
    {
        return $this->belongsTo(ProductionMachine::class);
    }

    public function machine()
    {
        return $this->belongsTo(ProductionMachine::class, 'production_machine_id');
    }

    public function breakdowns()
    {
        return $this->hasMany(MachineTimeBreakdown::class, 'production_voucher_machine_time_id');
    }
}
