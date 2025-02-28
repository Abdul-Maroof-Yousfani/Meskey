<?php

namespace App\Models\Arrival;

use App\Models\Product;
use App\Models\ACL\Company;
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
        'truck_no',
        'bilty_no',
        'loading_date',
        'loading_weight',
        'first_weight',
        'second_weight',
        'net_weight',
        'remarks',
        'status',
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
}
