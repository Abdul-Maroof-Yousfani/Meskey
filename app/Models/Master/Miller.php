<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Miller extends Model
{
    use SoftDeletes;

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
