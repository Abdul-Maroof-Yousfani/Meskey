<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Acl\Company;

class Tax extends Model
{
    use SoftDeletes, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)


    protected $fillable = [
        'company_id',
        'name',
        'percentage',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
