<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HsCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'code',
        'description',
        'custom_duty',
        'excise_duty',
        'sales_tax',
        'income_tax',
        'status',
    ];
}
