<?php

namespace App\Models\Procurement\Store;

use App\Models\Sales\JobOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseAgainstJobOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'purchase_request_data_id',
        'job_order_id',
    ];

    public function job_order_data() {
        return $this->belongsTo(JobOrder::class, "job_order_id");
    }
}
