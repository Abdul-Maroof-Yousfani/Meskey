<?php

namespace App\Models;

use App\Models\Procurement\PaymentRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentVoucherData extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_voucher_id',
        'payment_request_id',
        'amount',
        'description',
        'account_id',
        'tax_id',
        'tax_amount',
        'net_amount',
        'reference_type',
        'reference_id',
        'customer_advance_id',
        'sales_order_id',
        'delivery_order_id',
        'journal_voucher_id',
        'journal_voucher_detail_id',
    ];

    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class, 'payment_voucher_id');
    }

    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class, 'payment_request_id');
    }

    public function account()
    {
        return $this->belongsTo(\App\Models\Master\Account\Account::class, 'account_id');
    }

    public function customerAdvance()
    {
        return $this->belongsTo(\App\Models\CustomerAdvance::class, 'customer_advance_id');
    }

    public function salesOrder()
    {
        return $this->belongsTo(\App\Models\Sales\SalesOrder::class, 'sales_order_id');
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(\App\Models\Sales\DeliveryOrder::class, 'delivery_order_id');
    }

    public function journalVoucher()
    {
        return $this->belongsTo(\App\Models\JournalVoucher::class, 'journal_voucher_id');
    }

    public function journalVoucherDetail()
    {
        return $this->belongsTo(\App\Models\JournalVoucherDetail::class, 'journal_voucher_detail_id');
    }
}
