<?php

namespace App\Models\Procurement\Store;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestData extends Model
{
    use HasFactory;

     protected $guarded = [];

    public function JobOrder()
    {
        return $this->hasMany(PurchaseAgainstJobOrder::class);
    }

    public function purchase_request()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class,'category_id');
    }

    public function item()
    {
        return $this->belongsTo(Product::class,'item_id');
    }

     public function purchase_quotation_data()
    {
        return $this->hasOne(PurchaseQuotationData::class,'purchase_request_data_id');
    }

    public function approval()
    {
        return $this->hasMany(PurchaseItemApprove::class, 'purchase_request_data_id');
    }

}
