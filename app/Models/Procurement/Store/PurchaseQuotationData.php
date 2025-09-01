<?php

namespace App\Models\Procurement\Store;

use App\Models\Category;
use App\Models\Master\Supplier;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseQuotationData extends Model
{
    use HasFactory;

    protected $table = "purchase_quotation_data";
    protected $guarded = [];

    public function purchase_quotation()
    {
        return $this->belongsTo(PurchaseQuotation::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function item()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function approval()
    {
        return $this->hasMany(PurchaseItemApprove::class, 'purchase_request_data_id');
    }
}
