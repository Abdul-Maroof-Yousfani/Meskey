<?php

namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Acl\Company;
class PaymentTerm extends Model
{
    use SoftDeletes, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)


    protected $fillable = [
        'company_id',
        'desc',
        'status',
        'title'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
