<?php

namespace App\Models\Arrival;

use Illuminate\Database\Eloquent\Model;
use App\Models\Acl\Company;

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
}
