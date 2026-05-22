<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentCompany extends Model
{
    use HasFactory;

    protected $table = 'shipment_companies';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];
}
