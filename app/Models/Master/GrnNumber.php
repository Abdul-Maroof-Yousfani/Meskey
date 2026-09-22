<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;

class GrnNumber extends Model
{
    protected $fillable = ['model_id', 'model_type', 'unique_no', 'location_id', 'product_id', 'purchase_order_id', 'supplier_id'];

    public function purchaseOrder()
    {
        return $this->belongsTo(\App\Models\ArrivalPurchaseOrder::class, 'purchase_order_id');
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Master\Supplier::class, 'supplier_id');
    }

    public function paymentRequestData()
    {
        return $this->hasMany(\App\Models\Procurement\PaymentRequestData::class, 'grn_no', 'unique_no');
    }

    public function paymentRequestDatas()
    {
        return $this->hasMany(\App\Models\Procurement\PaymentRequestData::class, 'grn_no', 'unique_no');
    }
}
