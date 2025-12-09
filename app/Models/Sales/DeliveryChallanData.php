<?php

namespace App\Models\Sales;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryChallanData extends Model
{
    use HasFactory;
    protected $guarded = [ "id", "created_at", "updated" ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function deliveryChallan()
    {
        return $this->belongsTo(DeliveryChallan::class, 'delivery_challan_id');
    }
}
