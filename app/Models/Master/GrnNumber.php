<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;

class GrnNumber extends Model
{
    protected $fillable = ['model_id', 'model_type', 'unique_no', 'location_id', 'product_id'];
}
