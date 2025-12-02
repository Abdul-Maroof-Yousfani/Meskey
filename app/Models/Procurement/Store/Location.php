<?php

namespace App\Models\Procurement\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $guarded = ["id", "created_at", "updated_at"];
    protected $table = "model_location";

    public function locationable() {
        return $this->morphTo();
    }
}
