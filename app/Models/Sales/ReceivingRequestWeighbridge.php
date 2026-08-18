<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivingRequestWeighbridge extends Model
{
    use HasFactory;

    protected $table = 'receiving_request_weighbridges';

    protected $fillable = [
        'receiving_request_id',
        'name',
        'amount',
    ];

    public function receivingRequest()
    {
        return $this->belongsTo(ReceivingRequest::class, 'receiving_request_id');
    }
}
