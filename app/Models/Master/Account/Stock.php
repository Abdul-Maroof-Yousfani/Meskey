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
        'avg_cost_price',
        'parent_id',
        'company_location_id',
        'arrival_id',
        'subarrival_id',
        'parentable_id',
        'bag_packing_id',
        'parentable_type',
        'brand_id',
        'production_voucher_id'
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function companyLocation()
    {
        return $this->belongsTo(\App\Models\Master\CompanyLocation::class, 'company_location_id');
    }

    public function productionVoucher()
    {
        return $this->belongsTo(\App\Models\Production\ProductionVoucher::class, 'production_voucher_id');
    }
}
