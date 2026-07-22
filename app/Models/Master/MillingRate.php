<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MillingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'sublocation_id',
        'plant_id',
        'title',
        'description',
        'status',
        'company_id',
    ];

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function subLocation()
    {
        return $this->belongsTo(ArrivalLocation::class, 'sublocation_id');
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class, 'plant_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Acl\Company::class, 'company_id');
    }

    public function variables()
    {
        return $this->belongsToMany(Variable::class, 'milling_rate_variables', 'milling_rate_id', 'variable_id')
                    ->withPivot('value')
                    ->withTimestamps();
    }
}
