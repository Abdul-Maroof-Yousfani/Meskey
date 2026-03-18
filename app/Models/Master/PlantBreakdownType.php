<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Acl\Company;

class PlantBreakdownType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
