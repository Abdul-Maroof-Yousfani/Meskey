<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\Supplier;
use App\Models\Product;
use App\Models\User;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RejectionReturn extends Model
{
    use SoftDeletes, HasApproval;

    protected $guarded = ['id'];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderReceiving::class, 'grn_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RejectionReturnItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
