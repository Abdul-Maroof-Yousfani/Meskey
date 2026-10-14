<?php

namespace App\Models\Production;

use App\Models\Master\Brands;
use App\Models\Master\CompanyLocation;
use App\Models\Master\CropYear;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\BagPacking;
use App\Models\Production\ProductionAnalysisRequest;
use App\Models\Production\ProductionMachineAnalysis;
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
        'product_id',
        'production_analysis_type',
        'arrival_location_id',
        'plant_id',
        'analysis_request_id',
        'production_voucher_id',
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

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function cropYear()
    {
        return $this->belongsTo(CropYear::class, 'crop_year_id');
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalLocation::class, 'arrival_location_id');
    }

    public function plant()
    {
        return $this->belongsTo(\App\Models\Master\Plant::class, 'plant_id');
    }

    public function jobOrders()
    {
        return $this->belongsToMany(JobOrder::class, 'job_orders_against_production_analysis', 'production_id', 'job_order_id');
    }

    public function analysisData()
    {
        return $this->hasMany(ProductionAnalysisData::class, 'production_analysis_id');
    }

    public function items()
    {
        return $this->hasMany(ProductionAnalysisItem::class, 'production_analysis_id');
    }

    public function allItemSlabs()
    {
        return $this->hasManyThrough(
            ProductionAnalysisItemSlab::class, 
            ProductionAnalysisItem::class, 
            'production_analysis_id', // Foreign key on items table
            'production_analysis_item_id' // Foreign key on slabs table
        );
    }

    public function analysisRequest()
    {
        return $this->belongsTo(ProductionAnalysisRequest::class, 'analysis_request_id');
    }

    public function productionVoucher()
    {
        return $this->belongsTo(ProductionVoucher::class, 'production_voucher_id');
    }

    /**
     * Check if a previous analysis record exists with the same Arrival Location, Plant and Date.
     * Searches both production_analysis (input/output) and production_machine_analysis.
     *
     * @param int|null $arrivalLocationId
     * @param int|null $plantId
     * @param string|null $date
     * @param string|null $requestDate
     * @param int|null $excludeId
     * @param string|null $excludeType ('analysis' or 'machine')
     * @return \App\Models\Product|null
     */
    public static function getFirstProductForRun($arrivalLocationId, $plantId, $date, $requestDate = null, $excludeId = null, $excludeType = null)
    {
        if (!$arrivalLocationId || !$plantId) {
            return null;
        }

        $dates = array_filter(array_unique([
            $date ? \Carbon\Carbon::parse($date)->format('Y-m-d') : null,
            $requestDate ? \Carbon\Carbon::parse($requestDate)->format('Y-m-d') : null,
        ]));

        if (empty($dates)) {
            return null;
        }

        $matchingReqIds = ProductionAnalysisRequest::where('arrival_location_id', $arrivalLocationId)
            ->where('plant_id', $plantId)
            ->where(function($q) use ($dates) {
                foreach ($dates as $d) {
                    $q->orWhereDate('request_date', $d);
                }
            })
            ->pluck('id');

        // 1. Search in production_analysis (input and output)
        $firstPA = self::where('arrival_location_id', $arrivalLocationId)
            ->where('plant_id', $plantId)
            ->whereNotNull('product_id')
            ->where(function ($q) use ($dates, $matchingReqIds) {
                $q->where(function($sub) use ($dates) {
                    foreach ($dates as $d) {
                        $sub->orWhereDate('analysis_date', $d);
                    }
                });
                if ($matchingReqIds->isNotEmpty()) {
                    $q->orWhereIn('analysis_request_id', $matchingReqIds);
                }
            })
            ->when($excludeType === 'analysis' && $excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('id', 'asc')
            ->first();

        // 2. Search in production_machine_analysis
        $firstPMA = ProductionMachineAnalysis::where('arrival_location_id', $arrivalLocationId)
            ->where('plant_id', $plantId)
            ->whereNotNull('product_id')
            ->where(function ($q) use ($dates, $matchingReqIds) {
                $q->where(function($sub) use ($dates) {
                    foreach ($dates as $d) {
                        $sub->orWhereDate('analysis_date', $d);
                    }
                });
                if ($matchingReqIds->isNotEmpty()) {
                    $q->orWhereIn('analysis_request_id', $matchingReqIds);
                }
            })
            ->when($excludeType === 'machine' && $excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('id', 'asc')
            ->first();

        if ($firstPA && $firstPMA) {
            $earlier = ($firstPA->created_at <= $firstPMA->created_at) ? $firstPA : $firstPMA;
            return $earlier->product;
        }

        if ($firstPA) {
            return $firstPA->product;
        }

        if ($firstPMA) {
            return $firstPMA->product;
        }

        return null;
    }
}
