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
        'production_voucher_id',
        'production_machine_id',
        'start_time',
        'end_time',
        'duration_minutes',
    ];

    public function productionVoucher()
    {
        return $this->belongsTo(ProductionVoucher::class);
    }

    public function productionMachine()
    {
        return $this->belongsTo(ProductionMachine::class);
    }
}
