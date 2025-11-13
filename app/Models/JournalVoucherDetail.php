<?php

namespace App\Models;

use App\Models\Master\Account\Account;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalVoucherDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'journal_voucher_id',
        'acc_id',
        'debit_amount',
        'credit_amount',
        'description',
        'username',
        'status',
        'timestamp'
    ];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'timestamp' => 'datetime',
    ];

    public function journalVoucher()
    {
        return $this->belongsTo(JournalVoucher::class, 'journal_voucher_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'acc_id');
    }
}
