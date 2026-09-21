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
        'start_time',
        'end_time',
        'duration_minutes',
        'hours',
        'is_enabled',
        'remarks',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'hours' => 'decimal:2',
    ];

    public function machinePlanSetting()
    {
        return $this->belongsTo(MachinePlanSetting::class);
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
