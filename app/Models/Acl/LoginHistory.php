<?php

namespace App\Models\Acl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    use HasFactory;
    
    protected $fillable = ['header', 'location', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
