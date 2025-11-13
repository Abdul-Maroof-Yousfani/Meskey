<?php

namespace App\Models;

use App\Models\Acl\Company;
use App\Models\User;
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
        'approve_user_id',
        'delete_user_id',
        'company_id'
    ];

    protected $casts = [
        'jv_date' => 'date',
    ];

    public function journalVoucherDetails()
    {
        return $this->hasMany(JournalVoucherDetail::class, 'journal_voucher_id');
    }

    public function approveUser()
    {
        return $this->belongsTo(User::class, 'approve_user_id');
    }

    public function deleteUser()
    {
        return $this->belongsTo(User::class, 'delete_user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
