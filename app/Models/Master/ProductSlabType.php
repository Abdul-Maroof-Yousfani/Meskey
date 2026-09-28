<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSlabType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'calculation_base_type',
        'qc_symbol',
        'status',
        'for_general_item',
        'order_by',
    ];

    public function getOrderByAttribute()
    {
        return $this->attributes['order_by'] ?? null;
    }

    public function setOrderByAttribute($value)
    {
        $this->attributes['order_by'] = $value;
    }

    public function slabs()
    {
        return $this->hasMany(ProductSlab::class);
    }

    /**
     * Scope a query to sort records by order_by ascending (with 0 and null at bottom), then by id ascending.
     */
    public function scopeOrdered($query)
    {
        return $query->orderByRaw('CASE WHEN order_by IS NULL OR order_by = 0 THEN 1 ELSE 0 END ASC, order_by ASC, id ASC');
    }

    public function scopeOrderByCustom($query)
    {
        return $this->scopeOrdered($query);
    }
}
