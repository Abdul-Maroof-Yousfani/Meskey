<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $fillable = [
        'name',
        'alpha_2_code',
        'alpha_3_code',
        'phone_code',
    ];

    public function cities()
    {
        return $this->hasMany(CountryCity::class, 'country_id','id');
    }
}
