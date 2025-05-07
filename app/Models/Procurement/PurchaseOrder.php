<?php

namespace App\Models\Procurement;

use App\Models\TruckSizeRange;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    public function truckSizeRange()
    {
        return $this->belongsTo(TruckSizeRange::class);
    }
}
