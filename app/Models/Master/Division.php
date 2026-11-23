<?php

namespace App\Models\Master;

use App\Traits\Cacheable;
use App\Models\ArrivalPurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory, Cacheable; // master data: queries cache (app/Traits/Cacheable.php)

    protected $fillable = [
        'name',
        'status',
        'hours',
        'added_by'
    ];

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function arrivalPurchaseOrders()
    {
        return $this->hasMany(ArrivalPurchaseOrder::class);
    }
}
