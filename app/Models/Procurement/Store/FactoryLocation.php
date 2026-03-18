<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\ArrivalLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactoryLocation extends Model
{
    use HasFactory;

    protected $guarded = ["id", "created_at", "updated_at"];
    protected $table = "model_arrival_location";

    public function factoryable()
    {
        return $this->morphTo();
    }

    public function factory()
    {
        return $this->belongsTo(ArrivalLocation::class, 'arrival_location_id');
    }
}

