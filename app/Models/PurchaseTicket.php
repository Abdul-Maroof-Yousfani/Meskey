<?php

namespace App\Models;

use App\Models\Acl\Company;
use App\Models\Procurement\PaymentRequestData;
use App\Models\Procurement\PurchaseFreight;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseTicket extends Model
{
    use SoftDeletes;

    protected $table = 'purchase_tickets';

    protected $fillable = [
        'unique_no',
        'company_id',
        'product_id',
        'is_custom_qc',
        'qc_product',
        'purchase_order_id',
        'bag_weight',
        'qc_status',
        'freight_status',
        'payment_request_status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function qcProduct()
    {
        return $this->belongsTo(Product::class, 'qc_product');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(ArrivalPurchaseOrder::class, 'purchase_order_id');
    }

    public function paymentRequestData()
    {
        return $this->hasMany(PaymentRequestData::class, 'ticket_id');
    }

    public function purchaseFreight()
    {
        return $this->hasOne(PurchaseFreight::class, 'purchase_ticket_id');
    }

    public function purchaseSamplingRequests()
    {
        return $this->hasMany(PurchaseSamplingRequest::class, 'purchase_ticket_id');
    }
}
