<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UnitOfMeasure;

class ProductionMachineAnalysisItem extends Model
{
    use HasFactory;

    protected $table = 'production_machine_analysis_items';

    protected $fillable = [
        'machine_analysis_id',
        'analysis_time',
        'unit_id'
    ];

    public function parent()
    {
        return $this->belongsTo(ProductionMachineAnalysis::class, 'machine_analysis_id');
    }

    public function unit()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    public function slabs()
    {
        return $this->hasMany(ProductionMachineAnalysisItemSlab::class, 'machine_analysis_item_id');
    }
}
