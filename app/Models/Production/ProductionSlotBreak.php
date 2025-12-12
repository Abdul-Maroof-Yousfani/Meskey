<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionSlotBreak extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'production_slot_id',
        'break_in',
        'break_out',
        'reason'
    ];

    protected $casts = [
        // Time fields stored as time, formatted in views
    ];

    public function productionSlot()
    {
        return $this->belongsTo(ProductionSlot::class);
    }
}
