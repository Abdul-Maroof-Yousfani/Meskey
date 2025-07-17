<?php

namespace App\Models;

use App\Models\Master\Broker;
use Illuminate\Database\Eloquent\Model;

class BrokerOwnerBankDetail extends Model
{
    protected $fillable = [
        'broker_id',
        'bank_name',
        'branch_name',
        'branch_code',
        'account_title',
        'account_number'
    ];

    // Relationship back to supplier
    public function broker()
    {
        return $this->belongsTo(Broker::class);
    }
}
