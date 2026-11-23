<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $fillable = [
        'name',
        'description',
        'status',
    ];
}
