<?php

namespace App\Models\ProductionV2;

use App\Models\BagCondition;
use App\Models\Master\Brands;
use App\Models\Master\Color;
use App\Models\Master\CompanyLocation;
use App\Models\Master\FumigationCompany;
use App\Models\Master\Stitching;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionJobOrderPackingItem extends Model
{
    use HasFactory;

    protected $table = 'production_job_order_packing_items';

    protected $fillable = [
        'job_order_id',
        'company_location_id',
        'bag_product_id',
        'bag_condition_id',
        'bag_size',
        'no_of_bags',
        'extra_bags',
        'extra_bags_percentage',
        'empty_bags',
        'total_bags',
        'total_kgs',
        'metric_tons',
        'stuffing_in_container',
        'no_of_containers',
        'brand_id',
        'bag_color_id',
        'thread_color_id',
        'stitching_id',
        'min_weight_empty_bags',
        'delivery_date',
        'fumigation_company_id',
        'description',
        'location_instruction',
    ];

    protected $casts = [
        'fumigation_company_id' => 'array',
        'delivery_date' => 'date',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrderV2::class, 'job_order_id');
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

    public function bagColor()
    {
        return $this->belongsTo(Color::class, 'bag_color_id');
    }

    public function threadColor()
    {
        return $this->belongsTo(Color::class, 'thread_color_id');
    }

    public function stitching()
    {
        return $this->belongsTo(Stitching::class, 'stitching_id');
    }

    public function subItems()
    {
        return $this->hasMany(ProductionJobOrderPackingSubItem::class, 'job_order_packing_item_id');
    }
}
