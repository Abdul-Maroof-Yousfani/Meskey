<?php

namespace App\Models\Sales;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivingRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'receiving_request_id',
        'delivery_challan_data_id',
        'item_id',
        'item_name',
        'dispatch_weight',
        'receiving_weight',
        'difference_weight',
        'seller_portion',
        'remaining_amount',
    ];

    protected $casts = [
        'dispatch_weight' => 'decimal:2',
        'receiving_weight' => 'decimal:2',
        'difference_weight' => 'decimal:2',
        'seller_portion' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function receivingRequest()
    {
        return $this->belongsTo(ReceivingRequest::class, 'receiving_request_id');
    }

    public function deliveryChallanData()
    {
        return $this->belongsTo(DeliveryChallanData::class, 'delivery_challan_data_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }
}

