<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkingDay extends Model
{
    use HasFactory;

    protected $table = 'working_days';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];
}
