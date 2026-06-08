<?php
// app/Models/Production/MachinePlanSetting.php

namespace App\Models\Production;

use App\Models\Master\Plant;
use App\Models\Master\ProductionMachine;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MachinePlanSetting extends Model
{
    use SoftDeletes;

    protected $table = 'machine_plan_settings';

    protected $fillable = [
        'company_id',
        'date',
        'plant_id',
        'production_voucher_id',
        'user_id',
        'remarks'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function productionVoucher()
    {
        return $this->belongsTo(ProductionVoucher::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(MachinePlanSettingItem::class);
    }

    public function machines()
    {
        return $this->belongsToMany(ProductionMachine::class, 'machine_plan_setting_items')
            ->withPivot('hours', 'is_enabled', 'remarks')
            ->withTimestamps();
    }
}