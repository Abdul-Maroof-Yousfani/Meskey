<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;

/** "Other Products" row of an Input Analysis: a product and its percentage. */
class ProductionAnalysisOtherProduct extends Model
{
    protected $table = 'production_analysis_other_products';

    protected $fillable = [
        'production_analysis_id',
        'product_id',
        'percentage',
    ];

    protected $casts = [
        'percentage' => 'float',
    ];

    public function analysis()
    {
        return $this->belongsTo(ProductionAnalysis::class, 'production_analysis_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }
}
