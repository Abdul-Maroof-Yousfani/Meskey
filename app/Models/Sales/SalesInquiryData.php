<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class SalesInquiryData extends Model
{
    use HasFactory;
    protected $guarded = ["id", "created_at", "updated_at"];

    public function inquiry() {
        return $this->belongsTo(SalesInquiry::class, "inquiry_id", "id");
    }

    public function item() {    
        return $this->belongsTo(Product::class, "item_id", "id");
    }
}
