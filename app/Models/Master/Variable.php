<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variable extends Model
{
    use HasFactory;
    
    protected $fillable = ['title', 'description', 'status', 'company_id'];

    public function millingRates()
    {
        return $this->belongsToMany(MillingRate::class, 'milling_rate_variables', 'variable_id', 'milling_rate_id')
                    ->withPivot('value')
                    ->withTimestamps();
    }
}
