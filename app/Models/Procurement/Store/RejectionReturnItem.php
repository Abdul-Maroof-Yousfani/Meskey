<?php

namespace App\Models\Procurement\Store;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RejectionReturnItem extends Model
{
    protected $guarded = ['id'];

    // Stock-out and ledger are posted on approval by App\Services\StoreLedgerService::postRejectionReturn().

    public function rejectionReturn(): BelongsTo
    {
        return $this->belongsTo(RejectionReturn::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id');
    }
}
