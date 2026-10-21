<?php

namespace App\Models\Master;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ByProductRate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'by_product_rates';

    protected $fillable = [
        'company_id',
        'location_id',
        'sublocation_id',
        'plant_id',
        'product_id',
        'title',
        'from_date',
        'to_date',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function subLocation()
    {
        return $this->belongsTo(ArrivalLocation::class, 'sublocation_id');
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class, 'plant_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Acl\Company::class, 'company_id');
    }

    public function items()
    {
        return $this->hasMany(ByProductRateItem::class, 'by_product_rate_id');
    }

    public function byProducts()
    {
        return $this->belongsToMany(Product::class, 'by_product_rate_items', 'by_product_rate_id', 'by_product_id')
            ->withPivot('rate')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
