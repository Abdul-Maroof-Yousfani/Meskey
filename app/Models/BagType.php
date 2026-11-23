<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BagType extends Model
{
    use HasFactory, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $fillable = ['name'];

    /**
     * Get all arrival approvals for this bag type.
     */
    public function arrivalApproves()
    {
        return $this->hasMany(ArrivalApprove::class);
    }
}
