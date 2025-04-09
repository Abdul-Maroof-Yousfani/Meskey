<?php

namespace App\Models\Arrival;

use App\Models\{Product,User};
use App\Models\ACL\Company;
use App\Models\Master\{ArrivalTruckType,Station};
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
        'accounts_off_name',
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
        
        
        'first_qc_status',
        'location_transfer_status',
        'second_qc_status',
        'document_approval_status',
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


}
