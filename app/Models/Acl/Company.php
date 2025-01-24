<?php

namespace App\Models\Acl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'prefix', 'email', 'phone', 'address', 'registration_no', 'logo', 'connection_database', 'app_key', 'status','ntn','stn'];

    
 public function users()
    {
        return $this->belongsToMany(User::class, 'company_user_role')
                    ->withPivot('role_id')
                    ->withTimestamps();
    }

}
