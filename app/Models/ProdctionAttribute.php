<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdctionAttribute extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'prodction_attribute';

    protected $fillable = [
        'key',
        'slug',
        'type',
        'for_general',
        'status',
        'created_by',
    ];
}
