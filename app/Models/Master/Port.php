<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use App\Models\Acl\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Port extends Model
{
    use HasFactory, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'country_id',
        'city_id',
        'type',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(CountryCity::class, 'city_id');
    }
}
