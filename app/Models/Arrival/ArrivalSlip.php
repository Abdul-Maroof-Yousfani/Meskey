<?php

namespace App\Models\Arrival;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalSlip extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_no',
        'company_id',
        'arrival_ticket_id',
        'arrived_weight',
        'remark',
        'creator_id'
    ];

    public function arrivalTicket()
    {
        return $this->belongsTo(ArrivalTicket::class);
    }
}
