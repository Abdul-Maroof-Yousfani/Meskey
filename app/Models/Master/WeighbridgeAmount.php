<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeighbridgeAmount extends Model
{
    use HasFactory, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $table = "weighbridge_amounts";

    protected $guarded = ["id", "created_at", "updated_at"];

    public function company() {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function companyLocation() {
        return $this->belongsTo(\App\Models\Master\CompanyLocation::class);
    }

    public function truckType() {
        return $this->belongsTo(ArrivalTruckType::class, "truck_type_id");
    }

    public function createdBy() {
        return $this->belongsTo(\App\Models\User::class, "created_by");
    }
}
