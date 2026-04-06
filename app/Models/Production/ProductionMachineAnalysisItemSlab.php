<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Master\ProductSlabType;

class ProductionMachineAnalysisItemSlab extends Model
{
    use HasFactory;

    protected $table = 'production_machine_analysis_item_slabs';

    protected $fillable = [
        'machine_analysis_item_id',
        'slab_type_id',
        'analysis_value'
    ];

    public function item()
    {
        return $this->belongsTo(ProductionMachineAnalysisItem::class, 'machine_analysis_item_id');
    }

    public function slabType()
    {
        return $this->belongsTo(ProductSlabType::class, 'slab_type_id');
    }
}
