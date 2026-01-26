<?php


namespace App\Models\Procurement\Store;

use App\Models\Master\CompanyLocation;
use App\Models\Master\Supplier;
use App\Models\Procurement\PaymentRequest;
use App\Models\Product;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderReceiving extends Model
{
    use SoftDeletes, HasApproval;

    protected $fillable = [
        'purchase_order_receiving_no',
        'purchase_request_id',
        'purchase_order_id',
        'order_receiving_date',
        'location_id',
        'supplier_id',
        'company_id',
        'reference_no',
        'description',
        'created_by',
        'truck_no',
        'dc_no',
        'am_approval_status',
        'am_change_made',
    ];

    // protected $fillable = [
    //     'grn_id',
    //     'stock_id',
    //     'grn_number',
    //     'reference_number',
    //     'supplier_id',
    //     'location_id',
    //     'purchase_order_receiving_id',
    //     'product_id',
    //     'model_id',
    //     'model_type',
    //     'voucher_type',
    //     'voucher_no',
    //     'qty',
    //     'type',
    //     'narration',
    //     'price',
    //     'avg_price_per_kg',
    //     'status',
    //     'received_at',
    //     'verified_at',
    //     'received_by',
    //     'verified_by',
    //     'notes',
    //     'rejection_reason',
    //     'batch_number',
    //     'expiry_date',
    //     'quality_status',
    //     'quality_notes',
    //     'accepted_quantity',
    //     'rejected_quantity'
    // ];

    protected $casts = [
        'received_at' => 'datetime',
        'verified_at' => 'datetime',
        'expiry_date' => 'date',
        'qty' => 'decimal:2',
        'price' => 'decimal:2',
        'avg_price_per_kg' => 'decimal:2',
        'accepted_quantity' => 'decimal:2',
        'rejected_quantity' => 'decimal:2',
    ];
    public function purchase_order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchase_request(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }


    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(CompanyLocation::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseOrderReceivingData(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceivingData::class, 'purchase_order_receiving_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the parent model (polymorphic).
     */
    public function model()
    {
        return $this->morphTo();
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'purchase_order_receiving_id');
    }

    public function getTotalPaidAttribute()
    {
        return $this->paymentRequests()->where('status', 'approved')->sum('amount');
    }

    public function getRemainingAmountAttribute()
    {
        return max(0, $this->price - $this->total_paid);
    }

    public function getIsFullyPaidAttribute()
    {
        return $this->remaining_amount <= 0;
    }

    public function bills() {
        return $this->hasMany(PurchaseBill::class, "purchase_order_receiving_id");
    }

    public function scopeDoesNotHaveBills($query){
        return $query->whereRaw("(SELECT COUNT(*) 
            FROM purchase_order_receiving_data 
            WHERE purchase_order_receiving_data.purchase_order_receiving_id = purchase_order_receivings.id
        ) != (
            SELECT COUNT(*) 
            FROM purchase_bills 
            WHERE purchase_bills.purchase_order_receiving_id = purchase_order_receivings.id
        )");
    }

}
