<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentCountry extends Model
{
    use HasFactory;

    protected $table = 'shipment_countries';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];
}
