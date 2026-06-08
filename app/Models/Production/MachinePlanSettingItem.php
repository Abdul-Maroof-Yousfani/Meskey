<?php

namespace App\Models\Production;
use App\Models\Master\ProductionMachine;
use Illuminate\Database\Eloquent\Model;

class MachinePlanSettingItem extends Model
{
    protected $table = 'machine_plan_setting_items';

    protected $fillable = [
        'company_id',
        'machine_plan_setting_id',
        'production_machine_id',
        'hours',
        'is_enabled',
        'remarks'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'hours' => 'decimal:2'
    ];

    public function machinePlanSetting()
    {
        return $this->belongsTo(MachinePlanSetting::class);
    }

    public function productionMachine()
    {
        return $this->belongsTo(ProductionMachine::class);
    }

    public function machine()
    {
        return $this->belongsTo(ProductionMachine::class, 'production_machine_id');
    }
}