<?php

namespace App\Models\Master\Account;

use App\Models\Master\CompanyLocation;
use App\Models\Master\Supplier;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodReceiveNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'grn_id',
        'stock_id',
        'grn_number',
        'reference_number',
        'supplier_id',
        'location_id',
        'purchase_order_id',
        'product_id',
        'model_id',
        'model_type',
        'voucher_type',
        'voucher_no',
        'qty',
        'type',
        'narration',
        'price',
        'avg_price_per_kg',
        'status',
        'received_at',
        'verified_at',
        'received_by',
        'verified_by',
        'notes',
        'rejection_reason',
        'batch_number',
        'expiry_date',
        'quality_status',
        'quality_notes',
        'accepted_quantity',
        'rejected_quantity'
    ];

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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(CompanyLocation::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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
}
