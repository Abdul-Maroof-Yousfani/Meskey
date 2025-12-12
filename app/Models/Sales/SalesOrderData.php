<?php

namespace App\Models\Sales;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderData extends Model
{
    use HasFactory;
    protected $fillable = [
        "item_id",
        "qty",
        "rate",
        "sale_order_id",
        "pack_size",
        "brand_id",
        "sales_inquiry_id",
        "bag_type",
        "bag_size",
        "no_of_bags"
    ];

    public function sales_order() {
        return $this->belongsTo(SalesOrder::class, "sale_order_id");
    }

    public function sale_inquiry_data() {
        return $this->belongsTo(SalesInquiryData::class, "sales_inquiry_id");
    }

    public function item() {
        return $this->belongsTo(Product::class, "item_id");
    }
}
