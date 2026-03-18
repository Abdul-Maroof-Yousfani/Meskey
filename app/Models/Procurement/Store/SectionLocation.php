<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\ArrivalSubLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionLocation extends Model
{
    use HasFactory;

    protected $guarded = ["id", "created_at", "updated_at"];
    protected $table = "model_arrival_sub_location";

    public function sectionable()
    {
        return $this->morphTo();
    }

    public function section()
    {
        return $this->belongsTo(ArrivalSubLocation::class, 'arrival_sub_location_id');
    }
}

