<?php

namespace App\Models\Production;

use App\Models\Master\ProductSlabType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionAnalysisData extends Model
{
    use HasFactory;

    protected $table = 'production_analysis_data';

    protected $fillable = [
        'production_analysis_id',
        'analysis_time',
        'slab_type_id',
        'production_analysis_value',
    ];

    public function productionAnalysis()
    {
        return $this->belongsTo(ProductionAnalysis::class, 'production_analysis_id');
    }

    public function slabType()
    {
        return $this->belongsTo(ProductSlabType::class, 'slab_type_id');
    }
}
