<?php

namespace App\Models\Arrival;

use App\Models\Product;
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
        'truck_no',
        'bilty_no',
        'loading_date',
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
}
