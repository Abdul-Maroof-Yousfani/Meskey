<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Acl\Company;
use App\Models\User;

class LocationType extends Model
{
    use HasFactory;

    protected $table = 'location_types';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
