<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\CompanyLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;
    protected $table = "purchase_orders";
    protected $guarded = [];

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

     public function purchase_request()
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function po_data()
    {
        return $this->hasMany(PurchaseOrderData::class);
    }
}
