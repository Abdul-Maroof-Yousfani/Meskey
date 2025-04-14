<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BagPacking extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Get all arrival approvals for this bag packing.
     */
    public function arrivalApproves()
    {
        return $this->hasMany(ArrivalApprove::class);
    }
}
