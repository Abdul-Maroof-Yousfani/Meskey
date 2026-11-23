<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalCompulsoryQcParam extends Model
{
    use HasFactory, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $fillable = [
        'name',
        'type',
        'options',
        'calculation_base_type',
        'properties',
    ];

    protected $casts = [
        'options' => 'array',
        'properties' => 'array',
    ];
}
