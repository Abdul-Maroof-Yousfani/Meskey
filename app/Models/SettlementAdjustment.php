<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettlementAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_type',
        'reference_id',
        'voucher_no',
        'amount',
    ];
}
