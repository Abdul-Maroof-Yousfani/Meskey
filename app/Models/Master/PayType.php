<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayType extends Model
{
    use HasFactory, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)
    
    protected $table = "pay_types";
    
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'status'
    ];
}
