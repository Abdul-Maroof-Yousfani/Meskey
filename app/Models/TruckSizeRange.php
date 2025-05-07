<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TruckSizeRange extends Model
{
    use SoftDeletes;

    protected $fillable = ['min_number', 'max_number', 'status'];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return $this->min_number  . ' kg - ' . $this->max_number . ' kg';
    }
}
