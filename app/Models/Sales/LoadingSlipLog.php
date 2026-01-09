<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadingSlipLog extends Model
{
    use HasFactory;

    protected $table = 'loading_slip_logs';

    protected $fillable = [
        'loading_slip_id',
        'dispatch_qc_id',
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
        'labour',
        'qc_remarks',
        'edited_by'
    ];

    protected $casts = [
        'so_qty' => 'decimal:2',
        'do_qty' => 'decimal:2',
        'bag_size' => 'decimal:2',
        'kilogram' => 'decimal:2',
        'no_of_bags' => 'integer'
    ];

    public function loadingSlip(): BelongsTo
    {
        return $this->belongsTo(LoadingSlip::class);
    }

    public function dispatchQc(): BelongsTo
    {
        return $this->belongsTo(DispatchQc::class);
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by');
    }
}

