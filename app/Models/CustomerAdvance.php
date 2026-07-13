<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAdvance extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(\App\Models\Master\Customer::class);
    }

    public function adjustments()
    {
        return $this->hasMany(CustomerAdvanceAdjustment::class);
    }
}
