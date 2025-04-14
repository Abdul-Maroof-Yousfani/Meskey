<?php

namespace App\Models\Arrival;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecondWeighbridge extends Model
{
    use HasFactory;

    protected $fillable = [
        'arrival_ticket_id',
        'company_id',
        'remark',
        'weight',
        'created_by',
    ];

    public function arrivalTicket()
    {
        return $this->belongsTo(ArrivalTicket::class, 'arrival_ticket_id');
    }
}
