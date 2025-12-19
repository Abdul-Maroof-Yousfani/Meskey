<?php

namespace App\Models\Sales;

use App\Models\Production\JobOrder\JobOrderPackingItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrder extends Model
{
    use HasFactory;


    public function packing_items() {
        return $this->hasMany(JobOrderPackingItem::class, "job_order_id");
    }
}
