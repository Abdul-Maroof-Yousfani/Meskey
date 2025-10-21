<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionCompany extends Model
{
    use HasFactory;
        protected $fillable = [
        'company_id',
        'name',
        'description',
        'status',
    ];
}
