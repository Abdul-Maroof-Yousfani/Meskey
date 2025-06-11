<?php

namespace App\Models;

use App\Models\Acl\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseSamplingRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'arrival_product_id',
        'arrival_purchase_order_id',
        'purchase_ticket_id',
        'sampling_type',
        'is_custom_qc',
        'is_re_sampling',
        'remark',
        'decision_making',
        'is_done',
        'is_resampling_made',
        'approved_remarks',
        'approved_status',
        'party_ref_no',
        'sample_taken_by',
        'lumpsum_deduction',
        'lumpsum_deduction_kgs',
        'is_lumpsum_deduction',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseTicket()
    {
        return $this->belongsTo(PurchaseTicket::class, 'purchase_ticket_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(ArrivalPurchaseOrder::class, 'arrival_purchase_order_id');
    }

    public function contractProduct()
    {
        return $this->belongsTo(Product::class, 'arrival_product_id');
    }

    public function takenByUser()
    {
        return $this->belongsTo(User::class, 'sample_taken_by');
    }
}
