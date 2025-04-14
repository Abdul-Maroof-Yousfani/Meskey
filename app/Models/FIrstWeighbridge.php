<?php

namespace App\Models;

use App\Models\Arrival\ArrivalTicket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FirstWeighbridge extends Model
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
