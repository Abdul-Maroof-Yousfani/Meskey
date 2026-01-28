<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadingSlip extends Model
{
    use HasFactory;

    protected $fillable = [
        'loading_program_item_id',
        'customer',
        'commodity',
        'so_qty',
        'do_qty',
        'factory',
        'gala',
        'no_of_bags',
        'bag_size',
        'kilogram',
        'remarks',
        'delivery_order_id',
        'labour',
        'created_by',
        'company_id'
    ];

    protected $casts = [
        'so_qty' => 'decimal:2',
        'do_qty' => 'decimal:2',
        'bag_size' => 'decimal:2',
        'kilogram' => 'decimal:2',
        'no_of_bags' => 'integer'
    ];

    public function loadingProgramItem(): BelongsTo
    {
        return $this->belongsTo(LoadingProgramItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function secondWeighbridge() {
        return $this->hasOne(\App\Models\Sales\SecondWeighbridge::class, "loading_slip_id");
    }

    public function deliveryOrder() {
        return $this->belongsTo(\App\Models\Sales\DeliveryOrder::class, "delivery_order_id");
    }

    public function logs() {
        return $this->hasMany(\App\Models\Sales\LoadingSlipLog::class, "loading_slip_id");
    }

    /**
     * Check if this loading slip has a rejected dispatch QC (latest QC is rejected)
     */
    
    public function hasRejectedDispatchQc(): bool
    {
        $latestDispatchQc = $this->loadingProgramItem?->dispatchQc;
        
        if (!$latestDispatchQc || $latestDispatchQc->status !== 'reject') {
            return false;
        }
        
        return true;
    }

    /**
     * Get the latest rejected dispatch QC for this loading slip
     */
    public function getLatestRejectedDispatchQc()
    {
        return $this->loadingProgramItem?->latestRejectedDispatchQc;
    }

    /**
     * Check if loading slip can be edited
     * - Can edit if no dispatch QC exists
     * - Can edit if the latest dispatch QC is rejected
     * - Cannot edit if an accepted dispatch QC exists
     */
    public function canBeEdited(): bool
    {
        $loadingProgramItem = $this->loadingProgramItem;
        
        if (!$loadingProgramItem) {
            return true;
        }
        
        // If there's an accepted dispatch QC, editing is not allowed
        if ($loadingProgramItem->hasAcceptedDispatchQc()) {
            return false;
        }
        
        // If no dispatch QC exists or latest is rejected, editing is allowed
        return true;
    }
}
