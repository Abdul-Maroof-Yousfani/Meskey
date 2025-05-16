<?php

namespace App\Models\Procurement;

use App\Models\ArrivalPurchaseOrder;
use App\Models\BagType;
use App\Models\Master\Station;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseFreight extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "purchase_freights";

    protected $fillable = [
        'arrival_purchase_order_id',
        'loading_date',
        'supplier_name',
        'broker',
        'truck_no',
        'bilty_no',
        'station_id',
        'no_of_bags',
        'bag_type_id',
        'commodity',
        'loading_weight',
        'kanta_charges',
        'freight_on_bilty',
        'advance_freight',
        'bilty_slip',
        'weighbridge_slip',
        'supplier_bill'
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'loading_date'];

    public function purchaseOrder()
    {
        return $this->belongsTo(ArrivalPurchaseOrder::class, 'arrival_purchase_order_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'station_id');
    }

    public function bagType()
    {
        return $this->belongsTo(BagType::class, 'bag_type_id');
    }
}
