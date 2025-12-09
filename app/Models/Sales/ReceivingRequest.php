<?php

namespace App\Models\Sales;

use App\Models\User;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivingRequest extends Model
{
    use HasFactory, HasApproval;

    protected $fillable = [
        'delivery_challan_id',
        'dc_no',
        'dc_date',
        'truck_number',
        'bilty',
        'labour',
        'transporter',
        'inhouse_weighbridge',
        'labour_amount',
        'transporter_amount',
        'weighbridge_amount',
        'inhouse_weighbridge_amount',
        'company_id',
        'created_by_id',
    ];

    protected $casts = [
        'dc_date' => 'date',
        'labour_amount' => 'decimal:2',
        'transporter_amount' => 'decimal:2',
        'weighbridge_amount' => 'decimal:2',
        'inhouse_weighbridge_amount' => 'decimal:2',
    ];

    public function deliveryChallan()
    {
        return $this->belongsTo(DeliveryChallan::class, 'delivery_challan_id');
    }

    public function items()
    {
        return $this->hasMany(ReceivingRequestItem::class, 'receiving_request_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}

