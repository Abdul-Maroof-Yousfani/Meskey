<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAdvanceAdjustment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function customerAdvance()
    {
        return $this->belongsTo(CustomerAdvance::class);
    }
}
