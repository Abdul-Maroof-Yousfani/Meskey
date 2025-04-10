<?php

namespace App\Models\Arrival;

use Illuminate\Database\Eloquent\Model;
use App\Models\Acl\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArrivalSamplingRequest extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'company_id',
        'arrival_ticket_id',
        'sampling_type',
        'is_re_sampling',
        'remark',
        'is_done',
        'is_resampling_made',
        'approved_remarks',
        'approved_status',
        'party_ref_no',
        'sample_taken_by'
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

    public function takenByUser()
    {
        return $this->belongsTo(User::class, 'sample_taken_by');
    }
}
