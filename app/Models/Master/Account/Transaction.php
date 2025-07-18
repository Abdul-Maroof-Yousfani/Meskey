<?php

namespace App\Models\Master\Account;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'voucher_no',
        'company_id',
        'voucher_date',
        'transaction_voucher_type_id',
        'payment_against',
        'against_reference_no',
        'account_id',
        'account_unique_no',
        'type',
        'is_opening_balance',
        'action',
        'amount',
        'purpose',
        'remarks',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'type' => 'string',
        'is_opening_balance' => 'string',
        'status' => 'string',
        'purpose' => 'string',
        'voucher_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function voucherType()
    {
        return $this->belongsTo(TransactionVoucherType::class, 'transaction_voucher_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
