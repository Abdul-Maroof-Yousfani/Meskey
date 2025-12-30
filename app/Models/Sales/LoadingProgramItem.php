<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Master\Brand as Brands;

class LoadingProgramItem extends Model
{
    use HasFactory;

    protected $table = 'loading_program_items';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function loadingProgram()
    {
        return $this->belongsTo(LoadingProgram::class);
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalLocation::class);
    }

    public function subArrivalLocation()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalSubLocation::class);
    }

    public function brand()
    {
        return $this->belongsTo(\App\Models\Master\Brands::class, "brand_id");
    }
}
