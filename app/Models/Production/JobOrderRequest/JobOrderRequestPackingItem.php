<?php

namespace App\Models\Production\JobOrderRequest;

use App\Models\Master\FumigationCompany;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Brands;
use App\Models\BagCondition;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderRequestPackingItem extends Model
{
    use HasFactory;

    protected $table = 'job_order_request_packing_items';

    protected $fillable = [
        'job_order_request_id',
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
        'thread_color_id',
        'stitching_id',
        'delivery_date',
        'fumigation_company_id',
        'min_weight_empty_bags',
        'extra_bags_percentage',
        'empty_bags_percentage',
        'attachment',
    ];

    protected $casts = [
        'fumigation_company_id' => 'array',
        'delivery_date' => 'date',
    ];

    public function jobOrderRequest()
    {
        return $this->belongsTo(JobOrderRequest::class);
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
        return $this->belongsTo(\App\Models\Master\Color::class, 'bag_color_id');
    }

    public function threadColor()
    {
        return $this->belongsTo(\App\Models\Master\Color::class, 'thread_color_id');
    }

    public function stitching()
    {
        return $this->belongsTo(\App\Models\Master\Stitching::class, 'stitching_id');
    }

    public function subItems()
    {
        return $this->hasMany(JobOrderRequestPackingSubItem::class, 'job_order_request_packing_item_id');
    }
}
