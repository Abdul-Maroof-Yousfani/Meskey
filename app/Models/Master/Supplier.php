<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    // Define fillable attributes
    protected $fillable = [
        'company_id',
        'unique_no',
        'name',
        'prefix',
        'email',
        'phone',
        'address',
        'ntn',
        'stn',
        'status',
    ];

    // Define the relationship with Company (assuming the 'Company' model exists)
    public function company()
    {
        return $this->belongsTo(Company::class); // Adjust the model name if needed
    }
}