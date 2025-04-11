<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaudaType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_protected',
    ];

    const COMMODITY_RICE = 'rice';
    const COMMODITY_CORN = 'corn';
}
