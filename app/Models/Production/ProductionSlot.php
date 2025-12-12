<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionSlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'production_voucher_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'remarks',
        'attachment',
        'description'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function productionVoucher()
    {
        return $this->belongsTo(ProductionVoucher::class);
    }

    public function breaks()
    {
        return $this->hasMany(ProductionSlotBreak::class);
    }
}
