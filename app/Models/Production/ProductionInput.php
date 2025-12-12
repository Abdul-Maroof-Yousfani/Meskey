<?php

namespace App\Models\Production;

use App\Models\Product;
use App\Models\Master\ArrivalSubLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionInput extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'production_voucher_id',
        'product_id',
        'location_id',
        'qty',
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

    public function location()
    {
        return $this->belongsTo(ArrivalSubLocation::class, 'location_id');
    }
}
