<?php

namespace App\Models\Production;

use App\Models\Product;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Brands;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOutput extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'production_voucher_id',
        'slot_id',
        'job_order_id',
        'product_id',
        'qty',
        'no_of_bags',
        'bag_size',
        'avg_weight_per_bag',
        'arrival_sub_location_id',
        'brand_id',
        'remarks',
        'qc_status',
        'qc_remarks'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'avg_weight_per_bag' => 'decimal:3',
    ];

    public function productionVoucher()
    {
        return $this->belongsTo(ProductionVoucher::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function storageLocation()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalSubLocation::class, 'arrival_sub_location_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class, 'brand_id');
    }

    public function slot()
    {
        return $this->belongsTo(ProductionSlot::class);
    }

    public function jobOrder()
    {
        return $this->belongsTo(\App\Models\Production\JobOrder\JobOrder::class);
    }
}
