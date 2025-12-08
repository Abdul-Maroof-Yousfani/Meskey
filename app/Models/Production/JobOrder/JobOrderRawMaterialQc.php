<?php

namespace App\Models\Production\JobOrder;

use App\Models\Master\CompanyLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class JobOrderRawMaterialQc extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'qc_no',
        'qc_date',
        'job_order_id',
        'location_id',
        'mill',
        'commodities'
    ];

    protected $casts = [
        'qc_date' => 'date',
        'commodities' => 'array'
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function items()
    {
        return $this->hasMany(JobOrderRawMaterialQcItem::class,'job_order_rm_qc_id','id');
    }

    public function getWeightedAveragesAttribute()
    {
        $averages = [];

        foreach ($this->items->groupBy('product_id') as $productId => $productItems) {
            $totalQuantity = $productItems->sum('suggested_quantity');
            $productAverages = [];

            $parameters = [];
            foreach ($productItems as $item) {
                foreach ($item->parameters as $param) {
                    $parameters[$param->parameter_name][] = [
                        'value' => $param->parameter_value,
                        'quantity' => $item->suggested_quantity
                    ];
                }
            }

            foreach ($parameters as $paramName => $paramData) {
                $weightedSum = 0;
                foreach ($paramData as $data) {
                    $weightedSum += $data['value'] * $data['quantity'];
                }
                $productAverages[$paramName] = $totalQuantity > 0 ? $weightedSum / $totalQuantity : 0;
            }

            $averages[$productId] = [
                'total_quantity' => $totalQuantity,
                'averages' => $productAverages
            ];
        }

        return $averages;
    }
}