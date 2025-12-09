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
        'product_id',
        'qty',
        'storage_location_id',
        'brand_id',
        'remarks'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
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
        return $this->belongsTo(CompanyLocation::class, 'storage_location_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class, 'brand_id');
    }
}
