<?php

namespace App\Models\Production\JobOrder;
use App\Models\Master\FumigationCompany;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Brands;
use App\Models\BagType;
use App\Models\BagCondition;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderPackingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_id',
        'company_location_id',
        'bag_product_id',
        'bag_condition_id',
        'bag_size',
        'no_of_bags',
        'extra_bags',
        'empty_bags',
        'total_bags',
        'total_kgs',
        'metric_tons',
        'stuffing_in_container',
        'no_of_containers',
        'brand_id',
        'bag_color_id',
        'delivery_date',
        'fumigation_company_id',
        'min_weight_empty_bags',
        'description',
        'location_instruction'
    ];
    protected $casts = [
        // 'inspection_company_id' => 'array',
        // 'bag_type_id' => 'array',
        'fumigation_company_id' => 'array',
        // 'arrival_locations' => 'array',
        // 'job_order_date' => 'date',
        'delivery_date' => 'date',
        // 'loading_date' => 'date'
    ];
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Calculate totals from sub-items if they exist
            if ($model->relationLoaded('subItems') && $model->subItems->count() > 0) {
                $totalBagsFromSubItems = $model->subItems->sum('no_of_bags');
                $totalKgsFromSubItems = $model->subItems->sum(function($subItem) {
                    return $subItem->no_of_bags * $subItem->bag_size;
                });
                
                $model->total_bags = $totalBagsFromSubItems + ($model->extra_bags ?? 0) + ($model->empty_bags ?? 0);
                $model->total_kgs = $totalKgsFromSubItems;
                $model->metric_tons = $model->total_kgs / 1000;
            } else {
                // Fallback to old calculation if sub-items don't exist
                $model->total_bags = ($model->no_of_bags ?? 0) + ($model->extra_bags ?? 0) + ($model->empty_bags ?? 0);
                $model->total_kgs = ($model->no_of_bags ?? 0) * ($model->bag_size ?? 0);
            $model->metric_tons = $model->total_kgs / 1000;
            }

            // Ensure fumigation_company_id is properly formatted as array
            if ($model->fumigation_company_id && !is_array($model->fumigation_company_id)) {
                $model->fumigation_company_id = [$model->fumigation_company_id];
            }
        });
    }

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }
    public function fumigationCompanies()
    {
        return FumigationCompany::whereIn('id', $this->fumigation_company_id ?? []);
    }
    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function bagProduct()
    {
        return $this->belongsTo(Product::class, 'bag_product_id');
    }
    
    public function bagCondition()
    {
        return $this->belongsTo(BagCondition::class, 'bag_condition_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class, 'brand_id');
    }

    public function subItems()
    {
        return $this->hasMany(JobOrderPackingSubItem::class, 'job_order_packing_item_id');
    }
}