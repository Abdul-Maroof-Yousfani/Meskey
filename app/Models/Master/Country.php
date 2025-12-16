<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'alpha_2_code',
        'alpha_3_code',
        'region',
        'phone_code',
    ];

    public function cities()
    {
        return $this->hasMany(CountryCity::class, 'country_id','id');
    }
}
