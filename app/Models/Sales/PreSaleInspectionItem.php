<?php

namespace App\Models\Sales;

use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreSaleInspectionItem extends Model
{
    use HasFactory;

    protected $table = 'pre_sale_inspection_items';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'weight' => 'float',
    ];

    public function item()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function preSaleInspection()
    {
        return $this->belongsTo(PreSaleInspection::class, 'pre_sale_inspection_id');
    }

    public function factory()
    {
        return $this->belongsTo(ArrivalLocation::class, 'arrival_location_id');
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(ArrivalLocation::class, 'arrival_location_id');
    }

    public function section()
    {
        return $this->belongsTo(ArrivalSubLocation::class, 'arrival_sub_location_id');
    }

    public function arrivalSubLocation()
    {
        return $this->belongsTo(ArrivalSubLocation::class, 'arrival_sub_location_id');
    }
}
