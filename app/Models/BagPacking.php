<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BagPacking extends Model
{
    use HasFactory, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $fillable = ['name', 'value'];

    protected $casts = [
        'value' => 'float',
    ];

    protected $appends = ['size'];

    public function getSizeAttribute()
    {
        return $this->name;
    }

    /**
     * Get all arrival approvals for this bag packing.
     */
    public function arrivalApproves()
    {
        return $this->hasMany(ArrivalApprove::class);
    }
}
