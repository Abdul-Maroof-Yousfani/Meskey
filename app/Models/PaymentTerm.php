<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Acl\Company;
class PaymentTerm extends Model
{
    use SoftDeletes;


    protected $fillable = [
        'company_id',
        'desc',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
