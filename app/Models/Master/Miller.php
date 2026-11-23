<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Miller extends Model
{
    use SoftDeletes, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $table = 'millers';

    protected $fillable = [
        'name',
        'address',
        'contact_person',
        'phone',
        'email',
        'status',
    ];
}
