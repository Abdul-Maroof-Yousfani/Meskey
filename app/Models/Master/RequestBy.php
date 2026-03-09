<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestBy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'request_bies';

    protected $fillable = [
        'department_id',
        'name',
        'description',
        'status',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
