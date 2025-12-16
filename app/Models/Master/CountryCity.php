<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryCity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country_id',
        'country_code',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id','id');
    }
}
