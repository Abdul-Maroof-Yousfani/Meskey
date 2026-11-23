<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BagCondition extends Model
{
    use HasFactory, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $fillable = ['name', 'status'];

    /**
     * Get all arrival approvals for this bag condition.
     */
    public function arrivalApproves()
    {
        return $this->hasMany(ArrivalApprove::class);
    }
}
