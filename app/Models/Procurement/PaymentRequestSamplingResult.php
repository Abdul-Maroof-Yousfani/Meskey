<?php

namespace App\Models\Procurement;

use App\Models\Master\ProductSlabType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequestSamplingResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_request_data_id',
        'slab_type_id',
        'name',
        'checklist_value',
        'suggested_deduction',
        'applied_deduction',
        'deduction_type',
        'deduction_amount'
    ];

    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    public function slabType()
    {
        return $this->belongsTo(ProductSlabType::class);
    }
}
