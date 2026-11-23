<?php
namespace App\Models;

use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $fillable = [
        'company_id',
        'name',
    ];

   
}