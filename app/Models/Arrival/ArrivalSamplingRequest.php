<?php

namespace App\Models\Arrival;

use Illuminate\Database\Eloquent\Model;
use App\Models\Acl\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArrivalSamplingRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'remark',
        'is_done',
        'company_id',
        'party_ref_no',
        'sampling_type',
        'is_re_sampling',
        'approved_status',
        'decision_making',
        'sample_taken_by',
        'approved_remarks',
        'lumpsum_deduction',
        'arrival_ticket_id',
        'is_resampling_made',
        'arrival_product_id',
        'is_lumpsum_deduction',
        'lumpsum_deduction_maund',
        'lumpsum_deduction_kgs_maund',
        'lumpsum_deduction_kgs',
    ];

    /**
     * Get the company that owns the arrival sampling request.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the arrival ticket associated with the sampling request.
     */
    public function arrivalTicket()
    {
        return $this->belongsTo(ArrivalTicket::class);
    }

    public function arrivalProduct()
    {
        return $this->belongsTo(Product::class, 'arrival_product_id');
    }

    public function takenByUser()
    {
        return $this->belongsTo(User::class, 'sample_taken_by');
    }

    public function compulsoryResults()
    {
        return $this->hasMany(ArrivalSamplingResultForCompulsury::class, 'arrival_sampling_request_id', 'id')
            ->with('qcParam');
    }

    public function slabResults()
    {
        return $this->hasMany(ArrivalSamplingResult::class, 'arrival_sampling_request_id', 'id')
            ->with('slabType');
    }

}




