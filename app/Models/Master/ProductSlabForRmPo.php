<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSlabForRmPo extends Model
{
    use SoftDeletes;

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
}
