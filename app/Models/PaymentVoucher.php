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
        'am_approval_status',
        'bank_account_type',
        'bank_account_id',
        'module_id',
        'module_type',
        'voucher_type',
        'remarks',
        'total_amount',
        "is_direct"
    ];

    protected $casts = [
        'pv_date' => 'date',
        'bill_date' => 'date',
        'cheque_date' => 'date',
    ];

    protected static function booted()
    {
        static::updating(
            function ($model) {
                $changes = $model->getDirty();
                $changedColumns = [];

                foreach ($changes as $key => $newValue) {
                    if ($key !== "am_change_made") {
                        $oldValue = $model->getOriginal($key);
                        $changedColumns[$key] = [
                            'old' => $oldValue,
                            'new' => $newValue,
                        ];
                    }
                }

                if (!empty($changedColumns)) {
                    if ($model->getAttribute('am_change_made') !== null) {
                        $model->am_change_made = 1;
                    }
                }
            }
        );
    }

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
}
