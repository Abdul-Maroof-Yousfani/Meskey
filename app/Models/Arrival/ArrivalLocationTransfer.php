<?php

namespace App\Models\Arrival;

use App\Models\Acl\Company as AclCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalLocation;
use App\Models\Master\ArrivalLocation as MasterArrivalLocation;
use App\Models\User;

class ArrivalLocationTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'arrival_location_transfers';

    protected $fillable = [
        'company_id',
        'arrival_ticket_id',
        'creator_id',
        'arrival_location_id',
        'remark',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $dates = ['deleted_at', 'created_at', 'updated_at'];

    public function company()
    {
        return $this->belongsTo(AclCompany::class, 'company_id');
    }

    public function arrivalTicket()
    {
        return $this->belongsTo(ArrivalTicket::class, 'arrival_ticket_id');
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(MasterArrivalLocation::class, 'arrival_location_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
