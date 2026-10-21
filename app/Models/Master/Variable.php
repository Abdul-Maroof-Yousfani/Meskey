<?php

namespace App\Models\Master;

use App\Models\Master\Account\Account;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variable extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'description',
        'status',
        'company_id',
        'account_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function millingRates()
    {
        return $this->belongsToMany(MillingRate::class, 'milling_rate_variables', 'variable_id', 'milling_rate_id')
                    ->withPivot('value')
                    ->withTimestamps();
    }
}
