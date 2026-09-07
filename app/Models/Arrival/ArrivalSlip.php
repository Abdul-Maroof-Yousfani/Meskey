<?php

namespace App\Models\Arrival;

use App\Models\Master\GrnNumber;
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

    public function grnNumber()
    {
        return $this->morphOne(GrnNumber::class, 'model');
    }
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'creator_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'creator_id');
    }
}
