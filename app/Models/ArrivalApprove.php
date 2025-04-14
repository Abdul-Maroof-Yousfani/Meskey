<?php

namespace App\Models;

use App\Models\Arrival\ArrivalTicket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalApprove extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'arrival_ticket_id',
        'gala_name',
        'truck_no',
        'filling_bags_no',
        'bag_type_id',
        'bag_condition_id',
        'creator_id',
        'bag_packing_id',
        'bag_packing_approval',
        'total_receivings',
        'total_bags',
        'total_rejection',
        'amanat',
        'remark'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'bag_packing_approval' => 'string',
        'amanat' => 'string',
    ];

    public function arrivalTicket()
    {
        return $this->belongsTo(ArrivalTicket::class, 'arrival_ticket_id');
    }

    /**
     * Get the bag type associated with the arrival approval.
     */
    public function bagType()
    {
        return $this->belongsTo(BagType::class);
    }

    /**
     * Get the bag condition associated with the arrival approval.
     */
    public function bagCondition()
    {
        return $this->belongsTo(BagCondition::class);
    }

    /**
     * Get the bag packing associated with the arrival approval.
     */
    public function bagPacking()
    {
        return $this->belongsTo(BagPacking::class);
    }
}
