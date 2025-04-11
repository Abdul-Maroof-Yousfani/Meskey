<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalCompulsoryQcParam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'options',
        'properties',
    ];

    protected $casts = [
        'options' => 'array',
        'properties' => 'array',
    ];
}
