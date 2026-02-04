<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillPaymentVoucherData extends Model
{
    use HasFactory;
    protected $table = 'bill_payment_voucher_data';
    protected $fillable = [
        "payment_voucher_id",
        "purchase_bill_id",
        "amount"
    ];

}
