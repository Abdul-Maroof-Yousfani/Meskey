<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayType extends Model
{
    use HasFactory;
    
    protected $table = "pay_types";
    
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'status'
    ];
}
