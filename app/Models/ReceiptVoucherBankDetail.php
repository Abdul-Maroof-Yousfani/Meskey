<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptVoucherBankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_voucher_id',
        'account_id',
        'amount',
        'cheque_no'
    ];

    public function receiptVoucher()
    {
        return $this->belongsTo(ReceiptVoucher::class);
    }

    public function account()
    {
        return $this->belongsTo(\App\Models\Master\Account\Account::class);
    }
}
