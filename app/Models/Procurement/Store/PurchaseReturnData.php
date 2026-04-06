<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\Account\Stock;
use App\Models\Master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Product;

class PurchaseReturnData extends Model
{
    use HasFactory;

    protected $table = "purchase_return_data";
    protected $guarded = [
        "id",
        "created_at",
        "updated_at"
    ];



    public function purchase_return() {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function purchase_bill_data() {
        return $this->belongsTo(PurchaseBillData::class, 'purchase_bill_data_id');
    }
    
    public function item() {
        return $this->belongsTo(Product::class, 'item_id');
    }
    
}
