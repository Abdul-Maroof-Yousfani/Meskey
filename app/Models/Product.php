<?php

namespace App\Models;


use App\Models\Master\ProductSlab;
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
        'unit_of_measure_id',
        'unique_no',
        'name',
        'description',
        'bardcode',
        'image',
        'price',
        'status'
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
}