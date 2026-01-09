<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Master\Brand as Brands;

class LoadingProgramItem extends Model
{
    use HasFactory;

    protected $table = 'loading_program_items';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function loadingProgram()
    {
        return $this->belongsTo(LoadingProgram::class);
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalLocation::class);
    }

    public function subArrivalLocation()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalSubLocation::class);
    }

    public function brand()
    {
        return $this->belongsTo(\App\Models\Master\Brands::class, "brand_id");
    }

    public function firstWeighbridge()
    {
        return $this->hasOne(\App\Models\Sales\FirstWeighbridge::class, "loading_program_item_id");
    }
    public function salesQc() {
        return $this->hasOne(\App\Models\Sales\SalesQc::class);
    }
    
    /**
     * Get all dispatch QCs for this ticket (supports multiple QCs after rejections)
     */
    public function dispatchQcs() {
        return $this->hasMany(\App\Models\Sales\DispatchQc::class);
    }
    
    /**
     * Get the latest dispatch QC (for backward compatibility)
     */
    public function dispatchQc() {
        return $this->hasOne(\App\Models\Sales\DispatchQc::class)->latestOfMany();
    }
    
    /**
     * Get the latest accepted dispatch QC
     */
    public function acceptedDispatchQc() {
        return $this->hasOne(\App\Models\Sales\DispatchQc::class)
            ->where('status', 'accept')
            ->latestOfMany();
    }
    
    /**
     * Get the latest rejected dispatch QC
     */
    public function latestRejectedDispatchQc() {
        return $this->hasOne(\App\Models\Sales\DispatchQc::class)
            ->where('status', 'reject')
            ->latestOfMany();
    }
    
    /**
     * Check if this ticket has an accepted dispatch QC
     */
    public function hasAcceptedDispatchQc(): bool {
        return $this->dispatchQcs()->where('status', 'accept')->exists();
    }
    
    /**
     * Check if a new dispatch QC can be created for this ticket
     * (only if no accepted QC exists)
     */
    public function canCreateNewDispatchQc(): bool {
        return !$this->hasAcceptedDispatchQc();
    }

    public function loadingSlip() {
        return $this->hasOne(\App\Models\Sales\LoadingSlip::class);
    }

    public function delivery_challan_data() {
        return $this->hasOne(DeliveryChallanData::class, "ticket_id");
    }
}
