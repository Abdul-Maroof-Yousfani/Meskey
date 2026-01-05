<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use App\Models\Master\PlantBreakdownType;

class PlantBreakdownItem extends Model
{
    protected $fillable = [
        'company_id',
        'plant_breakdown_id',
        'breakdown_type_id',
        'from',
        'to',
        'hours',
        'remarks',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
    ];

    public function plantBreakdown()
    {
        return $this->belongsTo(PlantBreakdown::class);
    }

    public function breakdownType()
    {
        return $this->belongsTo(PlantBreakdownType::class);
    }
}
