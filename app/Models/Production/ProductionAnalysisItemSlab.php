<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionAnalysisItemSlab extends Model
{
    use HasFactory;

    protected $table = 'production_qc_analysis_item_slabs';

    protected $fillable = [
        'production_analysis_item_id',
        'slab_type_id',
        'production_analysis_value'
    ];

    public function item()
    {
        return $this->belongsTo(ProductionAnalysisItem::class, 'production_analysis_item_id');
    }

    public function slabType()
    {
        return $this->belongsTo(\App\Models\Master\ProductSlabType::class, 'slab_type_id');
    }
}
