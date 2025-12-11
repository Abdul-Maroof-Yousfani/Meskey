<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

     protected $fillable = [
        'company_id',
        'account_title',
        'bank_name',
        'iban',
        'account_no',
        'swift_code',
        'bank_address',
        'description',
        'status',
    ];
}
