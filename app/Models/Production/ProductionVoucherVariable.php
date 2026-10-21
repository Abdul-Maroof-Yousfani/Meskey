<?php

namespace App\Models\Production;

use App\Models\Master\Account\Account;
use App\Models\Master\Variable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionVoucherVariable extends Model
{
    use HasFactory;

    protected $table = 'production_voucher_variables';

    protected $fillable = [
        'production_voucher_id',
        'variable_id',
        'rate',
        'amount',
        'account_id'
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'amount' => 'decimal:2',
    ];

    public function productionVoucher()
    {
        return $this->belongsTo(ProductionVoucher::class, 'production_voucher_id');
    }

    public function variable()
    {
        return $this->belongsTo(Variable::class, 'variable_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
