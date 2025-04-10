<?php

namespace App\Models\Arrival;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArrivalCustomSampling extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'arrival_custom_sampling';

    protected $fillable = [
        'party_ref_no',
    ];

    protected $dates = ['deleted_at'];
}
