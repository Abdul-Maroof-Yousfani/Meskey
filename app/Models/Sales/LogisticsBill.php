<?php

namespace App\Models\Sales;

use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Transporter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogisticsBill extends Model
{
    use HasFactory;

    protected $table = 'receiving_requests';

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
        'transporter_deduction',
        'weighbridge_amount',
        'inhouse_weighbridge_amount',
        'company_id',
        'created_by_id',
        'am_approval_status',
        'am_change_made',
        'arrived_date',
        'arrived_weight',
        'exempted_weight',
        'payment_weight',
        'unloading_paid_by',
        'weighbridge_paid_by',
        'transporter_other_amount',
        'demurrage_detention_amount',
        'sales_return_id',
        'sales_return_qty',
        'sales_return_transporter_amount'
    ];

    protected $casts = [
        'dc_date' => 'date',
        'labour_amount' => 'decimal:2',
        'transporter_amount' => 'decimal:2',
        'transporter_deduction' => 'decimal:2',
        'transporter_other_amount' => 'decimal:2',
        'demurrage_detention_amount' => 'decimal:2',
        'sales_return_qty' => 'decimal:2',
        'sales_return_transporter_amount' => 'decimal:2',
        'weighbridge_amount' => 'decimal:2',
        'inhouse_weighbridge_amount' => 'decimal:2',
    ];

    public function deliveryChallan()
    {
        return $this->belongsTo(DeliveryChallan::class, 'delivery_challan_id');
    }

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id');
    }

    public function items()
    {
        return $this->hasMany(ReceivingRequestItem::class, 'receiving_request_id');
    }

    public function weighbridges()
    {
        return $this->hasMany(ReceivingRequestWeighbridge::class, 'receiving_request_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
