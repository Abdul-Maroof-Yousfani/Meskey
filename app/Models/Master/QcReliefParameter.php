<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QcReliefParameter extends Model
{
    use HasFactory, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $fillable = [
        'product_id',
        'parameter_name',
        'parameter_type',
        'slab_type_id',
        'relief_percentage',
        'is_active'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
