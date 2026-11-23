<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use App\Models\Acl\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSlabForRmPo extends Model
{
    use SoftDeletes, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $table = 'product_slab_for_rm_po';

    protected $fillable = [
        'arrival_purchase_order_id',
        'slab_id',
        'company_id',
        'product_id',
        'product_slab_type_id',
        'from',
        'to',
        'deduction_type',
        'deduction_value',
        'status'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function slabType()
    {
        return $this->belongsTo(ProductSlabType::class, 'product_slab_type_id');
    }
}
