<?php

namespace App\Models;

use App\Models\Acl\Company;
use App\Models\Master\ProductSlab;
use App\Models\Master\QcReliefParameter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    // Fillable attributes for mass assignment
    protected $fillable = [
        'company_id',
        'category_id',
        'bag_weight_for_purchasing',
        'unit_of_measure_id',
        'product_type',
        'description',
        'unique_no',
        'bardcode',
        'status',
        'image',
        'price',
        'name',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unitOfMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function slabs()
    {
        return $this->hasMany(ProductSlab::class);
    }

    public function reliefParameters()
    {
        return $this->hasMany(QcReliefParameter::class);
    }
}
