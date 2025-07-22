<?php

namespace App\Models\Procurement\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestData extends Model
{
    use HasFactory;

     protected $fillable = [
        'purchase_request_id',
        'category_id',
        'item_id',
        'qty',
        'remarks'
    ];

    public function JobOrder()
    {
        return $this->hasMany(PurchaseAgainstJobOrder::class);
    }

}
