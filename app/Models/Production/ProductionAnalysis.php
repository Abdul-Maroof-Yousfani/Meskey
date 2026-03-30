<?php

namespace App\Models\Production;

use App\Models\Master\Brands;
use App\Models\Master\CompanyLocation;
use App\Models\Master\CropYear;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\BagPacking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionAnalysis extends Model
{
    use HasFactory;

    protected $table = 'production_analysis';

    protected $fillable = [
        'analysis_date',
        'brand_id',
        'bag_packing_id',
        'location_id',
        'variety',
        'crop_year_id',
        'milling_degree',
        'inner_stitching',
        'outer_stitching',
        'remarks',
        'production_analysis_type',
    ];

    protected $casts = [
        'analysis_date' => 'date',
    ];

    public function brand()
    {
        return $this->belongsTo(Brands::class, 'brand_id');
    }

    public function bagPacking()
    {
        return $this->belongsTo(BagPacking::class, 'bag_packing_id');
    }

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function cropYear()
    {
        return $this->belongsTo(CropYear::class, 'crop_year_id');
    }

    public function jobOrders()
    {
        return $this->belongsToMany(JobOrder::class, 'job_orders_against_production_analysis', 'production_id', 'job_order_id');
    }

    public function analysisData()
    {
        return $this->hasMany(ProductionAnalysisData::class, 'production_analysis_id');
    }
}
