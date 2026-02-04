<?php

namespace App\Models\Sales;

use App\Models\Production\JobOrder\JobOrderPackingItem;
use App\Models\Production\JobOrder\JobOrderPackingSubItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrder extends Model
{
    use HasFactory;


    public function packing_items() {
        return $this->hasMany(JobOrderPackingItem::class, "job_order_id");
    }

    public function sub_packing_items() {
        return $this->hasMany(JobOrderPackingSubItem::class, "job_order_packing_item_id");
    }
}
