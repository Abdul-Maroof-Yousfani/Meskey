<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CFreightRate extends Model
{
    protected $guarded = [];

    public function cFreight()
    {
        return $this->belongsTo(CFreight::class, 'c_freight_id');
    }
}
