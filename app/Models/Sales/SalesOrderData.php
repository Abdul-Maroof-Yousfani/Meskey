<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderData extends Model
{
    use HasFactory;
    protected $fillable = [
        "item_id",
        "qty",
        "rate",
        "sale_order_id"
    ];

    public function sales_order() {
        return $this->belongsTo(SalesOrder::class, "sale_order_id");
    }
}
