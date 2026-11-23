<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HsCode extends Model
{
    use HasFactory, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

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
