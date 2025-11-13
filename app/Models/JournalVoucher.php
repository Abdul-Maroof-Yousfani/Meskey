<?php

namespace App\Models;

use App\Models\Master\Account\Account;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalVoucher extends Model
{
    use HasFactory, SoftDeletes, HasApproval;

    protected $fillable = [
        'jv_date',
        'jv_no',
        'description',
        'username',
        'status',
        'jv_status',
        'approve_username',
        'delete_username'
    ];

    protected $casts = [
        'jv_date' => 'date',
    ];

    public function journalVoucherDetails()
    {
        return $this->hasMany(JournalVoucherDetail::class, 'journal_voucher_id');
    }
}
