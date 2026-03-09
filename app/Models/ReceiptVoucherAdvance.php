<?php

namespace App\Models;

use App\Models\Master\Customer;
use App\Models\Master\Tax;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptVoucherAdvance extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_voucher_id',
        'customer_id',
        'adv_no',
        'amount',
        'tax_id',
        'tax_amount',
        'net_amount',
        'line_desc',
    ];

    public function receiptVoucher()
    {
        return $this->belongsTo(ReceiptVoucher::class, 'receipt_voucher_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
