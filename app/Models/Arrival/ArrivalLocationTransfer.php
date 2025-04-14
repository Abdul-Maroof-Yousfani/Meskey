<?php

namespace App\Models\Arrival;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalLocationTransfer extends Model
{
    use HasFactory;

    protected $table = 'arrival_location_transfers';

    protected $fillable = [
        'company_id',
        'arrival_ticket_id',
        'creator_id',
        'arrival_location_id',
        'remark',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $dates = ['deleted_at', 'created_at', 'updated_at'];
}
