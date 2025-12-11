<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'currency_name',
        'currency_code',
        'rate',
        'description',
        'status',
    ];
}
