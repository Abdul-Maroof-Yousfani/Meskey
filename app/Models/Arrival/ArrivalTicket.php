<?php

namespace App\Models\Arrival;

use App\Models\{ArrivalPurchaseOrder, Product, SaudaType, User};
use App\Models\ACL\Company;
use App\Models\Master\{ArrivalTruckType, Station};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArrivalTicket extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'unique_no',
        'company_id',
        'product_id',
        'supplier_name',
        'broker_name',
        'decision_id',
        'truck_type_id',
        'truck_no',
        'bilty_no',
        'bags',
        'station_name',
        'loading_date',
        'loading_weight',
        'first_weight',
        'second_weight',
        'net_weight',
        'remarks',
        'status',
        'accounts_of_id',
        'arrival_purchase_order_id',
        'sauda_type_id',


        'first_qc_status',
        'location_transfer_status',
        'second_qc_status',
        'document_approval_status',
        'first_weighbridge_status',
        'second_weighbridge_status',
        'arrival_slip_status',

    ];

    /**
     * Relationships.
     */

    // Company relationship
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Product relationship
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function arrivalSamplingRequests()
    {
        return $this->hasMany(ArrivalSamplingRequest::class, 'arrival_ticket_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(ArrivalPurchaseOrder::class, 'arrival_purchase_order_id');
    }

    public function saudaType()
    {
        return $this->belongsTo(SaudaType::class, 'sauda_type_id');
    }





    // Relationships
    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function decisionBy()
    {
        return $this->belongsTo(User::class, 'decision_id');
    }

    public function truckType()
    {
        return $this->belongsTo(ArrivalTruckType::class, 'truck_type_id');
    }

    public function accountsOf()
    {
        return $this->belongsTo(User::class, 'accounts_of_id');
    }


        public function unloadingLocation()
    {
        return $this->hasOne(ArrivalLocationTransfer::class, 'arrival_ticket_id');
    }
        public function arrivalSlip()
    {
        return $this->hasOne(ArrivalSlip::class, 'arrival_ticket_id');
    }
}
