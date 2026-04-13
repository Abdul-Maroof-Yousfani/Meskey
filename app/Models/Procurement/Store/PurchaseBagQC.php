<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\Account\Stock;
use App\Models\Master\Tax;
use App\Models\Product;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseBagQC extends Model
{
    use HasFactory, HasApproval;
    protected $table = "purchase_bag_qc";
    protected $guarded = ["id", "created", "updated_at"];

    protected $attributes = [
        'deduction_per_bag' => 0
    ];

    public function bags() {
        return $this->hasMany(QCItems::class, "qc_id");
    }
    
    // public static function booted() {
    //     static::updated(function($bag_qc) {
    //         if($bag_qc->wasChanged('am_approval_status') && $bag_qc->am_approval_status == "approved") {
    //             approve_qc($bag_qc);
    //         }
    //     });

    // }

    public function onApprovalComplete() {
        $this->am_approval_status = "approved";
        approve_qc($this);
    }

    public function scopeFilter($query)
    {
        if ($this->canUserApprove()) {
            return $query->where('is_qc_approved', 'pending');
        }

        return $query;
    }

    public function grn() {
        return $this->belongsTo(PurchaseOrderReceivingData::class, "purchase_order_receiving_data_id");
    }

}

