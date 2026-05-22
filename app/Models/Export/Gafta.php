<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gafta extends Model
{
    use HasFactory;

    protected $table = 'gaftas';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];
}
