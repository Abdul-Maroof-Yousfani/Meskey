<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadingSlip extends Model
{
    use HasFactory;

    protected $fillable = [
        'loading_program_item_id',
        'customer',
        'commodity',
        'so_qty',
        'do_qty',
        'factory',
        'gala',
        'no_of_bags',
        'bag_size',
        'kilogram',
        'remarks',
        'created_by'
    ];

    protected $casts = [
        'so_qty' => 'decimal:2',
        'do_qty' => 'decimal:2',
        'bag_size' => 'decimal:2',
        'kilogram' => 'decimal:2',
        'no_of_bags' => 'integer'
    ];

    public function loadingProgramItem(): BelongsTo
    {
        return $this->belongsTo(LoadingProgramItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function secondWeighbridge() {
        return $this->hasOne(\App\Models\Sales\SecondWeighbridge::class);
    }
}
