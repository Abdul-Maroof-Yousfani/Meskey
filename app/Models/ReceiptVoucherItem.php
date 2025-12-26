<?php

namespace App\Models;

use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptVoucherItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_voucher_id',
        'reference_type',
        'reference_id',
        'amount',
        'tax_id',
        'tax_amount',
        'net_amount',
        'line_desc',
        "account_id"
    ];

    public function receiptVoucher()
    {
        return $this->belongsTo(ReceiptVoucher::class, 'receipt_voucher_id');
    }

    public function saleOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'reference_id');
    }

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'reference_id');
    }
}


