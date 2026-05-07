<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationPackingItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function bagType()
    {
        return $this->belongsTo(\App\Models\BagType::class, 'bag_type_id');
    }

    public function bagPacking()
    {
        return $this->belongsTo(\App\Models\BagPacking::class, 'bag_packing_id');
    }
}
