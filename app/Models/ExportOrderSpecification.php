<?php

namespace App\Models;

use App\Models\Export\ExportOrder;
use App\Models\Master\ProductSlabType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportOrderSpecification extends Model
{
    use HasFactory;

    protected $fillable = [
        'export_order_id',
        'product_slab_type_id',
        'spec_name',
        'spec_value',
        'uom',
        'value_type',
    ];

    public function productSlabType()
    {
        return $this->belongsTo(ProductSlabType::class);
    }

    public function exportOrder()
    {
        return $this->belongsTo(ExportOrder::class);
    }
}
