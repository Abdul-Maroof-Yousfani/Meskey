<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\CompanyLocation;
use App\Models\Master\Supplier;
use App\Models\Procurement\PaymentRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;
    protected $table = "purchase_orders";
    protected $guarded = [];

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchase_request()
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function purchaseOrderData()
    {
        return $this->hasMany(PurchaseOrderData::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderData::class, 'purchase_order_id');
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'purchase_order_id');
    }

    public function getTotalAmountAttribute()
    {
        return $this->items()->sum('total');
    }

    public function getTotalPaidAttribute()
    {
        return $this->paymentRequests()->where('status', 'approved')->sum('amount');
    }

    public function getRemainingAmountAttribute()
    {
        return max(0, $this->total_amount - $this->total_paid);
    }

    public function getIsFullyPaidAttribute()
    {
        return $this->remaining_amount <= 0;
    }
}
