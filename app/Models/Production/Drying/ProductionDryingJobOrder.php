<?php

namespace App\Models\Production\Drying;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Master\CompanyLocation;
use App\Models\Master\CropYear;
use App\Models\Product;
use App\Models\User;

class ProductionDryingJobOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_drying_job_orders';

    protected $fillable = [
        'company_id',
        'company_location_id',
        'export_order_id',
        'production_recipe_id',
        'recipe_name',
        'job_order_no',
        'job_order_date',
        'ref_no',
        'attention_to',
        'product_id',
        'crop_year',
        'crop_year_id',
        'moisture_level',
        'moisture_value',
        'remarks',
        'order_description',
        'other_specifications',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'job_order_date' => 'date',
        'attention_to' => 'array',
    ];

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function exportOrder()
    {
        return $this->belongsTo(\App\Models\Export\ExportOrder::class, 'export_order_id');
    }

    public function productionRecipe()
    {
        return $this->belongsTo(\App\Models\Master\ProductionRecipe::class, 'production_recipe_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function cropYearModel()
    {
        return $this->belongsTo(CropYear::class, 'crop_year_id');
    }

    public function items()
    {
        return $this->hasMany(ProductionDryingJobOrderItem::class, 'production_drying_job_order_id');
    }

    public function specifications()
    {
        return $this->hasMany(ProductionDryingJobOrderSpecification::class, 'production_drying_job_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getAttentionUsersAttribute()
    {
        if (empty($this->attention_to)) {
            return collect();
        }
        $ids = is_array($this->attention_to) ? $this->attention_to : json_decode($this->attention_to, true);
        return User::whereIn('id', (array) $ids)->get();
    }
}
