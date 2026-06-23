<?php

namespace App\Models\Sales;

use App\Models\Export\Bank;
use App\Models\Master\Customer;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentIntimation extends Model
{
    use HasFactory, HasApproval;

    protected $fillable = [
        'customer_id',
        'sale_order_id',
        'bank_id',
        'payment_deposit',
        'company_id',
        'created_by',
        'attachment',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function sale_order()
    {
        return $this->belongsTo(SalesOrder::class, 'sale_order_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }
}
