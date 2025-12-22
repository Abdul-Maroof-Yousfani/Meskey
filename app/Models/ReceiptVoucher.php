<?php

namespace App\Models;

use App\Models\Master\Account\Account;
use App\Models\Master\Customer;
use App\Models\Sales\DeliveryOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceiptVoucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unique_no',
        'rv_date',
        'ref_bill_no',
        'bill_date',
        'cheque_no',
        'cheque_date',
        'account_id',
        'customer_id',
        'bank_account_id',
        'bank_account_type',
        'module_id',
        'module_type',
        'voucher_type',
        'remarks',
        'total_amount',
        'company_id'
    ];

    protected $casts = [
        'rv_date' => 'date',
        'bill_date' => 'date',
        'cheque_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(ReceiptVoucherItem::class, 'receipt_voucher_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function delivery_orders() {
        return $this->belongsToMany(DeliveryOrder::class, "delivery_order_receipt_voucher", "receipt_voucher_id", "delivery_order_id")->withPivot("amount");
    }
}
