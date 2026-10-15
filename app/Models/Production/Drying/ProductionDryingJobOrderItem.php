<?php

namespace App\Models\Production\Drying;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProdctionAttribute;

class ProductionDryingJobOrderItem extends Model
{
    use HasFactory;

    protected $table = 'production_drying_job_order_items';

    protected $fillable = [
        'production_drying_job_order_id',
        'production_attribute_id',
        'key',
        'type',
        'value',
        'created_by',
        'updated_by',
    ];

    public function dryingJobOrder()
    {
        return $this->belongsTo(ProductionDryingJobOrder::class, 'production_drying_job_order_id');
    }

    public function attribute()
    {
        return $this->belongsTo(ProdctionAttribute::class, 'production_attribute_id');
    }
}
