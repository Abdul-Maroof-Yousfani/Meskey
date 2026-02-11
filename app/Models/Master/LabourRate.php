<?php

namespace App\Models\Master;

use App\Models\BagPacking;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabourRate extends Model
{
    use HasFactory;
    protected $table = "labour_rate";
    protected $guarded = [ "id", "created_at", "updated_at" ];

    public function bagPacking() {
        return $this->belongsTo(BagPacking::class, "bag_packing_id");
    }

    public function category() {
        return $this->belongsTo(Category::class, "category_id");
    }

    public function factory() {
        return $this->belongsTo(ArrivalLocation::class, "factory_id");
    }
}
