<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryChallanData extends Model
{
    use HasFactory;
    protected $guarded = [ "id", "created_at", "updated" ];
}
