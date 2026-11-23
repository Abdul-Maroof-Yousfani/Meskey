<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaudaType extends Model
{
    use SoftDeletes, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $fillable = [
        'name',
        'description',
        'is_protected',
    ];

    const COMMODITY_RICE = 'rice';
    const COMMODITY_CORN = 'corn';
}
