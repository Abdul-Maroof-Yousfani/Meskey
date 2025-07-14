<?php

namespace App\Models;

use App\Models\Master\Account\Account;
use App\Models\Master\Supplier;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentVoucher extends Model
{
    use HasFactory, SoftDeletes, HasApproval;

    protected $fillable = [
        'unique_no',
        'pv_date',
        'ref_bill_no',
        'bill_date',
        'cheque_no',
        'cheque_date',
        'account_id',
        'supplier_id',
        'bank_account_type',
        'bank_account_id',
        'module_id',
        'module_type',
        'voucher_type',
        'remarks',
        'total_amount'
    ];

    protected $casts = [
        'pv_date' => 'date',
        'bill_date' => 'date',
        'cheque_date' => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function paymentVoucherData()
    {
        return $this->hasMany(PaymentVoucherData::class, 'payment_voucher_id');
    }

    protected function onApprovalComplete()
    {
        $this->update(['approval_status' => 'approved']);
    }

    protected function onApprovalRejected()
    {
        $this->update(['approval_status' => 'rejected']);
    }
}
