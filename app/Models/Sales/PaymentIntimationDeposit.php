<?php

namespace App\Models\Sales;

use App\Models\Export\Bank;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentIntimationDeposit extends Model
{
    use HasFactory;

    protected $table = 'payment_intimation_deposits';

    protected $fillable = [
        'payment_intimation_id',
        'bank_id',
        'payment_deposit',
    ];

    public function payment_intimation()
    {
        return $this->belongsTo(PaymentIntimation::class, 'payment_intimation_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }
}
