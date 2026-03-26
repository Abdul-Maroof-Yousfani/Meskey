<?php

namespace App\Models\Export;

use App\Models\ProductSlabType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationSpecification extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function slabType()
    {
        return $this->belongsTo(ProductSlabType::class, 'product_slab_type_id');
    }
}
