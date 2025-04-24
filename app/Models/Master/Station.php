<?php

namespace App\Models\Master;

use App\Models\Arrival\ArrivalTicket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'line_type',
        'description',
        'status',
    ];

    public function arrivalTickets()
    {
        return $this->hasMany(ArrivalTicket::class);
    }
}
