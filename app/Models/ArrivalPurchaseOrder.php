<?php

namespace App\Models;

use App\Models\Master\Broker;
use App\Models\Master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalPurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'contract_no',
        'contract_date',
        'company_location_id',
        'sauda_type_id',
        'truck_size_range_id',
        'account_of',
        'supplier_id',
        'supplier_commission',
        'broker_one_id',
        'broker_one_commission',
        'broker_two_id',
        'broker_two_commission',
        'broker_three_id',
        'broker_three_commission',
        'product_id',
        'line_type',
        'bag_weight',
        'bag_rate',
        'delivery_date',
        'credit_days',
        'delivery_address',
        'rate_per_kg',
        'rate_per_mound',
        'rate_per_100kg',
        'calculation_type',
        'no_of_trucks',
        'total_quantity',
        'min_quantity',
        'max_quantity',
        'no_of_bags',
        'is_replacement',
        'weighbridge_from',
        'remarks',
        'status'
    ];

    public function broker()
    {
        return $this->belongsTo(Broker::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
