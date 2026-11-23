<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TruckSizeRange extends Model
{
    use SoftDeletes, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $fillable = ['min_number', 'max_number', 'status'];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return $this->min_number  . ' kg - ' . $this->max_number . ' kg';
    }
}
