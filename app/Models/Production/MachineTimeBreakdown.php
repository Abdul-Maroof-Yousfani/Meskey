<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineTimeBreakdown extends Model
{
    use HasFactory;

    protected $table = 'machine_time_breakdowns';

    protected $fillable = [
        'company_id',
        'production_voucher_machine_time_id',
        'from',
        'to',
        'hours',
        'remarks',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
    ];

    public function machineTime()
    {
        return $this->belongsTo(ProductionVoucherMachineTime::class, 'production_voucher_machine_time_id');
    }
}
