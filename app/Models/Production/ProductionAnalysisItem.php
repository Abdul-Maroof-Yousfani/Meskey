<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionAnalysisItem extends Model
{
    use HasFactory;

    protected $table = 'production_qc_analysis_items';

    protected $fillable = [
        'production_analysis_id',
        'analysis_time',
        'unit_id'
    ];

    public function parent()
    {
        return $this->belongsTo(ProductionAnalysis::class, 'production_analysis_id');
    }

    public function unit()
    {
        return $this->belongsTo(\App\Models\UnitOfMeasure::class, 'unit_id');
    }

    public function slabs()
    {
        return $this->hasMany(ProductionAnalysisItemSlab::class, 'production_analysis_item_id');
    }
}
