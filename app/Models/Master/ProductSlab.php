<?php

namespace App\Models\Master;

use App\Models\Acl\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSlab extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_slab_type_id',
        'is_purchase_field',
        'deduction_value',
        'prefill_spec_value',
        'deduction_type',
        'company_id',
        'product_id',
        'is_tiered',
        'status',
        'from',
        'to',
        'is_export_enable',
    ];

    protected $casts = [
        'is_export_enable' => 'boolean',
        'is_tiered' => 'boolean',
        'is_purchase_field' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function slabType()
    {
        return $this->belongsTo(ProductSlabType::class, 'product_slab_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeGeneralEnabled($query)
    {
        return $query->active();
    }

    public function scopeExportEnabled($query)
    {
        return $query->active()->where('is_export_enable', true);
    }
}
