<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory;

    public function sales_order_data() {
        return $this->hasMany(SalesOrderData::class, "sale_order_id");
    }
}
