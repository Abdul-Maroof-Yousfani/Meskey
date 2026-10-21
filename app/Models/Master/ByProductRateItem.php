<?php

namespace App\Models\Master;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ByProductRateItem extends Model
{
    use HasFactory;

    protected $table = 'by_product_rate_items';

    protected $fillable = [
        'by_product_rate_id',
        'by_product_id',
        'rate',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
    ];

    public function byProductRate()
    {
        return $this->belongsTo(ByProductRate::class, 'by_product_rate_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'by_product_id');
    }

    public function byProduct()
    {
        return $this->belongsTo(Product::class, 'by_product_id');
    }
}
