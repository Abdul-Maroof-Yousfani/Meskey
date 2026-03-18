<?php

namespace App\Models\Master\Account;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'product_id',
        'voucher_type',
        'voucher_no',
        'qty',
        'type',
        'narration',
        'price',
        'avg_price_per_kg',
        'parent_id',
        'company_location_id',
        'arrival_id',
        'subarrival_id'
    ];
}
